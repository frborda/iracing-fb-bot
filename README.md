# iRacing FB Bot

**A Discord bot to track iRacing drivers and their statistics**

## 📌 Features
- Retrieves real-time statistics from iRacing.
- Displays iRating and other key metrics in a designated Discord channel.
- Allows administrators to manage tracked drivers.

## 📄 Changelog
See the [CHANGELOG.md](./CHANGELOG.md) file for a detailed changelog.

## 📥 Installation & Usage

### 1️⃣ Initial Setup
1. Rename the file `env_base.php` to `env.php`.
2. Open `env.php` and configure the following variables:
   - **`DISCORD_TOKEN`** → Your Discord bot token.
   - **`IRACING_USER`** → Your iRacing username.
   - **`IRACING_PASSWORD`** → Your iRacing password.
   - **`DISCORD_CHANNEL`** → ID of the channel where the bot will respond with statistics.
   - **`DISCORD_CHANNEL_ADMIN`** → ID of the channel for managing drivers.

### 2️⃣ Install Dependencies
Ensure you have [Composer](https://getcomposer.org/) installed, then run:

```sh
composer install
```

### 3️⃣ Connect the Bot to Discord
To start the bot, execute:

```sh
php main.php
```

To run the bot in the background:

```sh
nohup php main.php > bot.log 2>&1 &
```

## 🛠 Available Commands
Type `!commands` in the `DISCORD_CHANNEL` to see the list of available commands.

### 📌 General Commands
| Command            | Description |
|-------------------|-------------|
| `!irating <category>` | Displays the iRating for all users in the specified category (`oval`, `sports_car`, `formula_car`, `dirt_oval`, `dirt_road`). |
| `!user list`      | Shows the list of registered users. |

### ⚙️ Admin Commands
| Command                | Description |
|------------------------|-------------|
| `!user add <id>`      | Adds a user to the database. |
| `!user delete <id>`   | Removes a user from the database. |
| `!user update <id>`   | Updates data for a specific user. |
| `!user updateall` | Updates data for all registered users. |

## 🔧 Maintenance & Updates
To update the bot to the latest version:
```sh
git pull
composer dump-autoload
php main.php
```

If making code changes, ensure you update the autoload configuration:
```sh
composer dump-autoload
```

## 📬 Support & Contact
For issues or suggestions, open an issue in the repository.
# iracing-fb-bot
