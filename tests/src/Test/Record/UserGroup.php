<?php namespace Test\Record;

use Pckg\Database\Record;
use Test\Entity\UserGroups;
use Test\Entity\Users;

class UserGroup extends Record
{

    protected $entity = UserGroups::class;

}