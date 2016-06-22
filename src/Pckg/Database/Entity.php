<?php

namespace Pckg\Database;

use Exception;
use Pckg\Database\Collection;
use Pckg\Concept\Reflect;
use Pckg\Database\Entity\EntityInterface;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Query\Delete;
use Pckg\Database\Query\Helper\QueryBuilder;
use Pckg\Database\Query\Helper\With;
use Pckg\Database\Record\RecordInterface;
use Pckg\Database\Relation\Helper\RelationMethods;

/**
 * Presents table in database
 *
 */
class Entity implements EntityInterface
{

    use RelationMethods, QueryBuilder, With;

    /**
     * @var string
     */
    protected $table;

    protected $alias;

    /**
     * @var RecordInterface
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
    protected $fields = [];

    /**
     * @var array
     */
    protected $extensions = [];

    /**
     * @var bool
     */
    protected $useCache;

    /**
     * @param Repository $repository
     */
    public function __construct(Repository $repository = null) {
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
    public static function __callStatic($method, $args) {
        $entity = Reflect::create(static::class);

        return Reflect::method($entity, $method, $args);
    }

    /**
     * @param $method
     * @param $args
     *
     * @return $this
     */
    public function __call($method, $args) {
        $relation = $this->callWith($method, $args, $this);

        return $this;
    }

    /**
     * @param $property
     *
     * @return mixed
     */
    public function __get($property) {
        if (method_exists($this, $property)) {
            return $this->{$property}();
        }

        return null;
    }

    /**
     *
     */
    protected function guessDefaults() {
        if (!$this->table) {
            if (static::class == Entity::class) {
                $this->table = null;
            } else {
                $class = explode('\\', static::class);
                $this->table = Convention::fromCamel(end($class));
            }
        }

        if (!$this->record) {
            $class = static::class;

            if ($class == Entity::class) {
                $this->record = Record::class;

            } else {
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
    }

    public function getRecord() {
        $class = $this->getRecordClass();

        $record = new $class;
        $record->setEntity($this);
        $record->setEntityClass(get_class($this));

        return $record;
    }

    /**
     * @return mixed
     */
    public function getRecordClass() {
        return $this->record;
    }

    public function setRecordClass($class) {
        $this->record = $class;

        return $this;
    }

    /**
     * @param $table
     *
     * @return $this
     */
    public function setTable($table) {
        $this->table = $table;
        $this->getQuery()->table($table);

        return $this;
    }

    public function setAlias($alias) {
        $this->alias = $alias;
        $this->getQuery()->alias($alias);

        return $this;
    }

    public function getAlias() {
        return $this->alias;
    }

    /**
     * @return mixed
     */
    public function getTable() {
        return $this->table;
    }

    public function getPrimaryKey() {
        return $this->primaryKey;
    }

    public function getFields() {
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
    public function setRepository(Repository $repository) {
        $this->repository = $repository;

        return $this;
    }

    /**
     * @return Repository
     */
    public function getRepository() {
        return $this->repository;
    }

    /**
     * @param $extension
     *
     * @return $this
     */
    public function addExtension($extension) {
        $this->extensions[] = $extension;

        return $this;
    }

    /*
     * Find out which methods are extensions and init them.
     * */
    /**
     * @return $this
     */
    public function initExtensions() {
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
    public function applyExtensions() {
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
    public function tabelizeRecord(Record $record) {
        $dataArray = $record->__toArray(null, 1, false);
        $keys = [
            $this->table => $this->repository->getCache()->getTableFields($this->table),
        ];

        // Get extensions - which extends table with another table - and their fields.
        foreach (get_class_methods($this) as $method) {
            if ($method != 'getFields' && substr($method, 0, 3) == 'get' && substr($method, -6) == 'Fields') {
                $suffix = $this->{'get' . substr($method, 3, -6) . 'TableSuffix'}();
                if (substr($this->table, strlen($this->table) - strlen($suffix)) != $suffix && $this->repository->getCache()->hasTable($this->table . $suffix)) {
                    /**
                     * @T00D00 ... propery save extensions ...
                     */
                    $keys[$this->table . $suffix] = $this->{$method}();
                }
            }
        }

        // fill array with tables and fields
        $values = [];
        foreach ($keys as $table => $fields) {
            foreach ($fields as $field) {
                if (array_key_exists($field, $dataArray) && $this->repository->getCache()->tableHasField(
                        $table,
                        $field
                    )
                ) {
                    $values[$table][$field] = $dataArray[$field];
                }
            }
        }

        // Get extensions' foreign keys
        foreach (get_class_methods($this) as $method) {
            if ($method != 'getForeignKeys' && substr($method, 0, 3) == 'get' && substr($method, -11) == 'ForeignKeys') {
                $suffix = $this->{'get' . substr($method, 3, -11) . 'TableSuffix'}();
                if ($this->repository->getCache()->hasTable($this->table . $suffix)) {
                    $values[$table] = array_merge($this->{$method}($record), isset($values[$table]) && array_keys($values)[0] == $table ? $values[$table] : []);
                }
            }
        }

        return $values;
    }

    /**
     * @return Record
     */
    public function one() {
        $this->applyExtensions();

        $one = $this->repository->one($this);

        $this->resetQuery();

        return $one;
    }

    /**
     * @return Collection
     */
    public function all() {
        $this->applyExtensions();

        $all = $this->repository->all($this);

        $this->resetQuery();

        return $all;
    }

    /**
     * @return Record
     * @throws Exception
     */
    public function oneOrFail(callable $callback = null) {
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
    public function allOrFail() {
        if ($results = $this->all()) {
            return $results;
        }

        throw new Exception('No records found');
    }

    public function total() {
        return $this->count()
                    ->limit(1)
                    ->all()
                    ->total();
    }

    public function delete(Repository $repository = null) {
        if (!$repository) {
            $repository = $this->getRepository();
        }

        $delete = $this->getQuery()->transformToDelete();

        $prepare = $repository->prepareQuery($delete);
//d($prepare);
        return $repository->executePrepared($prepare);
    }

    public function insert(Repository $repository = null) {
        if (!$this->repository) {
            $repository = $this->getRepository();
        }

        $insert = $this->getQuery()->transformToInsert();

        $prepare = $repository->prepareQuery($insert);

        return $repository->executePrepared($prepare);
    }

}