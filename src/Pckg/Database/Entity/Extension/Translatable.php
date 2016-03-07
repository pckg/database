<?php

namespace Pckg\Database\Entity\Extension;

use Pckg\Database\Entity;
use Pckg\Database\Entity\Extension\Adapter\Lang;
use Pckg\Database\Query;
use Pckg\Database\Query\Helper\With;
use Pckg\Database\Record;
use Pckg\Database\Relation\HasMany;

/**
 * Class Translatable
 * @package Pckg\Database\Entity\Extension
 */
trait Translatable
{

    /**
     * @var array
     */
    // protected $translatableFields = [];

    /**
     * @var string
     */
    protected $translatableTableSuffix = '_i18n';

    /**
     * @var string
     */
    protected $translatableLanguageField = 'language_id';

    /**
     * @var
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
        $relation = $this->hasMany((new Entity($this->getRepository()))->setTable($this->getTable() . $this->getTranslatableTableSuffix()))
            ->primaryKey('id')
            ->foreignKey('id')
            ->primaryCollectionKey('_translatee')
            ->foreignCollectionKey('_translations');

        if ($callable) {
            $callable($relation->getRightEntity()->getQuery());
        }

        return $relation;
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
        return $this->withTranslations(function (Query $query) {
            $query->where($this->translatableLanguageField, $this->translatableLang->langId());
        });
    }

    public function joinTranslation()
    {
        return $this->join($this->translations());
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
        if ($record->keyExists('_translations')) {
            foreach ($record->getValue('_translations') as $translation) {
                if ($translation->keyExists($key)) {
                    return $translation->{$key};
                }
            }
        }
    }

}