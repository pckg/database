<?php namespace Test\Entity;

use Pckg\Database\Entity;
use Pckg\Database\Entity\Extension\Translatable;
use Test\Record\User;

class UserGroups extends Entity
{

    use Translatable;

    protected $record = User::class;

    public function users()
    {
        return $this->hasMany(Users::class)
                    ->foreignKey('user_group_id');
    }

}