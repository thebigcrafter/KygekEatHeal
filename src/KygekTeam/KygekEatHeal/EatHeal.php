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

namespace KygekTeam\KygekEatHeal;

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\api\legacy\ClosureContext;
use cooldogedev\BedrockEconomy\api\version\LegacyBEAPI;
use cooldogedev\BedrockEconomy\BedrockEconomy;
use KygekTeam\KygekEatHeal\commands\EatCommand;
use KygekTeam\KygekEatHeal\commands\HealCommand;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use thebigcrafter\Hydrogen\HConfig;
use thebigcrafter\Hydrogen\Hydrogen;

use function class_exists;

class EatHeal extends PluginBase {

	public const INFO = TF::GREEN;
	public const WARNING = TF::RED;

	public const TRANSACTION_ERROR_CAUSE_NO_ACCOUNT = "transactionErrorCauseNoAccount";
	public const TRANSACTION_ERROR_CAUSE_FULL = "transactionErrorCauseFull";
	public const TRANSACTION_ERROR_CAUSE_INSUFFICIENT_BALANCE = "transactionErrorCauseInsufficientBalance";
	public const TRANSACTION_ERROR_CAUSE_NOT_UPDATED = "transactionErrorCauseNotUpdated";

	public static string $prefix = TF::YELLOW . "[KygekEatHeal] " . TF::RESET;

	public bool $economyEnabled = false;
	private ?LegacyBEAPI $economyAPI;

	protected function onEnable() : void {
		$this->saveDefaultConfig();

		if (HConfig::verifyConfigVersion($this->getConfig(), "2.1")) {
			HConfig::resetConfig($this);
		}
		Hydrogen::checkForUpdates($this);

		if (!class_exists(BedrockEconomy::class)) {
			$this->economyAPI = null;
		} else {
			$this->economyEnabled = true;
			$this->economyAPI = BedrockEconomyAPI::legacy();
		}

		$this->getServer()->getCommandMap()->registerAll("KygekEatHeal", [
			new EatCommand("eat", $this), new HealCommand("heal", $this)
		]);
		self::$prefix = TF::colorize($this->getConfig()->get("message-prefix", "&e[KygekEatHeal] ")) . TF::RESET;
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

	public function eatTransaction(Player $player, bool $isPlayer, ?Player $senderPlayer, \Closure $callback) : void {
		if ($player->getHungerManager()->getFood() >= 20.0) {
			$callback(self::TRANSACTION_ERROR_CAUSE_FULL);
			return;
		}

		$price = (int) $this->getConfig()->getNested("price.eat", 0);
		$feedPlayer = function () use ($callback, $player, $price) {
			$eatValue = $this->getEatValue($player);
			$player->getHungerManager()->addFood($eatValue);
			if ($this->getConfig()->getNested("restore-saturation", true)) {
				$player->getHungerManager()->addSaturation(20);
			}
			$callback($price);
		};
		if ($this->economyEnabled && $isPlayer && $price > 0) {
			$name = $senderPlayer !== null ? $senderPlayer->getName() : $player->getName();
			$this->processTransaction($name, $price,
				function (?string $result) use ($callback, $feedPlayer) {
					if ($result !== null) {
						$callback($result);
						return;
					}

					$feedPlayer();
				}
			);
		}

		$feedPlayer();
	}

	public function healTransaction(Player $player, bool $isPlayer, ?Player $senderPlayer, \Closure $callback) : void {
		if ($player->getHealth() >= 20.0) {
			$callback(self::TRANSACTION_ERROR_CAUSE_FULL);
			return;
		}

		$price = (int) $this->getConfig()->getNested("price.heal", 0);
		$healPlayer = function () use ($callback, $player, $price) {
			$healValue = $this->getHealValue($player);
			$player->setHealth($healValue);
			$callback($price);
		};
		if ($this->economyEnabled && $isPlayer && $price > 0) {
			$name = $senderPlayer !== null ? $senderPlayer->getName() : $player->getName();
			$this->processTransaction($name, $price,
				function (?string $result) use ($callback, $healPlayer) {
					if ($result !== null) {
						$callback($result);
						return;
					}

					$healPlayer();
				}
			);
		}

		$healPlayer();
	}

	private function processTransaction(string $name, int $price, \Closure $callback) : void {
		$this->economyAPI->getPlayerBalance($name, ClosureContext::create(
			function (?int $balance) use ($callback, $name, $price) {
				if ($balance === null) {
					$callback(self::TRANSACTION_ERROR_CAUSE_NO_ACCOUNT);
					return;
				}
				if ($balance < $price) {
					$callback(self::TRANSACTION_ERROR_CAUSE_INSUFFICIENT_BALANCE);
					return;
				}

				$this->economyAPI->subtractFromPlayerBalance($name, $price, ClosureContext::create(
					function (bool $updated) use ($callback) {
						if (!$updated) {
							$callback(self::TRANSACTION_ERROR_CAUSE_NOT_UPDATED);
							return;
						}

						$callback(null);
					}
				));
			}
		));
	}

	public function reloadConfig() : void {
		parent::reloadConfig();
		self::$prefix = TF::colorize($this->getConfig()->get("message-prefix", "&e[KygekEatHeal] ")) . TF::RESET;
	}

}
