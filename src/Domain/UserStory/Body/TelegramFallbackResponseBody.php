<?php

declare(strict_types=1);

namespace TG\Domain\UserStory\Body;

use TG\Infrastructure\ImpureInteractions\PureValue;
use TG\Infrastructure\ImpureInteractions\PureValue\Present;
use TG\Infrastructure\UserStory\Body;

class TelegramFallbackResponseBody extends Body
{
    public function value(): PureValue
    {
        return new Present('Простите, у нас что-то сломалось. Скорее всего, мы об этом уже знаем, но на всякий случай, напишите пожалуйста об этом в @flurr_support_bot.');
    }
}