<?php

require __DIR__ . '/vendor/autoload.php';

use Prometheus\RenderTextFormat;
use SampleMetrics\Core\App;
use SampleMetrics\Core\Metrics;

if (isset($_REQUEST['uri']) && $_REQUEST['uri'] == '/metrics') {
    $app = App::getInstance()->init();
    $config = $app->getConfig();
    $metrics = Metrics::getInstance()->init($config);
    $renderer = new RenderTextFormat();
    $result = $renderer->render($metrics->getRegistry()->getMetricFamilySamples());
    header('Content-type: ' . RenderTextFormat::MIME_TYPE);
    echo $result;
} else {
    echo json_encode(
        ["silence" => "gold"]
    );
}


