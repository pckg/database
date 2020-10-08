<?php namespace Pckg\Database\Field;

use Pckg\Database\Record;

/**
 * Class AbstractField
 * @package Pckg\Database\Field
 */
abstract class AbstractField implements Stringifiable, \JsonSerializable
{

    /**
     * @var string
     */
    private $key;

    /**
     * @var Record
     */
    private $record;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * AbstractField constructor.
     * @param string $key
     * @param Record $record
     */
    public function __construct($value, string $key, Record $record)
    {
        $this->validateValue($value);
        $this->key = $key;
        $this->record = $record; // can we auto discover record?
    }

    /**
     * Placeholders in SQL.
     *
     * @return string
     */
    public function getPlaceholder()
    {
        return '?';
    }

    /**
     * Unsafe values for placeholders.
     *
     * @return mixed
     */
    public function getBind()
    {
        return $this->__toString();
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
     * @return mixed
     */
    public function encapsulated()
    {
        return $this->data;
    }

    /**
     * @return Record
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param $value
     * @return $this
     */
    public function set($value)
    {
        $this->validateValue($value);
        $this->markDirty();

        return $this;
    }

    /**
     * @return $this
     */
    public function markDirty()
    {
        $this->record->markDirty($this->key);

        return $this;
    }

    /**
     * @return array|float|int|mixed|object|string|null
     */
    public function decapsulate()
    {
        return $this->jsonSerialize();
    }

}