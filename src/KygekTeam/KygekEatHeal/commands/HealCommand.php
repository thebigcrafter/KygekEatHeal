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
use pocketmine\command\Command;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;

class HealCommand extends Command implements PluginOwned {

    /** @var int[] */
    private array $cooldownSelf = [];
    /** @var int[] */
    private array $cooldownOther = [];

    private EatHeal $owner;

    public function __construct(string $name, EatHeal $owner) {
        $desc = empty($owner->getConfig()->getNested("command.description.heal")) ?
            "Heal yourself or a player" : $owner->getConfig()->getNested("command.description.heal");
        parent::__construct($name, $desc, "/heal [player]", $owner->getConfig()->getNested("command.aliases.heal", []));

        $this->owner = $owner;
        $this->setPermission("kygekeatheal.heal");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
        if (!$sender->hasPermission("kygekeatheal") && !$this->testPermission($sender)) return true;

        $owner = $this->getOwningPlugin();
        $config = $owner->getConfig();
        $owner->reloadConfig();

        if (!isset($args[0])) {
            if (!$sender instanceof Player) {
                $sender->sendMessage(EatHeal::$prefix . EatHeal::INFO . "Usage: /heal <player>");
                return true;
            }

            $cooldown = $config->getNested("cooldown.self.heal",0);
            if ($cooldown !== 0) {
                if (isset($this->cooldownSelf[$sender->getName()]) && time() - $cooldown < $this->cooldownSelf[$sender->getName()]) {
                    $duration = $this->cooldownSelf[$sender->getName()] - (time() - $cooldown);
                    $sec = $duration <= 1 ? "second" : "seconds";
                    $sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "Healing yourself is currently on cooldown. Please wait " . $duration . " " . $sec . " before healing yourself again.");
                    return true;
                }
                $this->cooldownSelf[$sender->getName()] = time();
            }

            $result = $owner->healTransaction($sender);

            if ($result === true) {
                $sender->sendMessage(EatHeal::$prefix . EatHeal::INFO . "You are already healthy!");
                return true;
            }
            if ($result === false) {
                $sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "You do not have enough money to heal!");
                return true;
            }

            $price = ($owner->economyEnabled && $result > 0) ?
                " for " . $owner->economyAPI->getPlugin()->getCurrencyManager()->getSymbol() . $result : "";

            $sender->sendMessage(EatHeal::$prefix . EatHeal::INFO . "You have been healed" . $price);
        } else {
            $player = $owner->getServer()->getPlayerByPrefix($args[0]);

            if (is_null($player)) {
                $sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "Player is not online!");
                return true;
            }

            $cooldown = $config->getNested("cooldown.others.heal",0);
            if (($cooldown !== 0 && $sender instanceof Player) || (!$sender instanceof Player && $config->getNested("cooldown.enable-console", false))) {
                if (isset($this->cooldownOther[$sender->getName()]) && time() - $cooldown < $this->cooldownOther[$sender->getName()]) {
                    $duration = $this->cooldownOther[$sender->getName()] - (time() - $cooldown);
                    $sec = $duration <= 1 ? "second" : "seconds";
                    $sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "Healing other player is currently on cooldown. Please wait " . $duration . " " . $sec . " before healing other player again.");
                    return true;
                }
                $this->cooldownOther[$sender->getName()] = time();
            }

            if ($isPlayer = $sender instanceof Player) {
                $result = $owner->healTransaction($player, true, $sender);
            } else {
                $result = $owner->healTransaction($player, false);
            }

            if ($result === true) {
                $sender->sendMessage(EatHeal::$prefix . EatHeal::INFO . $player->getName() . " is already healthy!");
                return true;
            }
            if ($result === false) {
                $sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "You do not have enough money to heal " . $player->getName() . "!");
                return true;
            }

            $price = ($owner->economyEnabled && $isPlayer && $result > 0) ?
                " for " . $owner->economyAPI->getPlugin()->getCurrencyManager()->getSymbol() . $result : "";

            // Sends a message to healer
            $sender->sendMessage(EatHeal::$prefix . EatHeal::INFO . "Player " . $player->getName() . " has been healed" . $price);
            // Sends a message to the player being healed
            $player->sendMessage(EatHeal::$prefix . EatHeal::INFO . "You have been healed by " . $sender->getName());
        }

        return true;
    }

    public function getOwningPlugin() : EatHeal {
        return $this->owner;
    }

}