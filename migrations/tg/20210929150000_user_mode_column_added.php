<?php

use Phinx\Migration\AbstractMigration;
use TG\Domain\UserMode\Pure\Visible;

class UserModeColumnAdded extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            <<<qqqq
alter table bot_user
    add column user_mode smallint;
qqqq
        );
        $this->execute(
            sprintf(
                'update bot_user set user_mode = %s',
                (new Visible())->value()
            )
        );
    }
}
