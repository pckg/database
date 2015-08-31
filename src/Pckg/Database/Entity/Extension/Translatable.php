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
     * @return array
     */
    public function getTranslatableForeignKeys(Record $record)
    {
        return [
            $this->primaryKey => $record->{$this->primaryKey},
            $this->translatableLanguageField => $this->translatableLang->getCurrent(),
        ];
    }

    /**
     * @return mixed
     */
    public function translations()
    {
        return $this->hasMany((new Entity($this->getRepository()))->setTable($this->getTable() . $this->getTranslatableTableSuffix()));
    }

}