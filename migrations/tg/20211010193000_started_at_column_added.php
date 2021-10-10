<?php

use Phinx\Migration\AbstractMigration;

class StartedAtColumnAdded extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            <<<qqqq
alter table bot_user
    add column started_at timestamptz default now();
qqqq
        );
    }
}
