<?php
    require __DIR__.'/vendor/autoload.php';
    require __DIR__.'/config.php';

    use Src\Helper;
    use Src\Charts;
    use Src\IracingAPI;
    use Src\DiscordBot;
    use Src\Database;
    use Src\OpenAIClient;

    $charts = new Charts('roboto');
    $db = new Database(DB_FILE);
    $openAI = new OpenAIClient(OPENAI_API_KEY, OPENAI_ASSISTANT_ID, OPENAI_MODEL, OPENAI_ASSISTANT_CONTEXT);
    $iracing = new IracingAPI(IRACING_USERNAME, IRACING_PASSWORD, LOGIN_URL, $db);
    $bot = new DiscordBot(DISCORD_TOKEN, DISCORD_CHANNEL, $iracing, $db, $charts, $openAI);

    return $bot;