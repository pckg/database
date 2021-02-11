<?php

namespace Pckg\Database\Entity\Extension\Adapter;

/**
 * Class Auth
 *
 * Implementation of interface for authentication usages in database packet.
 *
 * @package Pckg\Database\Entity\Extension\Adapter
 */
class Auth implements AuthInterface
{

    /**
     * @return null|string|integer
     *
     * Return group id of currently logged in user.
     */
    public function groupId()
    {
        return null;
    }

    /**
     * @return null|string|integer
     *
     * Return user id of currently logged in user.
     */
    public function userId()
    {
        return null;
    }
}
