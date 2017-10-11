<?php namespace Pckg\Database\Record\Extension;

use Pckg\Database\Record;

/**
 * Class Permissionable
 *
 * @package Pckg\Database\Record\Extension
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
            function($permission) use ($action) {
                return $permission->action == $action;
            }
        );
    }

    /**
     * @param      $action
     * @param null $userGroupId
     */
    public function grantPermissionTo($action, $userGroupId = null)
    {
        $entity = $this->getEntity()->usePermissionableTable();

        Record::create([
                           'id'            => $this->id,
                           'user_group_id' => $userGroupId,
                           'action'        => $action,
                       ], $entity);
    }

}