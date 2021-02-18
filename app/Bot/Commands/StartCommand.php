<?php

declare(strict_types=1);

namespace Longman\TelegramBot\Commands\SystemCommand;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use SampleMetrics\Bot\BotHelper;
use SampleMetrics\Core\App;
use SampleMetrics\Core\Cache;
use SampleMetrics\Core\Database\Database;
use SampleMetrics\Core\Database\Repository\ProductRepository;
use SampleMetrics\Core\Metrics;

/**
 * Class StartCommand
 * @package Longman\TelegramBot\Commands\SystemCommand
 */
class StartCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'start';
    /**
     * @var string
     */
    protected $description = 'Start command';
    /**
     * @var string
     */
    protected $usage = '/start';
    /**
     * @var string
     */
    protected $version = '1.0.0';
    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $config   = App::getInstance()->getConfig();
        $metric   = Metrics::getInstance()->init($config);
        $database = Database::getInstance()->getConnection();
        $cache    = Cache::getInstance()->init($config);

        $metric->increaseUsage();

        $productId = 1;
        $chat_id = $this->getMessage()->getChat()->getId();
        $productRepository = new ProductRepository($database);
        $product = $productRepository->getProduct($productId);
        $keyboard = new InlineKeyboard(
            ...BotHelper::getPagination(
                $product->getId(),
                $product->getPrice(),
                in_array($productId, $cache->getCarts($chat_id))
            )
        );        $data = [
            'chat_id' => $chat_id,
            'photo' => $product->getImage(),
            'caption' => $product->getDescription(),
            'parse_mode' => 'markdown',
            'disable_web_page_preview' => true,
            'reply_markup' => $keyboard,
            'disable_notification' => 1,
        ];
        return Request::sendPhoto($data);
    }
}
