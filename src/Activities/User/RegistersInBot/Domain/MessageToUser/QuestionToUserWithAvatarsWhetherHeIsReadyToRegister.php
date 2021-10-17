<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\Domain\MessageToUser;

use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;

class QuestionToUserWithAvatarsWhetherHeIsReadyToRegister implements MessageToUser
{
    public function value(): string
    {
        return
            <<<qqq
Вот фотографии, которые увидят другие пользователи.
Бот всегда показывает первые пять ваших аватарок из telegram. Сам он эти фото не хранит. Поэтому, если вы удалите какую-то аватарку в самом telegram, бот перестанет её видеть и не сможет никому показать. А если загрузите новую, её будут видеть другие пользователи.

Если вас что-то беспокоит или у вас возникла проблема, в @flurr_support_bot вы можете задать любые вопросы.
qqq
            ;
    }

    public function isNonEmpty(): bool
    {
        return true;
    }
}