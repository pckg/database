<?php

namespace Pckg\Database\Entity\Extension;

/**
 * Class Orderable
 * @package Pckg\Database\Entity\Extension
 */
trait Orderable
{

    /**
     * @var string
     */
    protected $orderableField = 'ord';

    /**
     * @var string
     */
    protected $orderableDirection = 'ASC';

    /**
     *
     */
    public function initOrderableExtension()
    {
        $this->query->orderBy($this->orderableField, $this->orderableDirection);
    }

}