<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class TemplateTest extends TestCase
{
    public function testTheNidoTemplateIsReady(): void
    {
        self::assertSame('nido', 'nido');
    }
}
