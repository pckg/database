<?php namespace Pckg\Database\Record\Extension;

trait PrevAndNext
{

    public function getPrevAttribute()
    {
        $entity = $this->getEntity();

        return $this->cache('prev', function() use ($entity) {
            $entity = (new $entity())->published()
                                     ->where('dt_published', $this->dt_published, '<')
                                     ->orderBy('dt_published DESC, ' . $entity->getTable() . '.id DESC');

            if ($entity->hasField('private')) {
                $entity->where('private', null);
            }

            return $entity->one();
        });
    }

    public function getNextAttribute()
    {
        $entity = $this->getEntity();

        return $this->cache('next', function() use ($entity) {
            $entity = (new $entity())->published()
                                     ->where('dt_published', $this->dt_published, '>')
                                     ->orderBy('dt_published ASC, ' . $entity->getTable() . '.id ASC');

            if ($entity->hasField('private')) {
                $entity->where('private', null);
            }

            return $entity->one();
        });
    }

}