<?php
    require __DIR__.'/vendor/autoload.php';
    require __DIR__.'/config.php';

    use Src\IracingAPI;
    use Src\DiscordBot;
    use Src\Database;

    $db = new Database(DB_FILE);
    $iracing = new IracingAPI(IRACING_USERNAME, IRACING_PASSWORD, LOGIN_URL, $db);
    $bot = new DiscordBot(DISCORD_TOKEN, DISCORD_CHANNEL, $iracing, $db);

    return $bot;