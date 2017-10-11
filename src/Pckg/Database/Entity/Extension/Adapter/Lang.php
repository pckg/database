<?php namespace Pckg\Database\Entity\Extension\Adapter;

use Pckg\Locale\LangInterface;

/**
 * Class Lang
 *
 * Implementation for interface of language usages in database packet.
 *
 * @package Pckg\Database\Entity\Extension\Adapter
 */
class Lang implements LangInterface
{

    /**
     * @return string|null
     */
    public function langId()
    {
        return 'en';
    }

}