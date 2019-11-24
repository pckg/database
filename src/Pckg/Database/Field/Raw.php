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
     * @return string|null
     */
    public function __toString()
    {
        return $this->sql;
    }

}