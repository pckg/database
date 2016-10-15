<?php namespace Test\Entity;

use Pckg\Database\Entity;
use Test\Record\Language;

class Languages extends Entity
{

    protected $record = Language::class;

    public function users()
    {
        return $this->hasMany(Users::class)
                    ->foreignKey('language_id')
                    ->primaryKey('slug');
    }

}