<?php

namespace Pckg\Database\Entity\Extension;

/**
 * Class SoftDelete
 *
 * @package Pckg\Database\Entity\Extension
 */
trait Deletable
{

    /**
     * @var bool
     */
    protected $deletableEnabled = true;

    /**
     * @var string
     */
    protected $deletableField = 'deleted_at';

    /**
     *
     */
    public function applyDeletableExtension()
    {
        //$this->query->where($this->deletableField, null);
    }

    /**
     * @param bool|true $enabled
     *
     * @return $this
     */
    public function onlyDeleted()
    {
        $this->query->where($this->deletableField);

        return $this;
    }

    public function nonDeleted()
    {
        $this->query->where($this->deletableField, null);

        return $this;
    }

}