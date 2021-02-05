<?php

namespace Pckg\Database\Record\Extension;

use Pckg\Database\Entity;
use Pckg\Database\Repository;

/**
 * Class Translatable
 *
 * @package Pckg\Database\Record\Extension
 */
trait Translatable
{

    /**
     * @param                 $language
     * @param Entity|null     $entity
     * @param Repository|null $repository
     *
     * @return $this|\Pckg\Database\Record
     */
    public function deleteTranslation($language, Entity $entity = null, Repository $repository = null)
    {
        if (!$entity) {
            $entity = $this->getEntity();
        }

        if (!$repository) {
            $repository = $entity->getRepository();
        }

        $repository = $repository->aliased('write');

        $deleted = $repository->deleteTranslation($this, $entity, $language);

        return $deleted;
    }
}
