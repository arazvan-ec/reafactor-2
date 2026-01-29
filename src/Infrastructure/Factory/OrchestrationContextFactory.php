<?php

declare(strict_types=1);

namespace App\Infrastructure\Factory;

use App\Domain\Aggregator\ValueObject\AggregatorContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * Factory for creating AggregatorContext from various sources.
 *
 * Bridges between the existing Editorial domain model and the new
 * aggregation system's context.
 */
final class OrchestrationContextFactory
{
    /**
     * Create AggregatorContext from an Editorial array.
     *
     * @param array<string, mixed> $editorial Editorial data
     * @param Request|null $request HTTP request for metadata
     * @return AggregatorContext
     */
    public function createFromEditorial(array $editorial, ?Request $request = null): AggregatorContext
    {
        $editorialId = $this->extractEditorialId($editorial);
        $metadata = $this->buildMetadata($request);

        return new AggregatorContext($editorialId, $editorial, [], $metadata);
    }

    /**
     * Create AggregatorContext from an Editorial domain object.
     *
     * @param object $editorial Editorial domain object
     * @param Request|null $request HTTP request for metadata
     * @return AggregatorContext
     */
    public function createFromEditorialObject(object $editorial, ?Request $request = null): AggregatorContext
    {
        $rawData = $this->extractRawDataFromEditorial($editorial);
        $editorialId = $this->extractEditorialIdFromObject($editorial);
        $metadata = $this->buildMetadata($request);

        return new AggregatorContext($editorialId, $rawData, [], $metadata);
    }

    /**
     * Extract editorial ID from raw data.
     *
     * @param array<string, mixed> $editorial
     */
    private function extractEditorialId(array $editorial): string
    {
        if (isset($editorial['id'])) {
            return (string) $editorial['id'];
        }

        if (isset($editorial['editorialId'])) {
            return (string) $editorial['editorialId'];
        }

        return 'unknown';
    }

    /**
     * Extract editorial ID from domain object.
     *
     * @param object $editorial
     */
    private function extractEditorialIdFromObject(object $editorial): string
    {
        // Support different Editorial implementations
        if (method_exists($editorial, 'id')) {
            $id = $editorial->id();
            if (is_object($id) && method_exists($id, 'id')) {
                return (string) $id->id();
            }

            return (string) $id;
        }

        if (method_exists($editorial, 'getId')) {
            return (string) $editorial->getId();
        }

        return 'unknown';
    }

    /**
     * Extract raw data from Editorial domain object.
     *
     * @param object $editorial
     * @return array<string, mixed>
     */
    private function extractRawDataFromEditorial(object $editorial): array
    {
        $data = [];

        // Extract basic fields
        $data['id'] = $this->extractEditorialIdFromObject($editorial);

        // Extract section ID
        if (method_exists($editorial, 'sectionId')) {
            $data['sectionId'] = (string) $editorial->sectionId();
        }

        // Extract tags
        if (method_exists($editorial, 'tags')) {
            $tags = $editorial->tags();
            $data['tags'] = [];
            if ($tags !== null) {
                $tagArray = is_array($tags) ? $tags : $tags->getArrayCopy();
                foreach ($tagArray as $tag) {
                    if (is_object($tag) && method_exists($tag, 'id')) {
                        $data['tags'][] = ['id' => (string) $tag->id()];
                    }
                }
            }
        }

        // Extract multimedia
        if (method_exists($editorial, 'multimedia')) {
            $multimedia = $editorial->multimedia();
            if ($multimedia !== null && method_exists($multimedia, 'id')) {
                $multimediaId = $multimedia->id();
                if (is_object($multimediaId) && method_exists($multimediaId, 'id')) {
                    $data['multimediaId'] = (string) $multimediaId->id();
                }
            }
        }

        // Extract metaImage
        if (method_exists($editorial, 'metaImage')) {
            $data['metaImageId'] = $editorial->metaImage();
        }

        // Extract signatures/journalists
        if (method_exists($editorial, 'signatures')) {
            $signatures = $editorial->signatures();
            $data['journalistIds'] = [];
            if ($signatures !== null) {
                $sigArray = is_array($signatures) ? $signatures : $signatures->getArrayCopy();
                foreach ($sigArray as $signature) {
                    if (is_object($signature) && method_exists($signature, 'id')) {
                        $sigId = $signature->id();
                        if (is_object($sigId) && method_exists($sigId, 'id')) {
                            $data['journalistIds'][] = (string) $sigId->id();
                        }
                    }
                }
            }
        }

        // Extract body
        if (method_exists($editorial, 'body')) {
            $body = $editorial->body();
            if ($body !== null) {
                $data['body'] = $this->extractBodyData($body);
            }
        }

        // Extract opening
        if (method_exists($editorial, 'opening')) {
            $opening = $editorial->opening();
            if ($opening !== null && method_exists($opening, 'multimediaId')) {
                $data['openingMultimediaId'] = $opening->multimediaId();
            }
        }

        return $data;
    }

    /**
     * Extract body data from Body domain object.
     *
     * @param object $body
     * @return array<string, mixed>
     */
    private function extractBodyData(object $body): array
    {
        $data = ['bodyElements' => []];

        if (method_exists($body, 'bodyElements')) {
            $elements = $body->bodyElements();
            if ($elements !== null) {
                $elemArray = is_array($elements) ? $elements : $elements->getArrayCopy();
                foreach ($elemArray as $element) {
                    $data['bodyElements'][] = $this->extractBodyElementData($element);
                }
            }
        }

        return $data;
    }

    /**
     * Extract data from a body element.
     *
     * @param mixed $element
     * @return array<string, mixed>
     */
    private function extractBodyElementData(mixed $element): array
    {
        if (is_array($element)) {
            return $element;
        }

        if (!is_object($element)) {
            return ['type' => 'unknown', 'content' => (string) $element];
        }

        $data = [];

        // Determine type from class name
        $className = get_class($element);
        $shortName = substr($className, strrpos($className, '\\') + 1);
        $data['type'] = lcfirst($shortName);

        // Common fields
        if (method_exists($element, 'content')) {
            $data['content'] = $element->content();
        }

        if (method_exists($element, 'id')) {
            $id = $element->id();
            if (is_object($id) && method_exists($id, 'id')) {
                $data['multimediaId'] = (string) $id->id();
            }
        }

        return $data;
    }

    /**
     * Build metadata from request.
     *
     * @param Request|null $request
     * @return array<string, mixed>
     */
    private function buildMetadata(?Request $request): array
    {
        if ($request === null) {
            return [];
        }

        return [
            'requestId' => $request->headers->get('X-Request-ID', uniqid('req-', true)),
            'clientIp' => $request->getClientIp(),
            'userAgent' => $request->headers->get('User-Agent'),
        ];
    }
}
