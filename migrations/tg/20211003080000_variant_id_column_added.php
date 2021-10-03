<?php

use Phinx\Migration\AbstractMigration;

class VariantIdColumnAdded extends AbstractMigration
{
    public function change()
    {
        $this->execute(
            <<<qqqq
alter table bot_user
    add column variant_id int;
qqqq
        );
    }
}
