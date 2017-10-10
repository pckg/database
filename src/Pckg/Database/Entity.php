<?php namespace Pckg\Database;

use Pckg\Database\Entity\BlankEntity;
use Pckg\Database\Entity\Extension\Deletable;
use Pckg\Database\Entity\Extension\Paginatable;
use Pckg\Database\Entity\Extension\Permissionable;
use Pckg\Database\Entity\Extension\Translatable;
use Pckg\Database\Record as DatabaseRecord;

class Entity extends BlankEntity
{

    use Permissionable, Deletable, Translatable, Paginatable;

}