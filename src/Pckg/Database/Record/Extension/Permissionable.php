<?php

namespace Pckg\Database\Record\Extension;

use Pckg\Collection;
use Pckg\Database\Record;

/**
 * Class Permissionable
 *
 * @package Pckg\Database\Record\Extension
 * @property Collection $allPermissions
 */
trait Permissionable
{
    /**
     * @param $action
     *
     * @return mixed
     */
    public function hasPermissionTo($action)
    {
        return $this->allPermissions->has(
            function ($permission) use ($action) {
                return $permission->action == $action;
            }
        );
    }

    public function removePermissions()
    {
        return $this->getEntity()->usePermissionableTable()->resetQuery()->where('id', $this->id)->delete();
    }

    /**
     * @param      $action
     * @param null $userGroupId
     */
    public function grantPermissionTo($actions, $userGroupIds = [])
    {
        if (!is_array($userGroupIds)) {
            $userGroupIds = [$userGroupIds];
        }

        if (!is_array($actions)) {
            $actions = [$actions];
        }

        $entity = $this->getEntity()
                       ->usePermissionableTable()
                       ->resetQuery();

        foreach ($userGroupIds as $userGroupId) {
            foreach ($actions as $action) {
                Record::getOrCreate([
                                        'id'            => $this->id,
                                        'user_group_id' => $userGroupId,
                                        'action'        => $action,
                                    ], $entity);
            }
        }
    }
}
