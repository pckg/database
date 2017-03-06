<?php namespace Pckg\Database\Record\Extension;

use Pckg\Database\Record;

trait Permissionable
{

    public function hasPermissionTo($action)
    {
        return $this->allPermissions->has(
            function($permission) use ($action) {
                return $permission->action == $action;
            }
        );
    }

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