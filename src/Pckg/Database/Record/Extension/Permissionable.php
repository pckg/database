<?php namespace Pckg\Database\Record\Extension;

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

}