<?php namespace Pckg\Database\Record\Extension;

use Pckg\Database\Entity;
use Pckg\Database\Repository;

trait Translatable
{

    public function deleteTranslation($language, Entity $entity = null, Repository $repository = null)
    {
        if (!$entity) {
            $entity = $this->getEntity();
        }

        if (!$repository) {
            $repository = $entity->getRepository();
        }

        $deleted = $repository->deleteTranslation($this, $entity, $language);

        return $deleted;
    }

}