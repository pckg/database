<?php

namespace Pckg\Database\Entity\Extension;

use Pckg\Concept\Reflect;
use Pckg\Database\Entity;
use Pckg\Database\Entity\Extension\Adapter\Lang;
use Pckg\Database\Query;
use Pckg\Database\Record;
use Pckg\Database\Relation\HasMany;

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
     * @var Lang
     */
    protected $translatableLang;

    /**
     * @param Lang $lang
     */
    public function injectTranslatableDependencies(Lang $lang)
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
     * @return string
     */
    public function getTranslatableTableSuffix()
    {
        return $this->translatableTableSuffix;
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
            $this->primaryKey => $record->{$this->primaryKey},
            $this->translatableLanguageField => $this->translatableLang->langId(),
        ];
    }

    /**
     * @return mixed
     */
    public function translations(callable $callable = null)
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

    public function withTranslations(callable $callable = null)
    {
        return $this->with($this->translations($callable));
    }

    public function joinTranslations(callable $callable = null)
    {
        return $this->join($this->translations($callable));
    }

    public function withTranslation()
    {
        return $this->withTranslations(
            function(Query $query) {
                $query->where($this->translatableLanguageField, $this->translatableLang->langId());
            }
        );
    }

    public function joinTranslation(callable $callable = null)
    {
        return $this->join($this->translations($callable))
                    ->prependSelect([$this->getTable() . $this->translatableTableSuffix . '.*']);
    }

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
            $selects[] = 'IF(' . $translatableKey . ', ' . $translatableField . ', ' . $fallbackField . ') AS `' . $field . '`';
        }

        $relation = $this->join($this->translationsFallback($callable));

        $relation->getQuery()->addSelect($selects);

        return $relation;
    }

    public function setTranslatableLang(Lang $lang)
    {
        $this->translatableLang = $lang;

        return $this;
    }

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

            /**
             * Translations were fetched by join.
             */
            foreach ($record->getRelation('_translations') as $translation) {
                if ($translation->keyExists($key)) {
                    return $translation->{$key};
                }
            }
        }
    }

    public function __issetTranslatableExtension($key)
    {
        $table = $this->getTable() . $this->translatableTableSuffix;

        if ($this->getRepository()->getCache()->tableHasField($table, $key)) {
            return true;
        }

        return false;
    }

}