<?php namespace Pckg\Database\Record\Extension;

use Pckg\Database\Entity;
use Pckg\Database\Repository;

trait Deletable
{

    /**
     * @return mixed
     */
    public function forceDelete(Entity $entity = null, Repository $repository = null)
    {
        return parent::delete($entity, $repository);
    }

    /**
     * @return mixed
     */
    public function delete(Entity $entity = null, Repository $repository = null)
    {
        $this->deleted_at = date('Y-m-d H:i:s');

        return $this->update($entity, $repository);
    }

    public function restore(Entity $entity = null, Repository $repository = null)
    {
        $this->deleted_at = null;

        return $this->update($entity, $repository);

    }

    public function restoreIfDeleted(Entity $entity = null, Repository $repository = null)
    {
        if ($this->deleted_at) {
            return $this->restore();
        }

        return $this;
    }

}