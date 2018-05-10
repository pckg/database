<?php namespace Pckg\Database\Entity\Extension;

use Pckg\Concept\Reflect;
use Pckg\Database\Entity;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Relation\HasMany;
use Pckg\Locale\Lang;
use Pckg\Locale\Lang as LangAdapter;
use Pckg\Locale\LangInterface;

/**
 * Class Translatable
 *
 * @package Pckg\Database\Entity\Extension
 */
trait Translatable
{

    /**
     * @var string
     */
    protected $translatableTableSuffix = '_i18n';

    /**
     * @var string
     */
    protected $translatableLanguageField = 'language_id';

    /**
     * @var LangInterface
     */
    protected $translatableLang;

    /**
     * @return Entity
     */
    public function getTranslatableEntity()
    {
        return (new static($this->getRepository()))
            ->setTable($this->table . $this->getTranslatableTableSuffix())
            ->setAlias($this->table . $this->getTranslatableTableSuffix());
    }

    /**
     *
     */
    public function checkTranslatableDependencies()
    {
        /**
         * @T00D00 - check if we're able to resolve AuthInterface implementation.
         *         If not, use default.
         */
        if (!Reflect::canResolve(LangInterface::class)) {
            context()->bind(LangInterface::class, new LangAdapter());
        }
    }

    /**
     * @param LangInterface $lang
     */
    public function injectTranslatableDependencies(LangInterface $lang)
    {
        $this->translatableLang = $lang;
    }

    /**
     *
     */
    public function initTranslatableExtension()
    {
    }

    /**
     * @return array
     */
    public function getTranslatableFields()
    {
        return $this->getRepository()->getCache()->getTableFields($this->table . $this->translatableTableSuffix);
    }

    /**
     * @param Record $record
     *
     * @return array
     */
    public function getTranslatableForeignKeys(Record $record)
    {
        return [
            $this->primaryKey                => $record->{$this->primaryKey},
            $this->translatableLanguageField => $this->translatableLang->langId(),
        ];
    }

    /**
     * @param callable|null $callable
     *
     * @return mixed
     */
    public function joinTranslations(callable $callable = null)
    {
        return $this->join($this->translations($callable));
    }

    /**
     * @return mixed
     */
    public function translations(callable $callable = null)
    {
        $relation = $this->allTranslations($callable);

        if ($callable) {
            $query = $relation->getRightEntity()->getQuery();

            Reflect::call(
                $callable,
                [
                    $query,
                    $relation,
                    $this,
                ]
            );

            $this->addTranslatableConditionIfNot($relation);
        } else {
            $this->addTranslatableCondition($relation);
        }

        return $relation;
    }

    /**
     * @return mixed
     */
    public function allTranslations(callable $callable = null)
    {
        $translaTable = $this->getTable() . $this->getTranslatableTableSuffix;
        $translaTableAlias = $this->getAlias()
            ? $this->getAlias() . $this->getTranslatableTableSuffix
            : $translaTable;
        $repository = $this->getRepository();

        $relation = $this->hasMany(
            (new Entity($repository, $translaTableAlias))->setTable($translaTable)->setAlias($translaTableAlias)
        )
                         ->foreignKey('id')
                         ->fill('_translations')
                         ->addSelect(['`' . $translaTableAlias . '`.*'])
                         ->leftJoin();

        return $relation;
    }

    /**
     * @param HasMany $relation
     */
    private function addTranslatableConditionIfNot(HasMany $relation)
    {
        $foundLanguageCondition = false;
        $query = $relation->getQuery();
        foreach ($query->getWhere() as $where) {
            foreach ($where->getChildren() as $key => $child) {
                if (strpos($key, $this->translatableLanguageField)) {
                    $foundLanguageCondition = true;
                }
            }
        }

        if (!$foundLanguageCondition) {
            $this->addTranslatableCondition($relation);
        }
    }

    /**
     * @param HasMany $relation
     */
    private function addTranslatableCondition(HasMany $relation)
    {
        $translaTable = $this->getTable() . $this->getTranslatableTableSuffix();
        $translaTableAlias = $this->getAlias()
            ? $this->getAlias() . $this->getTranslatableTableSuffix()
            : $translaTable;

        $relation->where(
            '`' . $translaTableAlias . '`.`' . $this->translatableLanguageField . '`',
            $this->translatableLang->langId()
        );
    }

    /**
     * @return string
     */
    public function getTranslatableTableSuffix()
    {
        return $this->translatableTableSuffix;
    }

    /**
     * @return mixed
     */
    public function withTranslation()
    {
        return $this->withTranslations(
            function(Query $query) {
                $query->where($this->translatableLanguageField, $this->translatableLang->langId());
            }
        );
    }

    /**
     * @param callable|null $callable
     *
     * @return mixed
     */
    public function withTranslations(callable $callable = null)
    {
        return $this->with($this->translations($callable));
    }

    /**
     * @param callable|null $callable
     *
     * @return mixed
     */
    public function withAllTranslations(callable $callable = null)
    {
        return $this->with($this->allTranslations($callable));
    }

