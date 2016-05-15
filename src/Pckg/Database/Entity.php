<?php

namespace Pckg\Database;

use Exception;
use Pckg\Concept\Reflect;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Query\Helper\QueryBuilder;
use Pckg\Database\Query\Helper\With;
use Pckg\Database\Relation\Helper\RelationMethods;

/**
 * Presents table in database
 *
 */
class Entity
{

    use RelationMethods, QueryBuilder, With;

    /**
     * @var
     */
    protected $table;

    /**
     * @var
     */
    protected $record = Record::class;

    protected $primaryKey = 'id';

    /**
     * @var Repository
     */
    protected $repository;

    protected $repositoryName = Repository::class;

    /**
     * @var array
     */
    protected $relations = [];

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $extensions = [];

    protected $useCache;

    /**
     * @param Repository $repository
     */
    public function __construct(Repository $repository = null)
    {
        $this->repository = $repository;

        if (!$repository) {
            $this->repository = context()->get($this->repositoryName);

            if (!$this->repository) {
                throw new Exception('Cannot prepare repository');
            }
        }

        $this->guessDefaults();
        $this->initExtensions();
    }

    /**
     * @param $method
     * @param $args
     *
     * @return $this
     * @throws Exception
     */
    public static function __callStatic($method, $args)
    {
        $entity = Reflect::create(static::class);

        return Reflect::method($entity, $method, $args);
    }

    /**
     * @param $method
     * @param $args
     *
     * @return $this
     */
    public function __call($method, $args)
    {
        $relation = $this->callWith($method, $args, $this);

        return $this;
    }

    /**
     * @param $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        if (method_exists($this, $property)) {
            return $this->{$property}();
        }

        return null;
    }

    /**
     *
     */
    protected function guessDefaults()
    {
        if (!$this->table) {
            $class = explode('\\', static::class);
            $this->table = Convention::fromCamel(end($class));
        }

        if (!$this->record) {
            $class = static::class;

            if (strpos('\\Entity\\', $class)) {
                $class = explode('\\', str_replace('\\Entity\\', '\\Record\\', $class));
                $class[count($class) - 1] = Convention::nameOne($class[count($class) - 1]);
                $class = implode('\\', $class);
                if (class_exists($class)) {
                    $this->record = $class;
                }
            }
        }
    }

    /**
     * @return mixed
     */
    public function getRecordClass()
    {
        return $this->record;
    }

    public function setRecordClass($class)
    {
        $this->record = $class;

        return $this;
    }

    /**
     * @param $table
     *
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;
        $this->getQuery()->table($table);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getFields()
    {
        return $this->fields ?: $this->getRepository()->getCache()->getTableFields($this->table);
    }

    /*
     * Set repository/connection
     * */
    /**
     * @param Repository $repository
     *
     * @return $this
     */
    public function setRepository(Repository $repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param Relation $relation
     *
     * @return $this
     */
    public function addRelation(Relation $relation)
    {
        $this->relations[] = $relation;

        return $this;
    }

    /**
     * @param $extension
     *
     * @return $this
     */
    public function addExtension($extension)
    {
        $this->extensions[] = $extension;

        return $this;
    }

    /*
     * Find out which methods are extensions and init them.
     * */
    /**
     * @return $this
     */
    public function initExtensions()
    {
        foreach (get_class_methods($this) as $method) {
            if (substr($method, 0, 4) == 'init' && substr($method, -9) == 'Extension') {
                $this->{$method}();

            } else if (substr($method, 0, 6) == 'inject' && substr($method, -12) == 'Dependencies') {
                Reflect::method($this, $method);

            }
        }

        return $this;
    }

    /*
     * Find out which methods are extensions and init them.
     * */
    /**
     * @return $this
     */
    public function applyExtensions()
    {
        foreach (get_class_methods($this) as $method) {
            if (substr($method, 0, 4) == 'apply' && substr($method, -9) == 'Extension') {
                $this->{$method}();
            }
        }

        return $this;
    }

    /**
     * Transforms Record to two-dimensional array of tables and fields.
     *
     * @param Record $record
     *
     * @return array
     */
    public function tabelizeRecord(Record $record)
    {
        $dataArray = $record->toArray();
        $keys = [
            $this->table => $this->repository->getCache()->getTableFields($this->table),
        ];

        // Get extensions - which extends table with another table - and their fields.
        foreach (get_class_methods($this) as $method) {
            if ($method != 'getFields' && substr($method, 0, 3) == 'get' && substr($method, -6) == 'Fields') {
                $suffix = $this->{'get' . substr($method, 3, -6) . 'TableSuffix'}();
                if (substr($this->table, strlen($this->table) - strlen($suffix)) != $suffix) {
                    $keys[$this->table . $suffix] = $this->{$method}();
                }
            }
        }

        // fill array with tables and fields
        $values = [];
        foreach ($keys as $table => $fields) {
            foreach ($fields as $field) {
                if (array_key_exists($field, $dataArray) && $this->repository->getCache()->tableHasField($table,
                        $field)
                ) {
                    $values[$table][$field] = $dataArray[$field];
                }
            }
        }

        // Get extensions' foreign keys
        foreach (get_class_methods($this) as $method) {
            if (substr($method, 0, 3) == 'get' && substr($method, -11) == 'ForeignKeys') {
                $values[$table] = array_merge($this->{$method}($record), isset($values[$table]) ? $values[$table] : []);
            }
        }

        return $values;
    }

    /**
     * @return Record
     */
    public function one()
    {
        $this->applyExtensions();

        return $this->repository->one($this);
    }

    /**
     * @return Collection
     */
    public function all()
    {
        $this->applyExtensions();

        return $this->repository->all($this);
    }

    /**
     * @return Record
     * @throws Exception
     */
    public function oneOrFail(callable $callback = null)
    {
        if ($result = $this->one()) {
            return $result;
        }

        if ($callback) {
            return $callback();
        }

        throw new Exception('No record found');
    }

    /**
     * @throws Exception
     * @return Collection
     * */
    public function allOrFail()
    {
        if ($results = $this->all()) {
            return $results;
        }

        throw new Exception('No records found');
    }

}