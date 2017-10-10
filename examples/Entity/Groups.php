<?php namespace Entity;

use Pckg\Database\Entity;
use Record\Group;

class Groups extends Entity
{

    protected $record = Group::class;

    public function items()
    {
        return $this->hasMany(Items::class)
                    ->foreignKey('group_id');
    }

}