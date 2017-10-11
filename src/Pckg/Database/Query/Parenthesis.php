<?php namespace Pckg\Database\Query;

/**
 * Class Parenthesis
 *
 * @package Pckg\Database\Query
 */
class Parenthesis
{

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
     * @param $glue
     *
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
     * @param $children
     *
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

    /**
     * @return string
     */
    public function build()
    {
        return $this->children
            ? '((' . implode(') ' . $this->glue . ' (', $this->children) . '))'
            : '';
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

}