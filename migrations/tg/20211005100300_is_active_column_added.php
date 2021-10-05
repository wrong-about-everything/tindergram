<?php

use Phinx\Migration\AbstractMigration;

class IsActiveColumnAdded extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            <<<qqqq
alter table bot_user
    add column is_active bool default true;
qqqq
        );
    }
}
