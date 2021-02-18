<?php

declare(strict_types=1);

namespace SampleMetrics\Core;

use SampleMetrics\Common\Config;
use SampleMetrics\Common\Singleton;
use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Storage\Redis;

class Metrics extends Singleton
{
    private const METRIC_USAGE_PREFIX = 'usage';

    private const METRIC_ITEM_PREFIX = 'item';

    private const METRIC_CART_PREFIX = 'cart';

    /**
     * @var CollectorRegistry
     */
    private CollectorRegistry $registry;

    public function init(Config $config): self
    {
        Redis::setDefaultOptions(
            [
                'host' => $config->getKey('redis.host'),
                'port' => intval($config->getKey('redis.port')),
                'database' => intval($config->getKey('redis.database')),
                'password' => null,
                'timeout' => 0.1, // in seconds
                'read_timeout' => '10', // in seconds
                'persistent_connections' => false
            ]
        );
        $this->registry = CollectorRegistry::getDefault();

        return $this;
    }

    /**
     * @return CollectorRegistry
     */
    public function getRegistry(): CollectorRegistry
    {
        return $this->registry;
    }

    /**
     * @param string $metricName
     * @param array  $labels
     *
     * @throws MetricsRegistrationException
     */
    public function increaseMetric(string $metricName, array $labels = []): void
    {
        $counter = $this->registry->getOrRegisterCounter('sample_metrics_bot', $metricName, 'it increases', []);
        $counter->incBy(1, $labels);
    }

    /**
     * @param string $metricName
     * @param array  $labels
     *
     * @throws MetricsRegistrationException
     */
    public function increaseMetricItem(string $metricName, array $labels = []): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'sample_metrics_bot',
            $metricName,
            'it increases',
            [
                'productId'
            ]
        );
        $counter->incBy(1, $labels);
    }

    /**
     * @param string $metricName
     * @param array  $labels
     *
     * @throws MetricsRegistrationException
     */
    public function increaseMetricCart(string $metricName, array $labels = []): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'sample_metrics_bot',
            $metricName,
            'it increases',
            [
                'quantity'
            ]
        );
        $counter->incBy(1, $labels);
    }

    /**
     * @throws MetricsRegistrationException
     */
    public function increaseUsage(): void
    {
        $this->increaseMetric(self::METRIC_USAGE_PREFIX);
    }

    /**
     * @param array $labels
     *
     * @throws MetricsRegistrationException
     */
    public function increaseItemMetric(array $labels = []): void
    {
        $this->increaseMetricItem(self::METRIC_ITEM_PREFIX, $labels);
    }

    /**
     * @param array $labels
     *
     * @throws MetricsRegistrationException
     */
    public function increaseCartMetric(array $labels = []): void
    {
        $this->increaseMetricCart(self::METRIC_CART_PREFIX, $labels);
    }
}
