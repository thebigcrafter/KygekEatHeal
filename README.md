<h1 align="center">KygekEatHeal</h1>

<p align="center">

<img src="https://poggit.pmmp.io/shield.dl.total/KygekEatHeal?style=for-the-badge" />
<img src="https://img.shields.io/github/license/thebigcrafter/KygekEatHeal?style=for-the-badge" />
<img src="https://img.shields.io/discord/970294579372912700?color=7289DA&label=discord&logo=discord&style=for-the-badge" />

</p>

# 📖 About

A PocketMine-MP plugin that can heal and feed yourself or other players. [BedrockEconomy](https://github.com/cooldogedev/BedrockEconomy) is supported for paid heals and feeds.

**⚠️ NOTE:** EconomyAPI has been deprecated and perceived as obsolete because of performance reasons. BedrockEconomy is currently the preferred replacement of EconomyAPI. Therefore, starting from version 2.0.0, KygekEatHeal has started using BedrockEconomy, while also dropping EconomyAPI support. If you are interested to migrate to BedrockEconomy, you can use [its database converter](https://github.com/cooldogedev/EconAPIToBE) plugin to get started.

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

# 🚢 Other Versions

- [Nukkit](https://github.com/KygekTeam/KygekEatHeal-Nukkit)

# ⚖️ License

Licensed under the [GNU General Public License v3.0](https://github.com/thebigcrafter/KygekEatHeal/blob/pm4/LICENSE) license.
