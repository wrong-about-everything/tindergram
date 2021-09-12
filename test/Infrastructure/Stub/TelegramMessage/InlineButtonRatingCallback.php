<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Stub\TelegramMessage;

use TG\Domain\InternalApi\RateCallbackData\RateCallbackData;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class InlineButtonRatingCallback
{
    private $telegramUserId;
    private $callbackData;

    public function __construct(InternalTelegramUserId $telegramUserId, RateCallbackData $callbackData)
    {
        $this->telegramUserId = $telegramUserId;
        $this->callbackData = $callbackData;
    }

    public function value(): string
    {
        return
            sprintf(
                <<<json
{
   "update_id":507792387,
   "callback_query":{
      "id":"1053094301511232481",
      "from":{
         "id":%s,
         "is_bot":false,
         "first_name":"Vadim",
         "last_name":"Samokhin",
         "username":"samokhin_vadim",
         "language_code":"ru"
      },
      "message":{
         "message_id":59,
         "from":{
            "id":1936091479,
            "is_bot":true,
            "first_name":"Flurr",
            "username":"flurr_bot"
         },
         "chat":{
            "id":%s,
            "first_name":"Vadim",
            "last_name":"Samokhin",
            "username":"samokhin_vadim",
            "type":"private"
         },
         "date":1631091052,
         "text":"Kamilla",
         "reply_markup":{
            "inline_keyboard":[
               [
                  {
                     "text":"\ud83d\udc4e",
                     "callback_data":"{\"action\":1,\"pair_telegram_id\":221964428}"
                  },
                  {
                     "text":"\ud83d\udc4d",
                     "callback_data":"{\"action\":0,\"pair_telegram_id\":221964428}"
                  }
               ]
            ]
         }
      },
      "chat_instance":"74786330725176259",
      "data":"%s"
   }
}
json
                ,
                $this->telegramUserId->value(),
                $this->telegramUserId->value(),
                addslashes(json_encode($this->callbackData->value()))
            );
    }
}