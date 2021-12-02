<?php

/*
 * Eat and heal a player instantly!
 * Copyright (C) 2020-2021 KygekTeam
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace KygekTeam\KygekEatHeal;

use cooldogedev\BedrockEconomy\BedrockEconomy;
use cooldogedev\BedrockEconomy\session\cache\SessionCache;
use cooldogedev\BedrockEconomy\session\SessionManager;
use KygekTeam\KtpmplCfs\KtpmplCfs;
use KygekTeam\KygekEatHeal\commands\EatCommand;
use KygekTeam\KygekEatHeal\commands\HealCommand;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;

class EatHeal extends PluginBase {

    private const IS_DEV = false;

    public const INFO = TF::GREEN;
    public const WARNING = TF::RED;

    public static string $prefix = TF::YELLOW . "[KygekEatHeal] " . TF::RESET;

    public bool $economyEnabled = false;
    public ?SessionManager $economyAPI;

    protected function onEnable() : void {
        /** @phpstan-ignore-next-line */
        if (self::IS_DEV) {
            $this->getLogger()->warning("This plugin is running on a development version. There might be some major bugs. If you found one, please submit an issue in https://github.com/KygekTeam/KygekEatHeal/issues.");
        }

        if (!class_exists(BedrockEconomy::class)) {
            $this->economyAPI = null;
        } else {
            $this->economyEnabled = true;
            $this->economyAPI = BedrockEconomy::getInstance()->getSessionManager();
        }

        $this->saveDefaultConfig();
        KtpmplCfs::checkConfig($this, "2.0");
        $this->getServer()->getCommandMap()->registerAll("KygekEatHeal", [
            new EatCommand("eat", $this), new HealCommand("heal", $this)
        ]);

        self::$prefix = TF::colorize($this->getConfig()->get("message-prefix", "&e[KygekEatHeal] ")) . TF::RESET;
        KtpmplCfs::checkUpdates($this);
    }

    private function getEatValue(Player $player) : float {
        $config = $this->getConfig()->getNested("points.eat", "max");
        $food = $player->getHungerManager()->getMaxFood() - $player->getHungerManager()->getFood();

        return ($config === "max" ? $food : ($config > $food ? $food : (float) $config));
    }

    private function getHealValue(Player $player) : float {
        $config = $this->getConfig()->getNested("points.heal", "max");
        $maxHealth = $player->getMaxHealth();
        $health = $maxHealth - $player->getHealth();

        return ($config === "max" ? $maxHealth : ($config > $health ? $maxHealth : (float) $config + $player->getHealth()));
    }

    public function eatTransaction(Player $player, bool $isPlayer = true, Player $senderPlayer = null) : bool|int {
        if ($player->getHungerManager()->getFood() === 20.0) return true;

        $price = (int) $this->getConfig()->getNested("price.eat", 0);
        if ($this->economyEnabled && $isPlayer && $price > 0) {
            $xuid = $senderPlayer !== null ? $senderPlayer->getXuid() : $player->getXuid();
            $session = $this->getEconomySession($xuid);

            if ($session->getBalance() < $price) return false;
            $session->subtractFromBalance($price);
        }

        $eatValue = $this->getEatValue($player);
        $player->getHungerManager()->addFood($eatValue);
        $player->getHungerManager()->addSaturation(20);
        return $price;
    }

    public function healTransaction(Player $player, bool $isPlayer = true, Player $senderPlayer = null) : bool|int {
        if ($player->getHealth() === 20.0) return true;

        $price = (int) $this->getConfig()->getNested("price.heal", 0);
        if ($this->economyEnabled && $isPlayer && $price > 0) {
            $xuid = $senderPlayer !== null ? $senderPlayer->getXuid() : $player->getXuid();
            $session = $this->getEconomySession($xuid);

            if ($session->getBalance() < $price) return false;
            $session->subtractFromBalance($price);
        }

        $healValue = $this->getHealValue($player);
        $player->setHealth($healValue);
        return $price;
    }

    public function reloadConfig() : void {
        parent::reloadConfig();
        self::$prefix = TF::colorize($this->getConfig()->get("message-prefix", "&e[KygekEatHeal] ")) . TF::RESET;
    }

    /**
     * @param string $xuid
     * @return false|SessionCache
     */
    public function getEconomySession(string $xuid) : bool|SessionCache {
        if (!$this->economyEnabled) {
            return false;
        }

        return $this->economyAPI->getSession($xuid)->getCache();
    }

}
