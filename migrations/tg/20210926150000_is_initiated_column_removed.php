<?php

use Phinx\Migration\AbstractMigration;

class IsInitiatedColumnRemoved extends AbstractMigration
{
    public function change()
    {
        $this->execute('alter table bot_user drop column is_initiated');
    }
}
