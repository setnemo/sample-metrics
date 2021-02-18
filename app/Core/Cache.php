<?php

declare(strict_types=1);

namespace SampleMetrics\Core;

use Predis\Client;
use SampleMetrics\Common\Config;
use SampleMetrics\Common\Singleton;

/**
 * Class Redis
 * @package RepeatBot\Core
 */
class Cache extends Singleton
{
    public const PREFIX = 'sample_metrics_bot_cart_';
    /**
     * @var Client
     */
    private Client $redis;

    /**
     * @param Config $config
     *
     * @return $this
     */
    public function init(Config $config): self
    {
        $this->redis = new Client([
            'host' => $config->getKey('redis.host'),
            'port' => intval($config->getKey('redis.port')),
            'database' => $config->getKey('redis.database'),
        ]);
        return $this;
    }

    /**
     * @return Client
     */
    public function getRedis(): Client
    {
        return $this->redis;
    }

    /**
     * @param string $source
     *
     * @return string
     */
    public function getCacheSlug(string $source): string
    {
        return self::PREFIX . $source;
    }

    /**
     * @param int $userId
     * @param int $value
     */
    public function setCarts(int $userId, int $value): void
    {
        $redis = $this->getRedis();
        $ids[] = $value;
        $slug = $this->getSlugCart($userId);
        if ($redis->exists($slug)) {
            $ids = array_merge($ids, $this->getCarts($userId));
        }
        $redis->set($slug, json_encode($ids));
    }

    /**
     * @param int $userId
     */
    public function forgetCarts(int $userId): void
    {
        $redis = $this->getRedis();
        $slug = $this->getSlugCart($userId);
        $redis->del($slug);
    }

    /**
     * @param int $userId
     *
     * @return bool
     */
    public function haveCart(int $userId): bool
    {
        $redis = $this->getRedis();
        $slug = $this->getSlugCart($userId);

        $exist = $redis->exists($slug);

        return $exist ? count($this->getCarts($userId)) > 0 : false;
    }

    /**
     * @param int    $userId
     *
     * @return int
     */
    public function getCarts(int $userId): array
    {
        return json_decode($this->getRedis()->get($this->getSlugCart($userId)) ?? '{}', true);
    }

    /**
     * @param int $userId
     * @param int $value
     */
    public function removeCarts(int $userId, int $value): void
    {
        $ids = [];
        $redis = $this->getRedis();
        $remove = [$value];
        $slug = $this->getSlugCart($userId);
        if ($redis->exists($slug)) {
            $ids = array_diff($this->getCarts($userId), $remove);
        }
        $redis->set($slug, json_encode($ids));
    }

    /**
     * @param int    $userId
     *
     * @return string
     */
    private function getSlugCart(int $userId): string
    {
        return $this->getCacheSlug("{$userId}");
    }
}
