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

use KygekTeam\KtpmplCfs\KtpmplCfs;
use KygekTeam\KygekEatHeal\commands\EatCommand;
use KygekTeam\KygekEatHeal\commands\HealCommand;
use onebone\economyapi\EconomyAPI;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;

class EatHeal extends PluginBase {

    public const PREFIX = TF::YELLOW . "[KygekEatHeal] " . TF::RESET;
    public const INFO = TF::GREEN;
    public const WARNING = TF::RED;

    /** @var bool */
    public $economyEnabled = false;
    /** @var EconomyAPI|null */
    public $economyAPI;

    public function onEnable() {
        if (!class_exists(EconomyAPI::class)) {
            $this->economyAPI = null;
        } else {
            $this->economyEnabled = true;
            $this->economyAPI = EconomyAPI::getInstance();
        }

        $this->saveDefaultConfig();
        KtpmplCfs::checkConfig($this, "1.2");
        $this->getServer()->getCommandMap()->registerAll("KygekEatHeal", [
            new EatCommand("eat", $this), new HealCommand("heal", $this)
        ]);

        KtpmplCfs::checkUpdates($this);
    }

    private function getEatValue(Player $player) : float {
        $config = $this->getConfig()->getNested("points.eat", "max");
        $food = $player->getMaxFood() - $player->getFood();

        return ($config === "max" ? $food : ($config > $food ? $food : (float) $config));
    }

    private function getHealValue(Player $player) : float {
        $config = $this->getConfig()->getNested("points.heal", "max");
        $maxHealth = $player->getMaxHealth();
        $health = $maxHealth - $player->getHealth();

        return ($config === "max" ? $maxHealth : ($config > $health ? $maxHealth : (float) $config + $player->getHealth()));
    }

    public function eatTransaction(Player $player, bool $isPlayer = true, Player $senderPlayer = null) {
        if ($player->getFood() === (float) 20) return true;

        $price = $this->getConfig()->getNested("price.eat", 0);
        if ($this->economyEnabled && $isPlayer && $price > 0) {
            if ($this->economyAPI->myMoney($senderPlayer ?? $player) < $price) return false;
            $this->economyAPI->reduceMoney($senderPlayer ?? $player, $price);
        }

        $eatValue = $this->getEatValue($player);
        $player->addFood($eatValue);
        $player->addSaturation(20);
        return $price ?? 0;
    }

    public function healTransaction(Player $player, bool $isPlayer = true, Player $senderPlayer = null) {
        if ($player->getHealth() === (float) 20) return true;

        $price = $this->getConfig()->getNested("price.heal", 0);
        if ($this->economyEnabled && $isPlayer && $price > 0) {
            if ($this->economyAPI->myMoney($senderPlayer ?? $player) < $price) return false;
            $this->economyAPI->reduceMoney($senderPlayer ?? $player, $price);
        }

        $healValue = $this->getHealValue($player);
        $player->setHealth($healValue);
        return $price ?? 0;
    }

}
