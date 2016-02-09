<?php

namespace Pckg\Database\Query;

class Parenthesis
{

    protected $glue = 'AND';

    protected $children = [];

    public function setGlue($glue)
    {
        $this->glue = $glue;

        return $this;
    }

    public function push($child)
    {
        $this->children[] = $child;

        return $this;
    }

    public function hasChildren()
    {
        return !empty($this->children);
    }

    public function build()
    {
        return $this->children
            ? '(' . implode(') ' . $this->glue . ' (', $this->children) . ')'
            : '';
    }

    public function __toString()
    {
        return (string)$this->build();
    }

}