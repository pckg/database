<?php

namespace Pckg\Database\Entity\Extension;

/**
 * Class SoftDelete
 *
 * @package Pckg\Database\Entity\Extension
 */
trait SoftDelete
{

    /**
     * @var bool
     */
    protected $softDeleteEnabled = true;

    /**
     * @var string
     */
    protected $softDeleteField = 'deleted_at';

    /**
     *
     */
    public function applySoftDeleteExtension()
    {
        $this->query->where($this->softDeleteField, null);
    }

    /**
     * @param bool|true $enabled
     *
     * @return $this
     */
    public function withSoftDeleted($enabled = true)
    {
        $this->softDeleteEnabled = $enabled;

        return $this;
    }

    /* Overrides Entity's method */

    /**
     * @return mixed
     */
    public function forceDelete()
    {
        return $this->delete();
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        $this->{$this->softDeleteField} = date('Y-m-d H:i:s');

        return $this->update();
    }

}