<?php

declare(strict_types=1);

namespace cooldogedev\BedrockEconomy;

use cooldogedev\BedrockEconomy\currency\CurrencyManager;
use pocketmine\plugin\PluginBase;

final class BedrockEconomy extends PluginBase {
	public static function getInstance() : self {}

	public function getCurrencyManager() : CurrencyManager {}
}

namespace cooldogedev\BedrockEconomy\api;

use cooldogedev\BedrockEconomy\api\version\LegacyBEAPI;

final class BedrockEconomyAPI {
	public static function legacy() : LegacyBEAPI {}
}

namespace cooldogedev\BedrockEconomy\api\legacy;

use Closure;

final class ClosureContext {
	public static function create(Closure ...$closures) : self {}
}

namespace cooldogedev\BedrockEconomy\api\version;

use cooldogedev\BedrockEconomy\api\legacy\ClosureContext;

final class LegacyBEAPI {
	public function getPlayerBalance(string $username, ?ClosureContext $context = null) : void {}

	public function subtractFromPlayerBalance(string $username, int $subtraction, ?ClosureContext $context = null, ?string $issuer = null) : void {}
}

namespace cooldogedev\BedrockEconomy\currency;

final class CurrencyManager {
	public function getSymbol() : string {}
}
