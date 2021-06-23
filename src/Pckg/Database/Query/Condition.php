<?php

namespace Pckg\Database\Query;

class Condition implements Conditional, Buildable, Bindable
{

    protected string $key;

    protected $value;

    protected $operator;

    protected $binds;

    public function __construct(string $key, $value, string $operator = '=', $binds = [])
    {
        $this->key = $key;
        $this->value = $value;
        $this->operator = $operator;
        $this->binds = $binds;
    }

    public function buildBinds()
    {
        return $this->binds;
    }
}
