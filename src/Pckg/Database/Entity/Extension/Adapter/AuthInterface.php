<?php

namespace Pckg\Database\Entity\Extension\Adapter;

/**
 * Interface Auth
 *
 * @package Pckg\Database\Entity\Extension\Adapter
 *          Interface for Permissionable Extension
 */
interface AuthInterface
{

    /**
     * @return integer|null
     */
    public function groupId();

    /**
     * @return integer|null
     */
    public function userId();

}