<?php

use Phinx\Migration\AbstractMigration;

class BotUserAvatarCheckTableAdded extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            <<<qqqq
create table bot_user_avatar_check (
    telegram_id bigint,
    date date,

    primary key (telegram_id)
);

grant select, insert, update on bot_user_avatar_check to tg;

qqqq
        );
    }
}