    /**
     * @param callable|null $callable
     *
     * @return mixed
     */
    public function joinTranslation(callable $callable = null)
    {
        return $this->join($this->translations($callable))
                    ->prependSelect([$this->getTable() . $this->translatableTableSuffix . '.*']);
    }

    /**
     * @param callable|null $callable
     *
     * @return $this
     */
    public function joinFallbackTranslation(callable $callable = null)
    {
        if ($this->translatableLang->langId() == 'en') {
            return $this;
        }

        $selects = [];
        $translaTable = $this->getTable() . $this->translatableTableSuffix;
        $fields = $this->getRepository()->getCache()->getTableFields($translaTable);
        $translatableKey = '`' . $translaTable . '`.`id`';
        foreach ($fields as $field) {
            if (in_array($field, ['id', $this->translatableLanguageField])) {
                continue;
            }

            $translatableField = '`' . $translaTable . '`.`' . $field . '`';
            $fallbackField = '`' . $translaTable . '_f`.`' . $field . '`';
            $selects[] = 'IF(' . $translatableKey . ', ' . $translatableField . ', ' . $fallbackField . ') AS `' .
                $field . '`';
        }

        $relation = $this->join($this->translationsFallback($callable));

        $relation->getQuery()->addSelect($selects);

        return $relation;
    }

    /**
     * @return mixed
     */
    public function translationsFallback(callable $callable = null)
    {
        $translaTable = $this->getTable() . $this->getTranslatableTableSuffix;
        $repository = $this->getRepository();

        $relation = $this->hasMany(
            (new Entity($repository))->setTable($translaTable)->setAlias($translaTable . '_f')
        )
                         ->foreignKey('id')
                         ->fill('_translations')
                         ->addSelect(['`' . $translaTable . '`.*'])
                         ->leftJoin();

        if ($callable) {
            $query = $relation->getRightEntity()->getQuery();

            Reflect::call(
                $callable,
                [
                    $query,
                    $relation,
                    $this,
                ]
            );

            $this->addTranslatableFallbackConditionIfNot($relation);
        } else {
            $this->addTranslatableFallbackCondition($relation);
        }

        return $relation;
    }

    /**
     * @param HasMany $relation
     */
    private function addTranslatableFallbackConditionIfNot(HasMany $relation)
    {
        $foundLanguageCondition = false;
        $query = $relation->getQuery();
        foreach ($query->getWhere() as $where) {
            foreach ($where->getChildren() as $key => $child) {
                if (strpos($key, $this->translatableLanguageField)) {
                    $foundLanguageCondition = true;
                }
            }
        }

        if (!$foundLanguageCondition) {
            $this->addTranslatableFallbackCondition($relation);
        }
    }

    /**
     * @param HasMany $relation
     */
    private function addTranslatableFallbackCondition(HasMany $relation)
    {
        $translaTable = $this->getTable() . $this->getTranslatableTableSuffix();
        $translaTableAlias = $this->getAlias()
            ? $this->getAlias() . $this->getTranslatableTableSuffix()
            : $translaTable;

        $relation->where(
            '`' . $translaTableAlias . '_f`.`' . $this->translatableLanguageField . '`',
            'en'
        );
    }

    /**
     * @return LangInterface
     */
    public function getTranslatableLang()
    {
        return $this->translatableLang;
    }

    /**
     * @param LangInterface|string $lang
     *
     * @return $this
     */
    public function setTranslatableLang($lang)
    {
        if (is_string($lang)) {
            $lang = new Lang($lang);
        }

        $this->translatableLang = $lang;

        return $this;
    }

    /**
     * @param Record $record
     * @param        $key
     *
     * @return null
     */
    public function __getTranslatableExtension(Record $record, $key)
    {
        /**
         * Check that translatable field exists in database.
         */
        if (!$this->getRepository()->getCache()->tableHasField(
            $this->getTable() . $this->getTranslatableTableSuffix(),
            $key
        )
        ) {
            return null;
        }

        if (!$record->relationExists('_translations')) {
            /**
             * Fetch translations.
             */
            $record->withTranslations();
        }

        /**
         * Translations were fetched by join.
         */
        foreach ($record->getRelation('_translations') as $translation) {
            if ($translation->keyExists($key)) {
                return $translation->{$key};
            }
        }
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function __issetTranslatableExtension($key)
    {
        $table = $this->getTable() . $this->translatableTableSuffix;

        if ($this->getRepository()->getCache()->tableHasField($table, $key)) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function isTranslatable()
    {
        return $this->getRepository()->getCache()->hasTable($this->table . $this->translatableTableSuffix);
    }

    /**
     * @return bool
     */
    public function isTranslated()
    {
        foreach ($this->getQuery()->getJoin() as $join) {
            if ($this->getAlias()) {
                if (is_string($join) && strpos($join, '`' . $this->getAlias() . '_i18n`')) {
                    return true;
                }
            } else {
                if (is_string($join) && strpos($join, '`' . $this->getTable() . '_i18n`')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return $this
     */
    public function useTranslatableTable()
    {
        if (strpos($this->table, $this->getTranslatableTableSuffix()) === false) {
            $this->table = $this->getTranslatableTable();
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getTranslatableTable()
    {
        return $this->getTable() . $this->getTranslatableTableSuffix();
    }

}