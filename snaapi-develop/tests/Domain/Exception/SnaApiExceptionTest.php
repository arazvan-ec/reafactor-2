<?php

declare(strict_types=1);

namespace App\Tests\Domain\Exception;

use App\Domain\Exception\EditorialNotFoundException;
use App\Domain\Exception\InvalidAspectRatioException;
use App\Domain\Exception\ResourceNotFoundException;
use App\Domain\Exception\ServiceUnavailableException;
use PHPUnit\Framework\TestCase;

final class SnaApiExceptionTest extends TestCase
{
    public function testInvalidAspectRatioException(): void
    {
        $exception = InvalidAspectRatioException::create('invalid', ['4:3', '16:9']);

        $this->assertStringContainsString('Invalid aspect ratio "invalid"', $exception->getMessage());
        $this->assertEquals('invalid', $exception->getContext()['provided_ratio']);
        $this->assertEquals(['4:3', '16:9'], $exception->getContext()['supported_ratios']);
    }

    public function testEditorialNotFoundException(): void
    {
        $exception = EditorialNotFoundException::withId('123');

        $this->assertStringContainsString('Editorial with ID "123" not found', $exception->getMessage());
        $this->assertEquals('123', $exception->getContext()['editorial_id']);
    }

    public function testServiceUnavailableException(): void
    {
        $previous = new \RuntimeException('Connection failed');
        $exception = ServiceUnavailableException::forService('QuerySectionClient', $previous);

        $this->assertStringContainsString('Service "QuerySectionClient" is currently unavailable', $exception->getMessage());
        $this->assertEquals('QuerySectionClient', $exception->getContext()['service_name']);
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testServiceUnavailableExceptionWithoutPrevious(): void
    {
        $exception = ServiceUnavailableException::forService('QuerySectionClient');

        $this->assertNull($exception->getPrevious());
    }

    public function testResourceNotFoundException(): void
    {
        $exception = ResourceNotFoundException::forResource('Section', 'sec-456');

        $this->assertStringContainsString('Section with ID "sec-456" not found', $exception->getMessage());
        $this->assertEquals('Section', $exception->getContext()['resource_type']);
        $this->assertEquals('sec-456', $exception->getContext()['resource_id']);
    }

    public function testWithContext(): void
    {
        $exception = EditorialNotFoundException::withId('123');
        $exception->withContext(['additional' => 'info']);

        $context = $exception->getContext();
        $this->assertEquals('123', $context['editorial_id']);
        $this->assertEquals('info', $context['additional']);
    }

    public function testToArray(): void
    {
        $exception = EditorialNotFoundException::withId('123');

        $array = $exception->toArray();

        $this->assertEquals(EditorialNotFoundException::class, $array['error']);
        $this->assertStringContainsString('123', $array['message']);
        $this->assertArrayHasKey('editorial_id', $array['context']);
    }
}
