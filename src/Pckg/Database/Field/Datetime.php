<?php namespace Pckg\Database\Field;

use Pckg\Collection;
use Pckg\Collection\CollectionHelper;
use Pckg\Database\Record;

/**
 * Class Datetime
 * @package Pckg\Database\Field
 * @see \DateTimeImmutable::add()
 * @method $this add(\DateInterval $dateInterval)
 */
class Datetime extends AbstractField
{

    /**
     * @var string
     */
    protected $format = 'Y-m-d H:i:s';

    /**
     * @param mixed|mixed $value
     * @return mixed|void
     * @throws \Exception
     */
    public function validateValue($value)
    {
        if ($value instanceof \DateTimeImmutable) {
            $this->data = $value;
            return;
        }

        if (is_string($value)) {
            $this->data = new \DateTimeImmutable($value);
            return;
        }

        if (is_null($value)) {
            $this->data = null;
            return;
        }

        throw new \Exception('Invalid Datetime data');
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        if (!$this->data) {
            return null;
        }

        return $this->data->format($this->format);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->jsonSerialize() ?? '';
    }

    /**
     * @return $this
     */
    public function now()
    {
        $this->set(new \DateTimeImmutable());

        return $this;
    }

    /**
     * @return $this
     */
    public function empty()
    {
        $this->set(null);

        return $this;
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!$this->data) {
            //$this->data = new \DateTimeImmutable();
            throw new \Exception('No data to __call');
        }

        $this->markDirty();

        $result = $this->data->{$method}(...$args);

        if (!($result instanceof \DateTimeImmutable)) {
            return $result;
        }

        $this->data = $result;

        return $this;
    }

}