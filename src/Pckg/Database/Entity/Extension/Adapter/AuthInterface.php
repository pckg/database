<?php namespace Pckg\Database\Entity\Extension\Adapter;

/**
 * Interface AuthInterface
 *
 * Interface for authentication usages in database packet.
 *
 * @package Pckg\Database\Entity\Extension\Adapter
 */
interface AuthInterface
{

    /**
     * @return null|string|integer
     *
     * Return group id of currently logged in user.
     */
    public function groupId();

    /**
     * @return null|string|integer
     *
     * Return user id of currently logged in user.
     */
    public function userId();

}