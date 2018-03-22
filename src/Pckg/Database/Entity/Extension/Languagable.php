<?php namespace Pckg\Database\Entity\Extension;

use Locale;
use Pckg\Database\Entity;
use Pckg\Database\Record;
use Pckg\Database\Relation\HasMany;
use Pckg\Locale\Record\Language;

/**
 * Class Languagable
 *
 * @package Pckg\Database\Entity\Extension
 */
trait Languagable
{

    /**
     * @var array
     */
    protected $languagableFields = [];

    /**
     * @var string
     */
    protected $languagableTableSuffix = '_l11e'; // languagable

    /**
     * @var string
     */
    protected $languagableLanguageField = 'language_id';

    /**
     * @var Language
     */
    protected $languagableLanguage;

    /**
     * @param Locale $locale
     */
    public function injectLanguagableDependencies(Language $language)
    {
        $this->languagableLanguage = $language;
    }

    /**
     *
     */
    public function initLanguagableExtension()
    {
    }

    /**
     * @return string
     */
    public function getLanguagableTableSuffix()
    {
        return $this->languagableTableSuffix;
    }

    /**
     * @return array
     */
    public function getLanguagableFields()
    {
        return $this->languagableFields;
    }

    /**
     * @param Record $record
     *
     * @return array
     */
    public function getLanguagableForeignKeys(Record $record)
    {
        return [
            $this->languagableLanguageField => $this->language->slug,
            $this->primary                  => $record->{$this->primary},
        ];
    }

    /**
     *
     */
    public function languagables()
    {
        $languagableTable = $this->getTable() . $this->languagableTableSuffix;
        $languagableAlias = $this->getAlias()
            ? $this->getAlias() . $this->languagableTableSuffix
            : $languagableTable;
        $repository = $this->getRepository();

        $relation = $this->hasMany(
            (new Entity($repository, $languagableAlias))->setTable($languagableTable)->setAlias($languagableAlias)
        )
                         ->foreignKey('id')
                         ->fill('_languagables');

        return $relation;
    }

    public function forCurrentLanguage()
    {
        $languagableTable = $this->getTable() . $this->languagableTableSuffix;
        $languagableAlias = $this->getAlias()
            ? $this->getAlias() . $this->languagableTableSuffix
            : $languagableTable;

        $languagebleLanguageField = $this->languagableLanguageField;
        $this->joinLanguagables(function(HasMany $languagables) use ($languagableAlias, $languagebleLanguageField) {
            $languagables->where($languagableAlias . '.' . $languagebleLanguageField,
                                 substr(localeManager()->getCurrent(), 0, 2));
        });
    }

    /**
     * @return $this
     */
    public function forCurrentLanguageWhenMultilingual()
    {
        /**
         * Check for multilanguage platforms.
         */
        if (localeManager()->isMultilingual()) {
            $this->forCurrentLanguage();
        }

        return $this;
    }

}