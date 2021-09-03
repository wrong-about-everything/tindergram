<?php

declare(strict_types=1);

namespace TG\Domain\RegistrationQuestion\Single\Pure;

class AreYouReadyToRegister implements RegistrationQuestion
{
    public function value(): string
    {
        return
        <<<qqq
Вот фотографии, которые увидят другие пользователи. Все они берутся из ваших аватарок, бот их не хранит. Поэтому если вы хотите, чтобы какие-то фото никто не увидел, просто удалите их через сам telegram. Сразу после этого, эти фото пропадут из вашего профиля и их никто не увидит.

Если вас что-то беспокоит, вы всегда можете задать любые вопросы в @flurr_support_bot.
qqq
            ;
    }

    public function ordinalNumber(): int
    {
        return 3;
    }

    public function exists(): bool
    {
        return true;
    }
}