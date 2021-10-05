<?php

use Phinx\Migration\AbstractMigration;

class IsActiveColumnDropped extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            <<<qqqq
alter table bot_user
    drop column is_active;
qqqq
        );
    }
}
