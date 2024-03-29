<?php

namespace Pckg\Database\Query;

use Pckg\Concept\When;

/**
 * Class Parenthesis
 *
 * @package Pckg\Database\Query
 */
class Parenthesis implements Buildable, Bindable
{
    use When;

    /**
     * @var string
     */
    protected $glue;

    /**
     * @var array
     */
    protected $children = [];

    /**
     * @var array
     */
    protected $binds = [];

    /**
     * Parenthesis constructor.
     *
     * @param string $glue
     */
    public function __construct($glue = 'AND')
    {
        $this->glue = $glue;
    }

    /**
     * @return $this
     */
    public function setGlue($glue)
    {
        $this->glue = $glue;

        return $this;
    }

    /**
     * @param       $child
     * @param array $binds
     *
     * @return $this
     */
    public function push($child, $binds = [])
    {
        if (is_string($child) && strpos($child, '(:?)')) {
            if (is_countable($binds)) {
                $child = str_replace('(:?)', '(' . str_repeat('?,', count($binds) - 1) . '?)', $child);
            } elseif (is_object($binds) && $binds instanceof Select) {
                $explodedSql = explode('(:?)', $child, 2);
                $subSql = $binds->buildSQL();
                $child = $explodedSql[0] . '(' . $subSql . ')' . $explodedSql[1];
                $binds = $binds->buildBinds();
            }
        }

        $this->children[] = $child;

        if (!is_array($binds)) {
            $binds = [$binds];
        }
        foreach ($binds as $bind) {
            $this->binds[] = $bind;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return $this
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->build();
    }

    public function __clone()
    {
        $newChildren = [];
        foreach ($this->children as $child) {
            if (!is_object($child)) {
                $newChildren[] = $child;
                continue;
            }

            $newChildren[] = clone $child;
        }
        $this->children = $newChildren;
    }

    /**
     * @return string
     */
    public function build()
    {
        if (!$this->children) {
            return '';
        }

        return collect($this->children)
            ->map(fn($child) => is_object($child) ? $child->buildSQL() : $child)
            ->map(fn($child) => '(' . $child . ')')
            ->implode(' ' . $this->glue . ' ');
    }

    public function buildSQL()
    {
        return '(' . $this->build() . ')';
    }

    /**
     * @param array $binds
     *
     * @return array
     */
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

    public function buildBinds()
    {
        return $this->getBinds();
    }
}
