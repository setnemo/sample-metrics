<?php

declare(strict_types=1);

namespace Longman\TelegramBot\Commands\SystemCommand;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InputMedia\InputMediaPhoto;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use SampleMetrics\Bot\BotHelper;
use SampleMetrics\Core\App;
use SampleMetrics\Core\Cache;
use SampleMetrics\Core\Database\Database;
use SampleMetrics\Core\Database\Repository\ProductRepository;
use SampleMetrics\Core\Metrics;

/**
 * Class CallbackqueryCommand
 * @package Longman\TelegramBot\Commands\SystemCommand
 */
class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'callbackquery';

    /**
     * @var string
     */
    protected $description = 'Reply to callback query';

    /**
     * @var string
     */
    protected $version = '2.0.0';

    /**
     * @return ServerResponse
     */
    public function execute(): ServerResponse
    {

        $callback_query     = $this->getCallbackQuery();
        $callback_query_id  = $callback_query->getId();
        $callback_data      = $callback_query->getData();
        $array              = explode('_', $callback_data);
        $chat_id            = $callback_query->getMessage()->getChat()->getId();
        $message_id         = $callback_query->getMessage()->getMessageId();

        if ($array[0] === 'empty') {
            return Request::answerCallbackQuery([
                'callback_query_id' => $callback_query_id,
                'text'              => '',
                'show_alert'        => false,
            ]);
        }

        $config             = App::getInstance()->getConfig();
        $database           = Database::getInstance()->getConnection();
        $metric             = Metrics::getInstance()->init($config);
        $cache              = Cache::getInstance()->init($config);
        $productRepository  = new ProductRepository($database);

        $metric->increaseUsage();

        if ($array[0] === 'buy') {
            $productId = intval($array[1]);
            if ($cache->haveCart($chat_id)) {
                $items = $cache->getCarts($chat_id);
                foreach ($items as $item) {
                    $metric->increaseItemMetric(['productId' => $item]);
                }
                $metric->increaseCartMetric(['quantity' => count($items)]);
                $cache->forgetCarts($chat_id);
                $text = "Заказ оформлен";
            } else {
                $text = "Корзина пуста";
            }
            $data = [
                'chat_id' => $chat_id,
                'text' => $text,
                'parse_mode' => 'markdown',
                'disable_web_page_preview' => true,
                'disable_notification' => 1,
            ];
            Request::sendMessage($data);
            $product = $productRepository->getProduct($productId);
            $media = [
                'type' => 'photo',
                'media' => $callback_query->getMessage()->getPhoto()[0]->getFileId(),
                'caption' => $product->getDescription(),
            ];
        }

        if ($array[0] === 'item' && $array[1] === 'add') {
            $productId = intval($array[2]);
            $cache->setCarts($chat_id, $productId);
            $product = $productRepository->getProduct($productId);
            $media = [
                'type' => 'photo',
                'media' => $callback_query->getMessage()->getPhoto()[0]->getFileId(),
                'caption' => $product->getDescription(),
            ];
        }
        if ($array[0] === 'item' && $array[1] === 'del') {
            $productId = intval($array[2]);
            $cache->removeCarts($chat_id, $productId);
            $product = $productRepository->getProduct($productId);
            $media = [
                'type' => 'photo',
                'media' => $callback_query->getMessage()->getPhoto()[0]->getFileId(),
                'caption' => $product->getDescription(),
            ];
        }

        if ($array[0] === 'product') {
            $productId = intval($array[1]);
            $product = $productRepository->getProduct($productId);
            $media = [
                'type' => 'photo',
                'media' => Request::encodeFile($product->getImage()),
                'caption' => $product->getDescription(),
            ];
        }

        $keyboard = new InlineKeyboard(
            ...BotHelper::getPagination(
                $product->getId(),
                $product->getPrice(),
                in_array($productId, $cache->getCarts($chat_id))
            )
        );
        $data = [
            'chat_id' => $chat_id,
            'message_id'   => $message_id,
            'media' => new InputMediaPhoto($media),
            'parse_mode' => 'markdown',
            'disable_web_page_preview' => true,
            'reply_markup' => $keyboard,
            'disable_notification' => 1,
        ];
        return Request::editMessageMedia($data);
    }

    /**
     * @param TrainingRepository     $trainingRepository
     * @param TrainingSaveRepository $trainingSaveRepository
     * @param array                  $words
     * @param int                    $userId
     *
     * @return int
     */
    public function addNewWords(
        TrainingRepository $trainingRepository,
        TrainingSaveRepository $trainingSaveRepository,
        array $words,
        int $userId
    ): int {
        $i = 0;
        $config = App::getInstance()->getConfig();
        $logger = Log::getInstance()->init($config)->getLogger();
        $saves = $trainingSaveRepository->getTrainingSave($userId);
        foreach (BotHelper::getTrainingTypes() as $type) {
            /** @var Word $word */
            foreach ($words as $word) {
                try {
                    $wordId = $word->getId();
                    $collectionId = $word->getCollectionId();
                    $wordW = $word->getWord();
                    $translate = $word->getTranslate();
                    $voice = $word->getVoice();
                    $repeat = null;
                    $status = null;
                    if (isset($saves[$type][$word->getWord()])) {
                        /** @var TrainingSave $save */
                        $save = $saves[$type][$word->getWord()];
                        $repeat = $save->getRepeat();
                        $status = $save->getStatus();
                    }
                    $trainingRepository->createTraining(
                        $wordId,
                        $userId,
                        $collectionId,
                        $type,
                        $wordW,
                        $translate,
                        $voice,
                        $status,
                        $repeat
                    );
                    if (isset($saves[$type][$word->getWord()])) {
                        /** @var TrainingSave $save */
                        $save = $saves[$type][$word->getWord()];
                        $trainingSaveRepository->setUsed($save);
                    }
                    ++$i;
                    if ($i % 1000 == 0) {
                        $this->progressNotify($i / 2);
                        $i = 0;
                    }
                } catch (\Throwable $t) {
                    $logger->error('addNewWords: ' . $t->getMessage(), $t->getTrace());
                }
            }
        }

        return $i;
    }

    /**
     * @param int $count
     *
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    private function progressNotify(int $count): void
    {
        $text = BotHelper::getAnswer('Добавлено ', $count) . '!';
        Request::sendMessage([
            'chat_id' => $this->getCallbackQuery()->getFrom()->getId(),
            'text' => $text,
            'parse_mode' => 'markdown',
            'disable_web_page_preview' => true,
            'disable_notification' => 1,
        ]);
    }
}
