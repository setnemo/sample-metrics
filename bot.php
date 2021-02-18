<?php

use SampleMetrics\Core\App;
use SampleMetrics\Core\Bot;
use SampleMetrics\Core\Log;
use SampleMetrics\Core\Metrics;

require __DIR__ . '/vendor/autoload.php';

$app = App::getInstance()->init();
$config = $app->getConfig();
$logger = Log::getInstance()->init($config)->getLogger();
$bot = Bot::getInstance();
$bot->init($config, $logger);
$metric = Metrics::getInstance()->init($config);
while (true) {
    $bot->run();
    $metric->increaseMetric('worker');
}

