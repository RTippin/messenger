<?php

namespace RTippin\Messenger\Traits;

use ReflectionClass;
use ReflectionException;

trait ChecksReflection
{
    /**
     * @param string $abstract
     * @param string $contract
     * @return bool
     */
    public function checkImplementsInterface(string $abstract, string $contract): bool
    {
        try {
            return (new ReflectionClass($abstract))->implementsInterface($contract);
        } catch (ReflectionException $e) {
            //skip
        }

        return false;
    }

    /**
     * @param string $abstract
     * @param string $contract
     * @return bool
     */
    public function checkIsSubclassOf(string $abstract, string $contract): bool
    {
        try {
            return (new ReflectionClass($abstract))->isSubclassOf($contract);
        } catch (ReflectionException $e) {
            //skip
        }

        return false;
    }
}
