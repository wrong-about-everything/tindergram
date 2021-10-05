<?php

declare(strict_types=1);

namespace TG\Tests\Infrastructure\Stub\TelegramMessage;

use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class UserBannedBotMessage
{
    private $userId;

    public function __construct(InternalTelegramUserId $userId)
    {
        $this->userId = $userId;
    }

    public function value(): array
    {
        return
            json_decode(
                sprintf(
                    <<<q
{
   "update_id":507792865,
   "my_chat_member":{
      "chat":{
         "id":%d,
         "first_name":"\u0412\u0430\u0441\u044e\u0448\u0430\u2764\ufe0f",
         "username":"ILY10101",
         "type":"private"
      },
      "from":{
         "id":%d,
         "is_bot":false,
         "first_name":"\u0412\u0430\u0441\u044e\u0448\u0430\u2764\ufe0f",
         "username":"ILY10101",
         "language_code":"ru"
      },
      "date":1631709534,
      "old_chat_member":{
         "user":{
            "id":1936091479,
            "is_bot":true,
            "first_name":"Flurr",
            "username":"flurr_bot"
         },
         "status":"member"
      },
      "new_chat_member":{
         "user":{
            "id":1936091479,
            "is_bot":true,
            "first_name":"Flurr",
            "username":"flurr_bot"
         },
         "status":"kicked",
         "until_date":0
      }
   }
}
q
                    ,
                    $this->userId->value(),
                    $this->userId->value()
                ),
                true
            );
    }
}