<?php namespace Pckg\Database;

use Exception;
use Pckg\Concept\Reflect;
use Pckg\Database\Entity\Extension\Deletable;
use Pckg\Database\Entity\Extension\Paginatable;
use Pckg\Database\Entity\Extension\Permissionable;
use Pckg\Database\Entity\Extension\Translatable;
use Pckg\Database\Helper\Convention;
use Pckg\Database\Query\Helper\QueryBuilder;
use Pckg\Database\Query\Helper\With;
use Pckg\Database\Record as DatabaseRecord;
use Pckg\Database\Relation\Helper\RelationMethods;
use Pckg\Database\Repository\PDO;
use Pckg\Database\Repository\RepositoryFactory;

/**
 * Class Entity
 *
 * @package Pckg\Database
 */
class Entity
{

    use RelationMethods, QueryBuilder, With;
    use Permissionable, Deletable, Translatable, Paginatable;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var null
     */
    protected $alias;

    /**
     * @var
     */
    protected $record = Record::class;

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var
     */
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
     * @var array
     */
    protected $setData = [];

    /**
     * @param Repository $repository
     */
    public function __construct(Repository $repository = null, $alias = null, $boot = true)
    {
        if (!$repository) {
            $repository = RepositoryFactory::getOrCreateRepository($this->repositoryName);
        }

        $this->alias = $alias;
        $this->repository = $repository;

        $this->guessDefaults();

        if ($boot) {
            $this->initExtensions();
            $this->preboot();
            $this->boot();
        }
    }

