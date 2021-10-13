<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\Domain\MessageToUser;

use TG\Domain\ABTesting\Pure\SwitchToVisibleModeOnUpvote;
use TG\Infrastructure\ABTesting\Pure\VariantId;
use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;

class VariantDependentQuestionToUserWithAvatarsWhetherHeIsReadyToRegister implements MessageToUser
{
    private $variantId;

    public function __construct(VariantId $variantId)
    {
        $this->variantId = $variantId;
    }

    public function value(): string
    {
        return
            $this->variantId->equals(new SwitchToVisibleModeOnUpvote())
                ? $this->switchToVisibleModeOnUpvote()
                : $this->switchToVisibleModeOnRequest()
            ;
    }

    public function isNonEmpty(): bool
    {
        return true;
    }

    private function switchToVisibleModeOnUpvote()
    {
        return
            <<<qqq
Вот фотографии, которые увидят другие пользователи.
Бот всегда показывает первые пять ваших аватарок из telegram. Сам он эти фото не хранит. Поэтому, если вы удалите какую-то аватарку в самом telegram, бот перестанет её видеть и не сможет никому показать. А если загрузите новую, её будут видеть другие пользователи.

Если вас что-то беспокоит или у вас возникла проблема, в @flurr_support_bot вы можете задать любые вопросы.
qqq
            ;
    }

    private function switchToVisibleModeOnRequest()
    {
        return
            <<<qqq
Вот фотографии, которые увидят другие пользователи.
Бот всегда показывает первые пять ваших аватарок из telegram. Сам он эти фото не хранит. Поэтому, если вы удалите какую-то аватарку в самом telegram, бот перестанет её видеть и не сможет никому показать. А если загрузите новую, её будут видеть другие пользователи.

Если вас что-то беспокоит или у вас возникла проблема, в @flurr_support_bot вы можете задать любые вопросы.
qqq
            ;
    }
}