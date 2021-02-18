<?php

declare(strict_types=1);

namespace SampleMetrics\Bot;

class BotHelper
{
    /**
     * @param int  $itemId
     * @param int  $price
     * @param bool $exist
     *
     * @return array
     */
    public static function getPagination(int $itemId, int $price, bool $exist): array
    {
        $result[] = BotHelper::getPaginationFw($itemId);
        $addRemove = $exist ?
            [
                'text' => "🚫 Удалить",
                'callback_data' => 'item_del_' . $itemId,
            ] :
            [
                'text' => "✅ Добавить в корзину",
                'callback_data' => 'item_add_' . $itemId,
            ];
        $result[] = [
            $addRemove,
        ];
        $result[] = [
            [
                'text' => "💲 " . $price / 100,
                'callback_data' => 'empty',
            ]
        ];
        $result[] = [
            [
                'text' => "🛒 Оформить заказ",
                'callback_data' => 'buy_' . $itemId,
            ]
        ];

        return $result;
    }

    /**
     * @param int $num
     *
     * @return \string[][]
     */
    private static function getPaginationFw(int $num): array
    {
        return [
            [
                'text' => $num > 1 ? '   ⏮   ' : '        ',
                'callback_data' => $num > 2 ? 'product_' . 1 : 'empty',
            ],
            [
                'text' => $num > 1 ? '   ⏪   ' : '        ',
                'callback_data' => $num > 1 ? 'product_' . ($num - 1) : 'empty',
            ],
            [
                'text' => BotHelper::createEmojiNumber($num),
                'callback_data' => 'product_' . $num,
            ],
            [
                'text' => $num < 10 ? '   ⏩   ' : '        ',
                'callback_data' => $num < 10 ? 'product_' . ($num + 1) : 'empty',
            ],
            [
                'text' => $num < 10 ? '   ⏭   ' : '        ',
                'callback_data' => $num < 10 ? 'product_' . 10 : 'empty',
            ],
        ];
    }

    /**
     * @param int    $num
     * @param string $text
     *
     * @return string
     */
    private static function createEmojiNumber(int $num, string $text = ''): string
    {
        $tmp = $num;
        if ($tmp >= 10) {
            $text .= BotHelper::createEmojiNumber(intval($tmp / 10));
            $text .= BotHelper::createEmojiNumber(intval($tmp % 10));
        }
        if ($tmp < 10) {
            $text .= match($tmp) {
                0 => '0️⃣',
                1 => '1️⃣',
                2 => '2️⃣',
                3 => '3️⃣',
                4 => '4️⃣',
                5 => '5️⃣',
                6 => '6️⃣',
                7 => '7️⃣',
                8 => '8️⃣',
                9 => '9️⃣',
            };
        }

        return $text;
    }
}
