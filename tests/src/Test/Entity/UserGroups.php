<?php namespace Test\Entity;

use Pckg\Database\Entity;
use Test\Record\User;

class UserGroups extends Entity
{

    protected $record = User::class;

    public function users()
    {
        return $this->hasMany(Users::class)
                    ->foreignKey('user_group_id');
    }

}