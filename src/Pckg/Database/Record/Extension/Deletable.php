<?php

namespace Pckg\Database\Record\Extension;

use Pckg\Database\Entity;
use Pckg\Database\Repository;

/**
 * Class Deletable
 *
 * @package Pckg\Database\Record\Extension
 * @property string $deleted_at
 */
trait Deletable
{

    /**
     * @param Entity|null     $entity
     * @param Repository|null $repository
     *
     * @return $this|mixed
     */
    public function restoreIfDeleted(Entity $entity = null, Repository $repository = null)
    {
        if ($this->deleted_at) {
            return $this->restore();
        }

        return $this;
    }

    /**
     * @param Entity|null     $entity
     * @param Repository|null $repository
     *
     * @return mixed
     */
    public function restore(Entity $entity = null, Repository $repository = null)
    {
        $this->deleted_at = null;

        return $this->update($entity, $repository);
    }
}
