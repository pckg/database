<?php namespace Test\Record;

use Pckg\Database\Record;
use Test\Entity\Users;

class User extends Record
{

    protected $entity = Users::class;

}