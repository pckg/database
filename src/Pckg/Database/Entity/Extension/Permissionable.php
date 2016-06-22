<?php

namespace Pckg\Database\Entity\Extension;

use Pckg\Auth\Entity\Adapter\Auth;
use Pckg\Concept\Reflect;
use Pckg\Database\Entity;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Relation\HasMany;

/**
 * Class Permissionable - permissionalisation - p17n
 *
 * @package Pckg\Database\Entity\Extension
 */
trait Permissionable
{

    /**
     * @var string
     */
    protected $permissionableTableSuffix = '_p17n';

    /**
     * @var string
     */
    protected $permissionablePermissionField = 'user_group_id';

    /**
     * @var
     */
    protected $permissionableAuth;

    /**
     * @param Auth $lang
     */
    public function injectPermissionableDependencies(Auth $lang) {
        $this->permissionableAuth = $lang;
    }

    /**
     *
     */
    public function initPermissionableExtension() {

    }

    /**
     * @return string
     */
    public function getPermissionableTableSuffix() {
        return $this->permissionableTableSuffix;
    }

    /**
     * @return array
     */
    public function getPermissionableFields() {
        return $this->getRepository()->getCache()->getTableFields($this->table . $this->permissionableTableSuffix);
    }

    /**
     * @param Record $record
     *
     * @return array
     */
    public function getPermissionableForeignKeys(Record $record) {
        return [
            $this->primaryKey => $record->{$this->primaryKey},
            $this->permissionablePermissionField => $this->permissionableAuth->groupId(),
        ];
    }

    /**
     * @return mixed
     */
    public function permissions(callable $callable = null) {
        $permissionTable = $this->getTable() . $this->getPermissionableTableSuffix;
        $repository = $this->getRepository();

        /**
         * @T00D00 - group should be binded (PDO) ...
         */
        $relation = $this->hasMany((new Entity($repository))->setTable($permissionTable))
                         ->foreignKey('id')
                         ->fill('_permissions')
                         ->addSelect(['`' . $permissionTable . '`.*'])
                         ->innerJoin();

        if ($callable) {
            $query = $relation->getRightEntity()->getQuery();

            Reflect::call(
                $callable,
                [
                    $query,
                    $relation,
                    $this,
                ]
            );

            $this->addPermissionableConditionIfNot($relation);

        } else {
            $this->addPermissionableCondition($relation);

        }

        return $relation;
    }

    private function addPermissionableConditionIfNot(HasMany $relation) {
        $foundGroupCondition = false;
        $query = $relation->getQuery();
        foreach ($query->getWhere() as $where) {
            foreach ($where->getChildren() as $key => $child) {
                if (strpos($key, $this->permissionablePermissionField)) {
                    $foundGroupCondition = true;
                }
            }
        }

        if (!$foundGroupCondition) {
            $this->addPermissionableCondition($relation);
        }
    }

    private function addPermissionableCondition(HasMany $relation) {
        $permissionTable = $this->getTable() . $this->getPermissionableTableSuffix;

        $relation->where(
            '`' . $permissionTable . '`.`' . $this->permissionablePermissionField . '`',
            $this->permissionableAuth->groupId()
        );
    }

    public function withPermissions(callable $callable = null) {
        return $this->with($this->permissions($callable));
    }

    public function joinPermissions(callable $callable = null) {
        return $this->join($this->permissions($callable));
    }

    public function withPermission() {
        return $this->withPermissions(
            function(Query $query) {
                $query->where($this->permissionablePermissionField, $this->permissionableAuth->groupId());
            }
        );
    }

    public function joinPermission(callable $callable = null) {
        return $this->join($this->permissions($callable))
                    ->prependSelect([$this->getTable() . $this->permissionableTableSuffix . '.*']);
    }

}