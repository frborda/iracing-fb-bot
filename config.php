<?php
    date_default_timezone_set("America/Argentina/Buenos_Aires");
    define('LOGIN_URL', 'https://members-ng.iracing.com/auth');
    define('COOKIE_FILE', __DIR__ . '/cache/cookies.txt');
    define('IRATING_REFRESH_TIME', 600);
    define('DB_FILE',__DIR__.'/data/iracing.db');
    define('JSON_CARS',__DIR__.'/data/cars.json');
    include_once __DIR__.'/env.php';

