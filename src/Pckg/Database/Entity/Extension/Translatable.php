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

    protected $translatableFallbackLang;

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
            $this->primaryKey                => $record->{$this->primaryKey},
            $this->translatableLanguageField => $this->translatableLang->langId(),
        ];
    }

    /**
     * @return mixed
     */
    public function translations(callable $callable = null)
    {
        $translaTable = $this->getTable() . $this->getTranslatableTableSuffix;
        $repository = $this->getRepository();

        $relation = $this->hasMany((new Entity($repository))->setTable($translaTable))
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

            $this->addTranslatableConditionIfNot($relation);

        } else {
            $this->addTranslatableCondition($relation);

        }

        return $relation;
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

        $relation->where(
            '`' . $translaTable . '`.`' . $this->translatableLanguageField . '`',
            $this->translatableLang->langId()
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

    public function setTranslatableLang(Lang $lang)
    {
        $this->translatableLang = $lang;

        return $this;
    }

    public function setFallbackLang(Lang $lang)
    {
        $this->translatableFallbackLang = $lang;

        return $this;
    }

    public function __getTranslatableExtension(Record $record, $key)
    {
        if ($record->relationExists('_translations')) {
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