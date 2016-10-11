<?php namespace Test\Entity;

use Pckg\Database\Entity;

class Languages extends Entity
{

    public function users()
    {
        return $this->hasMany(Users::class)
                    ->foreignKey('language_id');
    }

}