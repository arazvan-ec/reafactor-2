<?php

declare(strict_types=1);

namespace App\Application\Aggregator;

use App\Domain\Aggregator\Contract\AggregatorInterface;
use App\Domain\Aggregator\Exception\AggregatorNotFoundException;
use App\Domain\Aggregator\Exception\CircularDependencyException;

/**
 * Resolves aggregator dependencies and creates execution batches.
 *
 * Uses Kahn's algorithm for topological sorting with batch grouping.
 * Aggregators in the same batch can execute in parallel.
 */
final class DependencyResolver
{
    /**
     * Resolve dependencies and return execution batches.
     *
     * @param AggregatorInterface[] $aggregators
     * @return AggregatorInterface[][] Array of batches, executed sequentially
     * @throws CircularDependencyException if circular dependencies detected
     * @throws AggregatorNotFoundException if dependency not in list
     */
    public function resolve(array $aggregators): array
    {
        if (empty($aggregators)) {
            return [];
        }

        $graph = $this->buildGraph($aggregators);
        $this->validateDependencies($aggregators, $graph);
        $this->detectCycles($graph);

        return $this->topologicalSort($graph, $aggregators);
    }

    /**
     * Build dependency graph from aggregators.
     *
     * @param AggregatorInterface[] $aggregators
     * @return array<string, string[]>
     */
    private function buildGraph(array $aggregators): array
    {
        $graph = [];
        foreach ($aggregators as $aggregator) {
            $graph[$aggregator->getName()] = $aggregator->getDependencies();
        }
        return $graph;
    }

    /**
     * Validate that all dependencies exist in the aggregator list.
     *
     * @param AggregatorInterface[] $aggregators
     * @param array<string, string[]> $graph
     * @throws AggregatorNotFoundException if dependency not found
     */
    private function validateDependencies(array $aggregators, array $graph): void
    {
        $names = array_map(
            static fn(AggregatorInterface $a): string => $a->getName(),
            $aggregators
        );

        foreach ($graph as $name => $deps) {
            foreach ($deps as $dep) {
                if (!in_array($dep, $names, true)) {
                    throw new AggregatorNotFoundException($dep);
                }
            }
        }
    }

    /**
     * Detect cycles using DFS.
     *
     * @param array<string, string[]> $graph
     * @throws CircularDependencyException if cycle detected
     */
    private function detectCycles(array $graph): void
    {
        $visited = [];
        $recStack = [];

        foreach (array_keys($graph) as $node) {
            if ($this->hasCycle($node, $graph, $visited, $recStack)) {
                throw new CircularDependencyException(array_keys($recStack));
            }
        }
    }

    /**
     * Check if a cycle exists starting from node.
     *
     * @param array<string, string[]> $graph
     * @param array<string, bool> $visited
     * @param array<string, bool> $recStack
     */
    private function hasCycle(
        string $node,
        array $graph,
        array &$visited,
        array &$recStack
    ): bool {
        if (isset($recStack[$node])) {
            return true;
        }

        if (isset($visited[$node])) {
            return false;
        }

        $visited[$node] = true;
        $recStack[$node] = true;

        foreach ($graph[$node] ?? [] as $neighbor) {
            if ($this->hasCycle($neighbor, $graph, $visited, $recStack)) {
                return true;
            }
        }

        unset($recStack[$node]);
        return false;
    }

    /**
     * Topological sort using Kahn's algorithm with batching.
     *
     * @param array<string, string[]> $graph
     * @param AggregatorInterface[] $aggregators
     * @return AggregatorInterface[][]
     */
    private function topologicalSort(array $graph, array $aggregators): array
    {
        // Build aggregator map
        $aggregatorMap = [];
        foreach ($aggregators as $aggregator) {
            $aggregatorMap[$aggregator->getName()] = $aggregator;
        }

        // Calculate in-degrees
        $inDegree = [];
        foreach ($graph as $name => $deps) {
            $inDegree[$name] = count($deps);
        }

        $batches = [];

        while (!empty($inDegree)) {
            // Get all nodes with no remaining dependencies
            $batch = [];
            foreach ($inDegree as $name => $degree) {
                if ($degree === 0) {
                    $batch[] = $aggregatorMap[$name];
                }
            }

            if (empty($batch)) {
                // This shouldn't happen if cycle detection is correct
                throw new CircularDependencyException(array_keys($inDegree));
            }

            // Sort batch by priority (descending)
            usort(
                $batch,
                static fn(AggregatorInterface $a, AggregatorInterface $b): int =>
                    $b->getPriority() <=> $a->getPriority()
            );

            $batches[] = $batch;

            // Remove processed nodes and update in-degrees
            foreach ($batch as $aggregator) {
                $name = $aggregator->getName();
                unset($inDegree[$name]);

                // Update in-degree for nodes that depended on this one
                foreach ($inDegree as $otherName => &$degree) {
                    if (in_array($name, $graph[$otherName] ?? [], true)) {
                        $degree--;
                    }
                }
            }
        }

        return $batches;
    }
}
