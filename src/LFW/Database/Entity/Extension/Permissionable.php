<?php

namespace LFW\Database\Entity\Extension;

use LFW\Database\Entity\Adapter\Auth;
use LFW\Database\Record;

/**
 * Class Permissionable
 * @package LFW\Database\Entity\Extension
 */
trait Permissionable
{

    /**
     * @var array
     */
    protected $permissionableFields = [];

    /**
     * @var string
     */
    protected $permissionableTableSuffix = '_permissions';

    /**
     * @var string
     */
    protected $permissionablePermissionField = 'permission_id';

    /**
     * @var
     */
    protected $permissionableAuth;

    /**
     * @param Auth $lang
     */
    public function injectPermissionableDependencies(Auth $lang)
    {
        $this->permissionableAuth = $lang;
    }

    /**
     *
     */
    public function initPermissionableExtension()
    {

    }

    /**
     * @return string
     */
    public function getPermissionableTableSuffix()
    {
        return $this->permissionableTableSuffix;
    }

    /**
     * @return array
     */
    public function getPermissionableFields()
    {
        return $this->permissionableFields;
    }

    /**
     * @param Record $record
     * @return array
     */
    public function getPermissionableForeignKeys(Record $record)
    {
        return [
            $this->permissionablePermissionField => $this->permissionableAuth->getGroupId(),
            $this->primary = $record->{$this->primary}
        ];
    }

    /**
     * @return mixed
     */
    public function permissions()
    {
        return $this->hasMany($this->table . $this->permissionableTableSuffix);
    }

}