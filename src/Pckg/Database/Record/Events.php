<?php namespace Pckg\Database\Record;

trait Events
{

    public function events()
    {
        return [];
    }

    public function trigger($events, $args = [])
    {
        $args[] = $this;
        if (!is_array($events)) {
            $events = [$events];
        }
        foreach ($events as $event) {
            dispatcher()->trigger(static::class . '.' . $event, $args);
        }

        return $this;
    }

}