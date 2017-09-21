<?php

namespace Services\Crawler\Messages;

abstract class AbstractMessage
{
    /**
     * @param $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    /**
     * Convert the object to an array
     *
     * @return array
     */
    abstract public function toArray();
}
