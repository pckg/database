<?php

namespace LFW\Database\Entity\Extension;

use LFW\Database\Record;
use Locale;

/**
 * Class Localizable
 * @package LFW\Database\Entity\Extension
 */
trait Localizable
{

    /**
     * @var array
     */
    protected $localizableFields = [];

    /**
     * @var string
     */
    protected $localizableTableSuffix = '_l10n';

    /**
     * @var string
     */
    protected $localizableLocaleField = 'locale_id';

    /**
     * @var
     */
    protected $localizableLocale;

    /**
     * @param Locale $locale
     */
    public function injectTranslatableDependencies(Locale $locale)
    {
        $this->localizableLocale = $locale;
    }

    /**
     *
     */
    public function initLocalizableExtension()
    {
    }

    /**
     * @return string
     */
    public function getLocalizableTableSuffix()
    {
        return $this->localizableTableSuffix;
    }

    /**
     * @return array
     */
    public function getLocalizableFields()
    {
        return $this->localizableFields;
    }

    /**
     * @param Record $record
     * @return array
     */
    public function getLocalizableForeignKeys(Record $record)
    {
        return [
            $this->localizableLocaleField => $this->localizableLocale->getCode(),
            $this->primary => $record->{$this->primary}
        ];
    }

    /**
     * @return mixed
     */
    public function localizations()
    {
        return $this->hasMany($this->table . $this->localizableTableSuffix);
    }

}