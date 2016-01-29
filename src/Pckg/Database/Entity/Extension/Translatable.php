<?php

namespace Pckg\Database\Entity\Extension;

use Pckg\Database\Entity;
use Pckg\Database\Entity\Extension\Adapter\Lang;
use Pckg\Database\Record;

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
        return $this->translatableFields
            ? $this->translatableFields
            : $this->getRepository()->getCache()->getTableFields($this->table);
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
    public function translations()
    {
        return $this->hasMany((new TestTranslatableEntity($this->getRepository()))->setTable($this->getTable() . $this->getTranslatableTableSuffix()))
            ->primaryKey('id')
            ->foreignKey('id')
            ->primaryCollectionKey('_translatee')
            ->foreignCollectionKey('_translations');
    }

    public function withTranslations()
    {
        return $this->with($this->translations());
    }

    public function joinTranslations()
    {
        return $this->join($this->translations());
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

class TestTranslatableEntity extends Entity {

}