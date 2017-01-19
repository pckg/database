<?php namespace Pckg\Database\Record;

trait Events
{
    
    public function events()
    {
        return [];
    }

    public function trigger($event, $args = [])
    {
        $args[] = $this;
        $this->trigger(static::class . '.' . $event, $args);

        return $this;
    }

}