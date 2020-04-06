<?php namespace Pckg\Database\Field;

class JsonArray implements Stringifiable, \JsonSerializable
{

    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function set($decoded)
    {
        $this->value = json_encode($decoded);

        return $this;
    }

    public function __toString()
    {
        return json_encode($this->value ?? []);
    }

    public function __toArray()
    {
        return json_decode($this->value) ?? [];
    }

    public function jsonSerialize()
    {
        return $this->__toArray();
    }

}