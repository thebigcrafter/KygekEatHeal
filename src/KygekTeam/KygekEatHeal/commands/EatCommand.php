<?php

/*
 * Eat and heal a player instantly!
 * Copyright (C) 2020-2023 KygekTeam
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace KygekTeam\KygekEatHeal\commands;

use cooldogedev\BedrockEconomy\BedrockEconomy;
use KygekTeam\KygekEatHeal\EatHeal;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use function is_null;
use function time;

class EatCommand extends Command implements PluginOwned {

	/** @var int[] */
	private array $cooldownSelf = [];
	/** @var int[] */
	private array $cooldownOther = [];

	private EatHeal $owner;

	public function __construct(string $name, EatHeal $owner) {
		$desc = empty($owner->getConfig()->getNested("command.description.eat")) ?
			"Eat or feed a player" : $owner->getConfig()->getNested("command.description.eat");
		parent::__construct($name, $desc, "/eat [player]", $owner->getConfig()->getNested("command.aliases.eat", []));

		$this->owner = $owner;
		$this->setPermission("kygekeatheal.eat");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		if (!$sender->hasPermission("kygekeatheal") && !$this->testPermission($sender)) return true;

		$owner = $this->getOwningPlugin();
		$config = $owner->getConfig();
		$owner->reloadConfig();

		if (!isset($args[0])) {
			if (!$sender instanceof Player) {
				$sender->sendMessage(EatHeal::$prefix . EatHeal::INFO . "Usage: /eat <player>");
				return true;
			}

			$cooldown = $config->getNested("cooldown.self.eat",0);
			if ($cooldown !== 0) {
				if (isset($this->cooldownSelf[$sender->getName()]) && time() - $cooldown < $this->cooldownSelf[$sender->getName()]) {
					$duration = $this->cooldownSelf[$sender->getName()] - (time() - $cooldown);
					$sec = $duration <= 1 ? "second" : "seconds";
					$sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "Eating is currently on cooldown. Please wait " . $duration . " " . $sec . " before eating again.");
					return true;
				}
				$this->cooldownSelf[$sender->getName()] = time();
			}

			$owner->eatTransaction($sender, true, null,
				static function (int|string $result) use ($owner, $sender) {
					switch ($result) {
						case EatHeal::TRANSACTION_ERROR_CAUSE_NO_ACCOUNT:
							$sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "Unable to make transaction due to the player not having an BedrockEconomy account.");
							return;
						case EatHeal::TRANSACTION_ERROR_CAUSE_FULL:
							$sender->sendMessage(EatHeal::$prefix . EatHeal::INFO . "You are already full!");
							return;
						case EatHeal::TRANSACTION_ERROR_CAUSE_INSUFFICIENT_BALANCE:
							$sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "You do not have enough money to eat!");
							return;
						case EatHeal::TRANSACTION_ERROR_CAUSE_NOT_UPDATED:
							$sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "Unable to make transaction due to the BedrockEconomy account balance not getting updated.");
							return;
					}

					$price = ($owner->economyEnabled && $result > 0) ?
						" for " . BedrockEconomy::getInstance()->getCurrencyManager()->getSymbol() . $result : "";

					$sender->sendMessage(EatHeal::$prefix . EatHeal::INFO . "You have eaten" . $price);
				}
			);
		} else {
			$player = $owner->getServer()->getPlayerByPrefix($args[0]);

			if (is_null($player)) {
				$sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "Player is not online!");
				return true;
			}

			$cooldown = $config->getNested("cooldown.others.eat",0);
			if (($cooldown !== 0 && $sender instanceof Player) || (!$sender instanceof Player && $config->getNested("cooldown.enable-console", false))) {
				if (isset($this->cooldownOther[$sender->getName()]) && time() - $cooldown < $this->cooldownOther[$sender->getName()]) {
					$duration = $this->cooldownOther[$sender->getName()] - (time() - $cooldown);
					$sec = $duration <= 1 ? "second" : "seconds";
					$sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "Feeding other player is currently on cooldown. Please wait " . $duration . " " . $sec . " before feeding other player again.");
					return true;
				}
				$this->cooldownOther[$sender->getName()] = time();
			}

			$isPlayer = $sender instanceof Player;
			$callback = static function (int|string $result) use ($isPlayer, $owner, $player, $sender) {
				switch ($result) {
					case EatHeal::TRANSACTION_ERROR_CAUSE_NO_ACCOUNT:
						$sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "Unable to make transaction due to the player not having an BedrockEconomy account.");
						return;
					case EatHeal::TRANSACTION_ERROR_CAUSE_FULL:
						$sender->sendMessage(EatHeal::$prefix . EatHeal::INFO . $player->getName() . " is already full!");
						return;
					case EatHeal::TRANSACTION_ERROR_CAUSE_INSUFFICIENT_BALANCE:
						$sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "You do not have enough money to feed " . $player->getName() . "!");
						return;
					case EatHeal::TRANSACTION_ERROR_CAUSE_NOT_UPDATED:
						$sender->sendMessage(EatHeal::$prefix . EatHeal::WARNING . "Unable to make transaction due to the BedrockEconomy account balance not getting updated.");
						return;
				}

				$price = ($owner->economyEnabled && $isPlayer && $result > 0) ?
					" for " . BedrockEconomy::getInstance()->getCurrencyManager()->getSymbol() . $result : "";

				// Sends a message to feeder
				$sender->sendMessage(EatHeal::$prefix . EatHeal::INFO . "Player " . $player->getName() . " has been fed" . $price);
				// Sends a message to eater
				$player->sendMessage(EatHeal::$prefix . EatHeal::INFO . "You have been fed by " . $sender->getName());
			};
			if ($isPlayer) {
				$owner->eatTransaction($player, true, $sender, $callback);
			} else {
				$owner->eatTransaction($player, false, null, $callback);
			}
		}

		return true;
	}

	public function getOwningPlugin() : EatHeal {
		return $this->owner;
	}

}