    /**
     *
     */
    protected function guessDefaults()
    {
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

    /**
     * @return $this
     */
    public function initExtensions()
    {
        foreach (get_class_methods($this) as $method) {
            if (substr($method, 0, 4) == 'init' && substr($method, -9) == 'Extension') {
                Reflect::method($this, $method);
            } else if (substr($method, 0, 6) == 'inject' && substr($method, -12) == 'Dependencies') {
                $extension = substr($method, 6, -12);
                if (method_exists($this, 'check' . $extension . 'Dependencies')) {
                    Reflect::method($this, 'check' . $extension . 'Dependencies');
                }
                Reflect::method($this, $method);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function preboot()
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function boot()
    {
        return $this;
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
     *
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function extendedKey($key)
    {
        $cache = $this->repository->getCache();
        if (!$cache->tableHasField($this->table, $key)) {
            if ($cache->tableHasField($this->table . '_i18n', $key)) {
                return '`' . $this->table . '_i18n`.`' . $key . '`';
            }
        }

        return $key;
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
     * @param $class
     *
     * @return $this
     */
    public function setRecordClass($class)
    {
        $this->record = $class;

        return $this;
    }

    /**
     * @return null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param $alias
     *
     * @return $this
     */
    public function setAlias($alias)
    {
        /**
         * @T00D00
         */
        $this->alias = $alias;
        $this->getQuery()->alias($alias);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
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
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields ?: $this->getRepository()->getCache()->getTableFields($this->table);
    }

    /**
     * @return Repository|PDO
     */
    public function getRepository()
    {
        return $this->repository;
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
     * @param $repository
     *
     * @return Repository|PDO
     */
    public function getRepositoryIfEmpty($repository)
    {
        if ($repository) {
            return $repository;
        }

        return $this->getRepository();
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

    /**
     * Transforms Record to two-dimensional array of tables and fields.
     *
     * @param Record $record
     *
     * @return array
     */
    public function tabelizeRecord(DatabaseRecord $record, $onlyDirty = false)
    {
        $dataArray = $record->__toArray(null, 2, false);
        $extensionArray = [];

        if ($onlyDirty) {
            foreach ($dataArray as $key => $val) {
                /**
                 * Leave only dirty and primary keys.
                 *
                 * @T00D00 - What if primary id has changed?
                 */
                if ($record->isDirty($key) || $key == 'id') {
                    continue;
                }

                unset($dataArray[$key]);
            }
        }

        /**
         * Holds all available fields in database table cache.
         */
        $keys = [
            $this->table => $this->repository->getCache()->getTableFields($this->table),
        ];

        foreach (get_class_methods($this) as $method) {
            /**
             * Get extension's fields.
             */
            if ($method != 'getFields' && substr($method, 0, 3) == 'get' && substr($method, -6) == 'Fields') {
                $suffix = $this->{'get' . substr($method, 3, -6) . 'TableSuffix'}();
                if (substr($this->table, strlen($this->table) - strlen($suffix)) != $suffix
                    && $this->repository->getCache()->hasTable($this->table . $suffix)
                ) {
                    $keys[$this->table . $suffix] = $this->{$method}();
                }
            }

            /**
             * Get extension's foreign key values.
             */
            if ($method != 'getForeignKeys' && substr($method, 0, 3) == 'get' && substr(
                                                                                     $method,
                                                                                     -11
                                                                                 ) == 'ForeignKeys'
            ) {
                $suffix = $this->{'get' . substr($method, 3, -11) . 'TableSuffix'}();
                if (substr($this->table, strlen($this->table) - strlen($suffix)) != $suffix
                    && $this->repository->getCache()->hasTable($this->table . $suffix)
                ) {
                    // base table
                    $extensionArray[$this->table . $suffix] = $this->{$method}($record);
                } elseif (strrpos($this->table, $suffix) == strlen($this->table) - strlen($suffix)
                          && $this->repository->getCache()->hasTable($this->table)
                ) {
                    // extendee table
                    $extensionArray[$this->table] = $this->{$method}($record);
                }
            }
        }

        // fill array with tables and fields
        $values = [];
        foreach ($keys as $table => $fields) {
            $values[$table] = [];
            foreach ($fields as $field) {
                /**
                 * Add value if field exists in data array and repository has that field.
                 */
                if ($this->repository->getCache()->tableHasField($table, $field)
                ) {
                    if (isset($extensionArray[$table]) && array_key_exists($field, $extensionArray[$table])) {
                        $values[$table][$field] = $extensionArray[$table][$field];
                    } elseif (array_key_exists($field, $dataArray)) {
                        $values[$table][$field] = $dataArray[$field];
                    }
                }
            }
        }

        return $values;
    }

    /*
     * Find out which methods are extensions and init them.
     * */

    /**
     * @return Record
     */
    public function oneOrNew()
    {
        $this->applyExtensions();

        $binds = $this->query->getBinds();

        $record = $this->repository->one($this);

        if (!$record) {
            $data = [];
            $where = $this->query->getWhere();
            foreach ($where->getChildren() as $i => $sql) {
                $start = strpos($sql, '.') ?? null;
                $start = strpos($sql, '`', $start) ?? null;
                $end = strpos($sql, '`', $start + 1) ?? null;
                $length = $end ? $end - $start - 1 : null;
                $field = substr($sql, $start + 1, $length);
                $data[$field] = $binds[$i];
            }

            $record = $this->getRecord($data);
        }

        $this->resetQuery();

        $this->resetRelations();

        return $record;
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
     * @return \Pckg\Database\Record
     */
    public function getRecord($data = [])
    {
        $class = $this->getRecordClass();

        $record = new $class($data);
        $record->setEntity($this);
        $record->setEntityClass(get_class($this));

        return $record;
    }

    /**
     * @return mixed
     */
    public function getRecordClass()
    {
        return $this->record;
    }

    /**
     * @return Record|mixed
     */
    public function oneAnd($callback)
    {
        $one = $this->one();

        return $callback($one);
    }

    /**
     * @return Record|mixed
     */
    public function one()
    {
        $this->applyExtensions();

        $repository = $this->getRepository()->aliased('read');

        $one = $repository->one($this);

        $this->resetQuery();

        $this->resetRelations();

        return $one;
    }

    /**
     * @return Record|mixed
     */
    public function oneAndIf($callback)
    {
        $one = $this->one();

        return $one
            ? $callback($one)
            : $one;
    }

    /**
     * @param callable $callback
     *
     * @return mixed
     */
    public function allAnd(callable $callback)
    {
        return $callback($this->all());
    }

    /**
     * @return Collection
     */
    public function all()
    {
        $this->applyExtensions();

        $repository = $this->getRepository()->aliased('read');

        $all = $repository->all($this);

        $this->resetQuery();

        $this->resetRelations();

        return $all;
    }

    /**
     * @param callable $callback
     *
     * @return $this|\Pckg\Collection\Each
     */
    public function allAndEach(callable $callback)
    {
        return $this->all()->each($callback);
    }

    /**
     * @return Record|mixed
     * @throws Exception
     */
    public function oneOrFail(callable $callback = null)
    {
        if (!$callback) {
            $callback = function() {
                throw new Exception('No record ' . $this->getRecordClass() . ' / ' . static::class . ' found');
            };
        }

        return $this->oneOr($callback);
    }

    /**
     * @return Record
     * @throws Exception
     */
    public function oneOr(callable $callback)
    {
        if ($result = $this->one()) {
            return $result;
        }

        return $callback();
    }

    /**
     * @throws Exception
     * @return Collection
     * */
    public function allOrFail(callable $callback = null)
    {
        if (($results = $this->all()) && $results->count()) {
            return $results;
        }

        if ($callback) {
            return $callback();
        }

        throw new Exception('No records found');
    }

    /**
     * @return int
     */
    public function total()
    {
        return $this->count()
                    ->limit(1)
                    ->all()
                    ->total();
    }

    /**
     * @param Repository|null $repository
     *
     * @return mixed
     */
    public function delete(Repository $repository = null)
    {
        if (!$repository) {
            $repository = $this->getRepository();
        }

        $repository = $repository->aliased('write');

        $delete = $this->getQuery()->transformToDelete();

        $prepare = $repository->prepareQuery($delete);

        return $repository->executePrepared($prepare);
    }

    /**
     * @param Repository|null $repository
     *
     * @return mixed
     */
    public function insert(Repository $repository = null)
    {
        if (!$repository) {
            $repository = $this->getRepository();
        }

        $repository = $repository->aliased('write');

        $insert = $this->getQuery()->transformToInsert();

        $prepare = $repository->prepareQuery($insert);

        return $repository->executePrepared($prepare);
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function set($data = [])
    {
        $this->setData = $data;

        return $this;
    }

    /**
     * @param Repository|null $repository
     *
     * @return mixed
     */
    public function update(Repository $repository = null)
    {
        if (!$repository) {
            $repository = $this->getRepository();
        }

        $repository = $repository->aliased('write');

        $update = $this->getQuery()->transformToUpdate();

        $update->setSet($this->setData);

        $prepare = $repository->prepareQuery($update);

        return $repository->executePrepared($prepare);
    }

    /**
     * @param Record $record
     *
     * @return Record
     */
    public function transformRecordToEntities(Record $record)
    {
        if (get_class($record) == $this->record) {
            return $record;
        }

        $newRecord = $this->getRecord();
        $newRecord->setData($record->data());
        $newRecord->setOriginalFromData();

        if ($record->isSaved()) {
            $newRecord->setSaved();
        }

        return $newRecord;
    }

    /**
     * @param $field
     *
     * @return bool
     */
    public function hasField($field)
    {
        return $this->getRepository()->getCache()->tableHasField($this->table, $field);
    }

}