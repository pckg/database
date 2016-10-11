<?php namespace Test\Entity;

use Pckg\Database\Entity;

class Categories extends Entity
{

    public function users()
    {
        return $this->hasAndBelongsTo(Users::class)
                    ->over('users_categories')
                    ->leftForeignKey('category_id')
                    ->rightForeignKey('user_id');
    }

}