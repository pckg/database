<?php

namespace Pckg\Database\Record;

use Pckg\Database\Entity;
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
     * @return array
     */
    public function eventHandlers()
    {
        if (method_exists($this, 'collectTimeableEvents')) {
            return $this->collectTimeableEvents();
        }

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
        if (!is_array($args)) {
            $args = [$args];
        }
        $isRecord = $this instanceof Record;
        if ($isRecord) {
            $args[] = $this;
        }
        if (!is_array($events)) {
            $events = [$events];
        }

        $handlers = $this->eventHandlers();
        foreach ($events as $event) {
            if (isset($handlers[$event])) {
                $handlers[$event]($isRecord ? $this : $args[0]);
            }
            dispatcher()->trigger(static::class . '.' . $event, $args);
            dispatcher()->trigger($isRecord ? Record::class : Entity::class . '.' . $event, $args);
        }

        return $this;
    }
}
