<?php namespace Pckg\Database\Record\Extension;

trait PrevAndNext
{

    public function getPrevAttribute()
    {
        $entity = $this->getEntity();

        return $this->cache('prev', function() use ($entity) {
            return (new $entity())->published()
                                  ->where('dt_published', $this->dt_published, '<')
                                  ->orderBy('dt_published DESC, ' . $entity->getTable() . '.id DESC')
                                  ->one();
        });
    }

    public function getNextAttribute()
    {
        $entity = $this->getEntity();

        return $this->cache('next', function() use ($entity) {
            return (new $entity())->published()
                                  ->where('dt_published', $this->dt_published, '>')
                                  ->orderBy('dt_published ASC, ' . $entity->getTable() . '.id ASC')
                                  ->one();
        });
    }

}