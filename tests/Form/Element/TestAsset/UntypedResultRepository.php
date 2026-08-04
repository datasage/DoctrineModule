<?php

declare(strict_types=1);

namespace DoctrineModuleTest\Form\Element\TestAsset;

use Doctrine\Persistence\ObjectRepository;

/**
 * Repository exposing a find method whose return value is unconstrained.
 *
 * Proxy's "find_method" option calls arbitrary repository methods, so it has to
 * guard against results that are neither an array nor Traversable. findAll() is
 * declared as returning an array by doctrine/persistence 4, so the guard can
 * only be exercised through a method that accepts anything. Only a new method
 * is added here, so this stays valid against persistence 3 and 4.
 *
 * @template-extends ObjectRepository<object>
 */
interface UntypedResultRepository extends ObjectRepository
{
    public function findUntypedResult(): mixed;
}
