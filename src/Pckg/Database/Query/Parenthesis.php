<?php namespace Pckg\Database\Query;

class Parenthesis
{

    protected $glue;

    protected $children = [];

    protected $binds = [];

    public function __construct($glue = 'AND')
    {
        $this->glue = $glue;
    }

    public function setGlue($glue)
    {
        $this->glue = $glue;

        return $this;
    }

    public function push($child, $binds = [])
    {
        $this->children[] = $child;

        if (!is_array($binds)) {
            $binds = [$binds];
        }
        foreach ($binds as $bind) {
            $this->binds[] = $bind;
        }

        return $this;
    }

    public function hasChildren()
    {
        return !empty($this->children);
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    public function __toString()
    {
        return (string)$this->build();
    }

    public function build()
    {
        return $this->children
            ? '((' . implode(') ' . $this->glue . ' (', $this->children) . '))'
            : '';
    }

    public function getBinds(&$binds = [])
    {
        foreach ($this->children as $child) {
            if ($child instanceof Parenthesis) {
                $child->getBinds($binds);
            }
        }
        foreach ($this->binds as $bind) {
            $binds[] = $bind;
        }

        return $binds;
    }

}