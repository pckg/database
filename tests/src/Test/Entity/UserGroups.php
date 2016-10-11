<?php namespace Test\Entity;

use Pckg\Database\Entity;
use Pckg\Database\Entity\Extension\Translatable;

class UserGroups extends Entity
{

    use Translatable;

    public function users()
    {
        return $this->hasMany(Users::class)
                    ->foreignKey('user_id');
    }

}