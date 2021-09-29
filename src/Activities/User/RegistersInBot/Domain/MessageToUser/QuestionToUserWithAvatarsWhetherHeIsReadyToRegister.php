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

Если вы не хотите, чтобы ваш профиль кто-то увидел, но хотите посмотреть другие, вы можете зарегистрироваться в режиме невидимки. Но будьте внимательны: как только вы кого-то лайкнете, ваш профиль станет видимым для всех пользователей. Чтобы снова стать невидимкой, напишите в @flurr_support_bot.

Если вас что-то беспокоит или у вас возникла проблема, вы всегда можете задать любые вопросы в @flurr_support_bot.
qqq
            ;
    }

    public function isNonEmpty(): bool
    {
        return true;
    }
}