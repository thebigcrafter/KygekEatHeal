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

namespace KygekTeam\KygekEatHeal\commands;

use KygekTeam\KygekEatHeal\EatHeal;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

class HealCommand extends PluginCommand {

    public function __construct(string $name, EatHeal $owner) {
        parent::__construct($name, $owner);

        $desc = empty($owner->getConfig()->get("heal-desc")) ?
            "Heal yourself or a player" : $owner->getConfig()->get("heal-desc");
        $this->setDescription($desc);
        $this->setAliases($owner->getConfig()->get("heal-aliases", []));
        $this->setUsage("/heal [player]");
        $this->setPermission("kygekeatheal.heal");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
        if (!$this->testPermission($sender)) return true;

        /** @var EatHeal $owner */
        $owner = $this->getPlugin();
        $owner->getConfig()->reload();

        if (!isset($args[0])) {
            if (!$sender instanceof Player) {
                $sender->sendMessage(EatHeal::PREFIX . EatHeal::INFO . "Usage: /heal <player>");
                return true;
            }

            $result = $owner->healTransaction($sender);

            if ($result === true) {
                $sender->sendMessage(EatHeal::PREFIX . EatHeal::INFO . "You are already healthy!");
                return true;
            }
            if ($result === false) {
                $sender->sendMessage(EatHeal::PREFIX . EatHeal::WARNING . "You do not have enough money to heal!");
                return true;
            }

            $price = ($owner->economyEnabled) ?
                " for " . $owner->economyAPI->getMonetaryUnit() . $result : "";

            $sender->sendMessage(EatHeal::PREFIX . EatHeal::INFO . "You have been healed" . $price);
        } else {
            $player = $owner->getServer()->getPlayer($args[0]);

            if (is_null($player)) {
                $sender->sendMessage(EatHeal::PREFIX . EatHeal::WARNING . "Player is not online!");
                return true;
            }

            $isPlayer = $sender instanceof Player;
            $result = $owner->healTransaction($player, $isPlayer);

            if ($result === true) {
                $sender->sendMessage(EatHeal::PREFIX . EatHeal::INFO . $player->getName() . " is already healthy!");
                return true;
            }
            if ($result === false) {
                $sender->sendMessage(EatHeal::PREFIX . EatHeal::WARNING . "You do not have enough money to heal " . $player->getName() . "!");
                return true;
            }

            $price = ($owner->economyEnabled && $isPlayer) ?
                " for " . $owner->economyAPI->getMonetaryUnit() . $result : "";

            // Sends a message to healer
            $sender->sendMessage(EatHeal::PREFIX . EatHeal::INFO . "Player " . $player->getName() . " has been healed" . $price);
            // Sends a message to the player being healed
            $player->sendMessage(EatHeal::PREFIX . EatHeal::INFO . "You have been healed by " . $sender->getName());
        }

        return true;
    }

}