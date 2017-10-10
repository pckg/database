<?php namespace Entity;

use Pckg\Database\Entity;
use Record\Item;

class Items extends Entity
{

    protected $record = Item::class;

    public function group()
    {
        return $this->belongsTo(Groups::class)
                    ->foreignKey('group_id');
    }

}