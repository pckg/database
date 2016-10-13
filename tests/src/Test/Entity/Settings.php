<?php namespace Test\Entity;

use Pckg\Database\Entity;

class Settings extends Entity
{

    public function users()
    {
        return $this->morphsMany(Users::class);
    }

}