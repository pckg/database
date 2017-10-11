<?php namespace Pckg\Database\Entity\Extension;

/**
 * Class Deletable
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
     * @return $this
     */
    public function onlyDeleted()
    {
        $this->where($this->deletableField);

        return $this;
    }

    /**
     * @return $this
     */
    public function nonDeleted()
    {
        $this->where($this->deletableField, null);

        return $this;
    }

    /**
     * @return mixed
     */
    public function isDeletable()
    {
        return $this->getRepository()->getCache()->tableHasField($this->table, $this->deletableField);
    }

}