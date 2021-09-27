<?php

use Phinx\Migration\AbstractMigration;

class HasAvatarColumnAdded extends AbstractMigration
{
    public function change()
    {
        $this->execute('alter table bot_user add column has_avatar bool');
    }
}
