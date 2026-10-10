<h1 align="center">KygekEatHeal</h1>

<p align="center">
<img src="https://poggit.pmmp.io/shield.dl.total/KygekEatHeal?style=for-the-badge" alt="poggit" />
<a href="https://github.com/thebigcrafter/KygekEatHeal/blob/main/LICENSE"><img src="https://img.shields.io/github/license/thebigcrafter/KygekEatHeal?style=for-the-badge" alt="license" /></a>
<a href="https://discord.gg/PykBfE2TZ9"><img src="https://img.shields.io/discord/1087729577004122112?color=7289DA&label=discord&logo=discord&style=for-the-badge" alt="discord" /></a>
</p>

# 📖 About

A PocketMine-MP API 5 plugin that can heal and feed yourself or other players. [BedrockEconomy](https://github.com/cooldogedev/BedrockEconomy) is supported for paid heals and feeds.

**⚠️ NOTE:** EconomyAPI has been deprecated and perceived as obsolete because of performance reasons. BedrockEconomy is currently the preferred replacement of EconomyAPI. Therefore, starting from version 2.0.0, KygekEatHeal has started using BedrockEconomy, while also dropping EconomyAPI support. If you are interested to migrate to BedrockEconomy, you can use [its database converter](https://github.com/cooldogedev/EconAPIToBE) plugin to get started.

# ✅ Requirements

- PocketMine-MP API 5.0.0 or a compatible API 5 release
- PHP 8.1 or newer
- [BedrockEconomy](https://github.com/cooldogedev/BedrockEconomy) is optional and is required only for paid actions

# 🧩 Features

- [BedrockEconomy](https://github.com/cooldogedev/BedrockEconomy) support for paid heals and feeds
- Customizable heal or feed amount (points)
- Heal or feed another player
- Offline player detection
- Full or healthy detection (so you won't waste money!)
- Customizable heal and feed cooldown
- Option to restore saturation
- Custom prefix for messages and logs
- Command description can be changed
- Supports command aliases
- Automatic plugin updates checker
- Automatic configuration files reloading
- Automatic configuration file updater

# ⬇️ Installation

1. Download the latest version (It is recommended to always download the latest version for the best experience, except you're having compatibility issues).
2. Place the `KygekEatHeal.phar` file into the plugins folder.
3. Restart or start your server.
4. Done!

# 📜 Commands & Permissions

| Command | Default Description | Permission | Default |
| --- | --- | --- | --- |
| `/eat` | Eat or feed a player | `kygekeatheal.eat` | true |
| `/heal` | Heal yourself or a player | `kygekeatheal.heal` | true |

💡 Tips:
- Permission `kygekeatheal` can be used to allow all commands (Default is `true`).
- Command description can be changed in `config.yml`. You can also add command aliases in `config.yml`.
- Use `-{COMMAND-PERMISSION}` to blacklist the command permissions to groups/users in PurePerms (e.g. `-kygekeatheal.eat`).

# ⚙️ Configuration

The default `config.yml` controls command descriptions and aliases, prices, eat/heal amounts, saturation restoration, cooldowns, message prefix, update checks and configuration migration. Set a price above `0` only when BedrockEconomy is installed and configured for the server. The plugin reloads its message prefix and command configuration when a command is executed.

# 🛠️ Development

Install the locked development dependencies and run the maximum-level analysis:

```sh
composer install
composer analyse
```

The packaging process uses [DevTools](https://github.com/NhanAZ/DevTools) to prepare and validate the plugin PHAR. The checked-in Poggit manifest remains the source of its dependency declaration; build tooling versions are kept in the build environment rather than copied into this user documentation.

# 🚢 Other Versions

- [Nukkit](https://github.com/KygekTeam/KygekEatHeal-Nukkit)

# ⚖️ License

Licensed under the [GNU General Public License v3.0](https://github.com/thebigcrafter/KygekEatHeal/blob/main/LICENSE) license.
