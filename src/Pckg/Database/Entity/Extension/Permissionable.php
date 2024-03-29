<?php

namespace Pckg\Database\Entity\Extension;

use Pckg\Concept\Reflect;
use Pckg\Database\Entity;
use Pckg\Database\Entity\Extension\Adapter\Auth as AuthAdapter;
use Pckg\Database\Entity\Extension\Adapter\AuthInterface;
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

    protected $permissionableAuth;

    /**
     * @param AuthInterface $auth
     */
    public function checkPermissionableDependencies()
    {
        /**
         * @T00D00 - check if we're able to resolve AuthInterface implementation.
         *         If not, use default.
         */
        if (!Reflect::canResolve(AuthInterface::class)) {
            context()->bind(AuthInterface::class, new AuthAdapter());
        }
    }

    /**
     * @param AuthInterface $auth
     */
    public function injectPermissionableDependencies(AuthInterface $auth)
    {
        $this->permissionableAuth = $auth;
    }

    /**
     * @return AuthInterface
     */
    public function getPermissionableAuth()
    {
        return $this->permissionableAuth;
    }

    /**
     *
     */
    public function initPermissionableExtension()
    {
    }

    /**
     * @return mixed
     */
    public function withAllPermissions()
    {
        $permissionTable = $this->getPermissionableTable();
        $repository = $this->getRepository();

        $relation = $this->hasMany((new Entity($repository))->setTable($permissionTable))
                         ->foreignKey('id')
                         ->fill('allPermissions')
                         ->addSelect(['`' . $permissionTable . '`.*']);

        return $this->with($relation);
    }

    /**
     * @return array
     */
    /*public function getPermissionableFields()
    {
        return $this->getRepository()->getCache()->getTableFields($this->table . $this->permissionableTableSuffix);
    }*/

    /**
     * @param Record $record
     *
     * @return array
     */
    /*public function getPermissionableForeignKeys(Record $record)
    {
        return [
            $this->primaryKey                    => $record->{$this->primaryKey},
            $this->permissionablePermissionField => $this->permissionableAuth->groupId(),
        ];
    }*/

    /**
     * @return string
     */
    public function getPermissionableTable()
    {
        return $this->getTable() . $this->getPermissionableTableSuffix();
    }

    /**
     * @return string
     */
    public function getPermissionableTableSuffix()
    {
        return $this->permissionableTableSuffix;
    }

    /**
     * @param callable|null $callable
     *
     * @return mixed
     */
    public function joinPermissions(callable $callable = null)
    {
        return $this->join($this->permissions($callable));
    }

    /**
     * @return mixed
     */
    public function permissions(callable $callable = null)
    {
        $relation = $this->allPermissions()
                         ->innerJoin();

        if ($callable) {
            $this->addPermissionableConditionIfNot($relation);

            $relation->reflect($callable, $this);
        } else {
            $this->addPermissionableCondition($relation);
        }

        return $relation;
    }

    /**
     * @param callable|null $callable
     *
     * @return mixed
     */
    public function allPermissions(callable $callable = null)
    {
        $permissionTable = $this->getPermissionableTable();
        $repository = $this->getRepository();

        $relation = $this->hasMany((new Entity($repository))->setTable($permissionTable))
                         ->foreignKey('id')
                         ->fill('allPermissions')
                         ->addSelect(['`' . $permissionTable . '`.*']);

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
        }

        return $relation;
    }

    /**
     * @param HasMany $relation
     */
    private function addPermissionableConditionIfNot(HasMany $relation)
    {
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

    /**
     * @param HasMany $relation
     */
    private function addPermissionableCondition(HasMany $relation)
    {
        $permissionTable = $this->getTable() . $this->getPermissionableTableSuffix();

        $relation->where(
            '`' . $permissionTable . '`.`' . $this->permissionablePermissionField . '`',
            $this->permissionableAuth->groupId()
        );
    }

    /**
     * @return mixed
     */
    public function withPermission()
    {
        return $this->withPermissions(
            function (Query $query) {
                $query->where($this->permissionablePermissionField, $this->permissionableAuth->groupId());
            }
        );
    }

    /**
     * @param callable|null $callable
     *
     * @return mixed
     */
    public function withPermissions(callable $callable = null)
    {
        return $this->with($this->permissions($callable));
    }

    /**
     * @param callable|null $callable
     *
     * @return mixed
     */
    public function joinPermission(callable $callable = null)
    {
        return $this->join($this->permissions($callable))
                    ->prependSelect([$this->getTable() . $this->permissionableTableSuffix . '.*']);
    }

    /**
     * @return mixed
     */
    public function joinPermissionTo($permission)
    {
        $self = $this;

        return $this->join(
            $this->permissions(
                function (HasMany $permissions) use ($permission, $self) {
                    $permissions->where($self->getPermissionableTable() . '.action', $permission);
                }
            )
        )->prependSelect([$this->getTable() . $this->permissionableTableSuffix . '.*']);
    }

    /**
     * @return $this
     */
    public function usePermissionableTable()
    {
        if (strpos($this->table, $this->getPermissionableTableSuffix()) === false) {
            $this->table = $this->getPermissionableTable();
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function isPermissionable()
    {
        if (!$this->permissionableTableSuffix) {
            return false;
        }

        return $this->getRepository()->getCache()->hasTable($this->table . $this->permissionableTableSuffix);
    }
}
