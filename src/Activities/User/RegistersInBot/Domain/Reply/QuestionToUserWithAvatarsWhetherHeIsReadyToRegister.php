<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\Domain\Reply;

use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;

class QuestionToUserWithAvatarsWhetherHeIsReadyToRegister implements MessageToUser
{
    public function value(): string
    {
        return
            <<<qqq
Вот фотографии, которые увидят другие пользователи.
Бот всегда показывает первые пять ваших аватарок из telegram. Сам он эти фото не хранит. Поэтому, если вы удалите какую-то аватарку в самом telegram, бот перестанет её видеть и не сможет никому показать. А если загрузите новую аватарку, её сразу начнут видеть другие пользователи.

Если вас что-то беспокоит, вы всегда можете задать любые вопросы в @flurr_support_bot.
qqq
            ;
    }
}