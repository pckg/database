<?php

namespace Pckg\Database\Field;

/**
 * Interface Stringifiable
 *
 * @package Pckg\Database\Field
 */
interface Stringifiable
{

    /**
     * Normalize value to the selected type.
     *
     * @param mixed $value
     * @return mixed
     */
    public function validateValue($value);

    /**
     * Prepare data for JSON serialization.
     * Either to __toString or __toArray?
     *
     * @return string|object|array|null|integer|float
     */
    public function jsonSerialize();

    /**
     * Return field object (datetime, array, object, ...).
     *
     * @return mixed
     */
    public function encapsulated();

    /**
     * Return field object (datetime, array, object, ...).
     *
     * @return mixed
     */
    public function decapsulate();

    /**
     * @return string
     */
    public function getPlaceholder();

    /**
     * @return string|array
     */
    public function getBind();

    /**
     * Convert value to string.
     *
     * @return string
     */
    public function __toString();
}
