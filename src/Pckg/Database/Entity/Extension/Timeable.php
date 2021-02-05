<?php

namespace Pckg\Database\Entity\Extension;

/**
 * Class Timeable
 *
 * @package Pckg\Database\Entity\Extension
 */
trait Timeable
{

    /**
     * @var array
     */
    protected $timeableFields = ['created_at', 'updated_at', 'deleted_at'];

    /**
     *
     */
    public function initTimeableExtension()
    {
        foreach ($this->timeableFields as $field) {
            $this->fields[] = $field;
        }
    }
}
