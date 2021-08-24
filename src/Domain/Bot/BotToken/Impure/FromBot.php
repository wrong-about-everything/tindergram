<?php

declare(strict_types=1);

namespace TG\Domain\Bot\BotToken\Impure;

use TG\Domain\Bot\Bot;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;

class FromBot extends BotToken
{
    private $bot;

    public function __construct(Bot $bot)
    {
        $this->bot = $bot;
    }

    public function value(): ImpureValue
    {
        if (!$this->bot->value()->isSuccessful()) {
            return $this->bot->value();
        }

        return new Successful(new Present($this->bot->value()->pure()->raw()['token']));
    }
}
