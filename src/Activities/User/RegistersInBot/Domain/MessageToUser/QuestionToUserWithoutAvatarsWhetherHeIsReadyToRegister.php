<?php

declare(strict_types=1);

namespace TG\Activities\User\RegistersInBot\Domain\MessageToUser;

use TG\Infrastructure\TelegramBot\MessageToUser\MessageToUser;

class QuestionToUserWithoutAvatarsWhetherHeIsReadyToRegister implements MessageToUser
{
    public function value(): string
    {
        return
        <<<qqq
Бот всегда показывает первые пять ваших аватарок из telegram. Сам он эти фото не хранит. Поэтому, если вы удалите какую-то аватарку в самом telegram, бот перестанет её видеть и не сможет никому показать. А если загрузите новую, её сразу же начнут видеть другие пользователи.

У вас в профиле telegram пока нет ни одного фото. Можете пока зарегистрироваться, а как будете готовы -- просто загрузите аватарку в telegram.

Если вас что-то беспокоит, вы всегда можете задать любые вопросы в @flurr_support_bot.

Ну что, поехали?
qqq
            ;
    }

    public function isNonEmpty(): bool
    {
        return true;
    }
}