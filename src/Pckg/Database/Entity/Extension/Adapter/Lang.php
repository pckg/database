<?php namespace Pckg\Database\Entity\Extension\Adapter;

use Pckg\Locale\LangInterface;

class Lang implements LangInterface
{

    public function langId()
    {
        return 'en';
    }

}