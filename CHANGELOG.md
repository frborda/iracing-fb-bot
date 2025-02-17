# Changelog

## v0.1.1 - AI & Charts

### 🚀 New Features

- Added bar chart displaying iRating by category.
- Added AI that reacts to race results on Discord.

### 🛠 New commands
- `!irating <category> [<smurf>]` - Now supports an optional `smurf` parameter. If specified, accounts tagged as smurf will be included in the table and charts.
- `!user add <id> <user_discord_id> [<smurf>]` - Added `user_discord_id` parameter to mention users in race results. `smurf` remains optional.
- `!user updateall` was renamed to `!admin update`.


## v0.1.0 - Initial Release

### 🚀 Features
- Retrieves real-time iRacing statistics.
- Displays iRating for different racing categories.
- Supports adding, updating, and deleting drivers from the database.
- Provides an admin-only command set for user management.
- Periodically updates all tracked users' statistics.
- Shows recent race history for tracked drivers.

### 🛠 Commands
- `!irating <category>` - Fetches iRating for a specific category (`oval`, `sports_car`, `formula_car`, `dirt_oval`, `dirt_road`).
- `!user add <id>` - Adds a user to the database.
- `!user delete <id>` - Removes a user from the database.
- `!user update <id>` - Updates statistics for a specific user.
- `!user list` - Lists all registered users.
- `!user updateall` - Updates statistics for all users.
- `!commands` - Shows available commands.

### 🔧 Setup
- Configuration via `env.php`.
- Authentication with iRacing API.
- Database storage for tracking users.

### 🛠 Future Enhancements
- Add support for additional racing statistics.