<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Impure;

use TG\Domain\BotUser\ReadModel\ByInternalTelegramUserId;
use TG\Domain\RegistrationQuestion\Single\Pure\RegistrationQuestion as PureRegistrationQuestion;
use TG\Domain\RegistrationQuestion\Multiple\Impure\Unanswered;
use TG\Domain\RegistrationQuestion\Multiple\Pure\All;
use TG\Domain\RegistrationQuestion\Multiple\Impure\Ordered;
use TG\Infrastructure\ImpureInteractions\ImpureValue;
use TG\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use TG\Infrastructure\TelegramBot\InternalTelegramUserId\Pure\InternalTelegramUserId;

class NextRegistrationQuestion implements RegistrationQuestion
{
    private $telegramUserId;
    private $connection;
    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->connection = $connection;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        return
            (new First(
                new Ordered(
                    new Unanswered(
                        new All(),
                        new ByInternalTelegramUserId($this->telegramUserId, $this->connection)
                    ),
                    function (PureRegistrationQuestion $left, PureRegistrationQuestion $right) {
                        return $left->ordinalNumber() > $right->ordinalNumber();
                    }
                )
            ))
                ->value();
    }
}