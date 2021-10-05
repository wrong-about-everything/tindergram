<?php

use Phinx\Migration\AbstractMigration;

class AccountPausedColumnAdded extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            <<<qqqq
alter table bot_user
    add column account_paused bool default false;
qqqq
        );
    }
}
