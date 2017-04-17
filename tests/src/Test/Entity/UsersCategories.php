<?php namespace Test\Entity;

use Pckg\Database\Entity;

class UsersCategories extends Entity
{

    public function category()
    {
        return $this->belongsTo(Categories::class)
                    ->foreignKey('category_id');
    }

}