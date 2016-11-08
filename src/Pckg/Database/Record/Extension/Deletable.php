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

}