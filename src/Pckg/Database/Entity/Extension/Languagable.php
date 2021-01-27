<?php namespace Pckg\Database\Entity\Extension;

use Pckg\Concept\Reflect;
use Pckg\Database\Entity;
use Pckg\Database\Record;
use Pckg\Database\Relation\HasMany;
use Pckg\Locale\Lang as LangAdapter;
use Pckg\Locale\LangInterface;

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
     * @var LangInterface
     */
    protected $languagableLanguage;

    /**
     *
     */
    public function checkLanguagableDependencies()
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
    public function injectLanguagableDependencies(LangInterface $lang)
    {
        $this->languagableLanguage = $lang;
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
        return $this->getRepository()->getCache()->getTableFields($this->table . $this->languagableTableSuffix);
    }

    /**
     * @param Record $record
     *
     * @return array
     */
    public function getLanguagableForeignKeys(Record $record)
    {
        return [
            $this->primaryKey               => $record->{$this->primaryKey},
            $this->languagableLanguageField => $this->languagableLanguage->langId(),
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
        if (config('pckg.database.extension.languagable.active', false) && localeManager()->isMultilingual()) {
            $this->forCurrentLanguage();
        }

        return $this;
    }

}