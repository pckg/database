<?php namespace Pckg\Database\Field;

/**
 * Class Raw
 *
 * @package Pckg\Database\Field
 */
class Raw implements Stringifiable
{

    /**
     * @var null|string
     */
    protected $sql;

    /**
     * @var array
     */
    protected $binds = [];

    /**
     * Raw constructor.
     *
     * @param string|null $sql
     * @param array       $binds
     */
    public function __construct(string $sql = null, $binds = [])
    {
        $this->sql = $sql;
        $this->binds = $binds;
    }

    /**
     * @param string $sql
     * @param array  $binds
     *
     * @return static
     */
    public static function raw(string $sql, array $binds = [])
    {
        return new static($sql, $binds);
    }

    /**
     * @return array
     */
    public function getBind()
    {
        return $this->binds;
    }

    /**
     * @return string|null
     */
    public function __toString()
    {
        return $this->sql;
    }

    public function validateValue($value)
    {
        throw new \Exception('Raw cannot be used as a encapsulator!');
    }

    public function jsonSerialize()
    {
        throw new \Exception('No json serializer');
    }

    public function encapsulated()
    {
        throw new \Exception('No encapsul');
    }

    public function decapsulate()
    {
        throw new \Exception('Raw cannot be decapsulated');
    }

    /**
     * This should be safe string!
     *
     * @return string|null
     */
    public function getPlaceholder()
    {
        return $this->sql;
    }


}