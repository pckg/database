<?php

namespace Pckg\Database\Entity\Extension;

use Pckg\Database\Entity;
use Pckg\Database\Record;

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

    /**
     * @return \Closure[]
     */
    public function collectTimeableEvents()
    {
        return [
            'inserting' => function (Record $record) {
                $record->created_at = date('Y-m-d H:i:s');
            },
            'updating' => function (Record $record) {
                $record->updated_at = date('Y-m-d H:i:s');
            },
            'softDeleting' => function (Record $record) {
                $record->deleted_at = date('Y-m-d H:i:s');
            },
        ];
    }
}
