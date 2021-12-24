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

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\BedrockEconomy;
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
    private ?BedrockEconomyAPI $economyAPI;

    protected function onEnable() : void {
        /** @phpstan-ignore-next-line */
        if (self::IS_DEV) {
            $this->getLogger()->warning("This plugin is running on a development version. There might be some major bugs. If you found one, please submit an issue in https://github.com/KygekTeam/KygekEatHeal/issues.");
        }

        if (!class_exists(BedrockEconomy::class)) {
            $this->economyAPI = null;
        } else {
            $this->economyEnabled = true;
            $this->economyAPI = BedrockEconomy::getInstance()->getAPI();
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
            $account = $this->economyAPI->getPlayerAccount($senderPlayer !== null ? $senderPlayer->getName() : $player->getName());

            if ($account->getBalance() < $price) return false;
            $account->decrementBalance($price);
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
            $account = $this->economyAPI->getPlayerAccount($senderPlayer !== null ? $senderPlayer->getName() : $player->getName());

            if ($account->getBalance() < $price) return false;
            $account->decrementBalance($price);
        }

        $healValue = $this->getHealValue($player);
        $player->setHealth($healValue);
        return $price;
    }

    public function reloadConfig() : void {
        parent::reloadConfig();
        self::$prefix = TF::colorize($this->getConfig()->get("message-prefix", "&e[KygekEatHeal] ")) . TF::RESET;
    }

}
