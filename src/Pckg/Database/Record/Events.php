<?php

namespace Pckg\Database\Record;

use Pckg\Database\Record;

/**
 * Class Events
 *
 * @package Pckg\Database\Record
 */
trait Events
{

    /**
     * @return array
     */
    public function events()
    {
        return [];
    }

    /**
     * @param       $events
     * @param array $args
     *
     * @return $this
     */
    public function trigger($events, $args = [])
    {
        $args[] = $this;
        if (!is_array($events)) {
            $events = [$events];
        }

        foreach ($events as $event) {
            dispatcher()->trigger(static::class . '.' . $event, $args);
            dispatcher()->trigger(Record::class . '.' . $event, $args);
        }

        return $this;
    }
}
