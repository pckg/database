<?php namespace Pckg\Database\Entity\Extension;

/**
 * Class Orderable
 *
 * @package Pckg\Database\Entity\Extension
 */
trait Orderable
{

    /**
     * @var string
     */
    protected $orderableField = 'order';

    /**
     * @var string
     */
    protected $orderableDirection = 'ASC';

    /**
     *
     */
    public function initOrderableExtension()
    {
        if ($this->orderableField == 'order') {
            $this->orderableField = '`' . $this->alias . '`.`order`';
        }

        $this->orderBy($this->orderableField . ' ' . $this->orderableDirection);
    }

}