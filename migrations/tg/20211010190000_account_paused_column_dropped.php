<?php

use Phinx\Migration\AbstractMigration;

class AccountPausedColumnDropped extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            <<<qqqq
alter table bot_user
    drop column account_paused;
qqqq
        );
    }
}
