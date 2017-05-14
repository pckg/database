<?php namespace Pckg\Database\Record\Extension;

use Pckg\Database\Entity;
use Pckg\Database\Repository;

trait Deletable
{

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