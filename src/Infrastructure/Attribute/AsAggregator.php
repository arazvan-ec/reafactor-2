<?php

declare(strict_types=1);

namespace App\Infrastructure\Attribute;

/**
 * Attribute to mark a class as an Aggregator for auto-registration.
 *
 * Classes marked with this attribute will be automatically registered
 * in the AggregatorRegistry via the AggregatorCompilerPass.
 *
 * @example
 * #[AsAggregator(name: 'tag', priority: 70, timeout: 3000)]
 * final class TagAggregator implements AsyncAggregatorInterface
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsAggregator
{
    /**
     * @param string $name Unique identifier for this aggregator
     * @param int $priority Execution priority (higher = earlier). Default: 50
     * @param int $timeout Timeout in milliseconds for async aggregators. Default: 5000
     * @param string[] $dependencies Names of aggregators this depends on
     */
    public function __construct(
        public readonly string $name,
        public readonly int $priority = 50,
        public readonly int $timeout = 5000,
        public readonly array $dependencies = []
    ) {
    }
}
