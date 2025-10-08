<?php

namespace App\Entity;

use InvalidArgumentException;

abstract class BaseEntity
{
    /**
     * Populate this class instance with data from array.
     *
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $data): static
    {
        $class = new static();

        foreach ($data as $key => $value) {
            if (! property_exists($class, $key)) {
                $className = $class::class;

                throw new InvalidArgumentException("Property '{$key}' is not present in class '{$className}'!");
            }

            $class->$key = $value;
        }

        return $class;
    }
}
