<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Import;

use App\Exceptions\InvalidRowException;
use App\Services\Import\XlsxParser;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertNull;

final class XlsxParserTest extends TestCase
{
    public function testParsesValidRowsAndReportsInvalid(): void
    {
        $parser = new XlsxParser();
        $result = $parser->parse(__DIR__ . '/../../../files/import_example.xlsx');

        self::assertGreaterThan(0, $result['total']);
        self::assertNotEmpty($result['rows']);

        $first = $result['rows'][0];
        self::assertSame('3UHfAid1jaMiwgBuNvnsf3', $first->externalCode);
        self::assertSame('1320.00', $first->price);
        self::assertSame('880.00', $first->purchasePrice);
        self::assertSame('33.33', $first->discount);
        self::assertArrayHasKey('Размер', $first->attributes);
        self::assertSame('46(M)', $first->attributes['Размер']);
        self::assertContains(
            'http://catalog.collant.ru/pics/SNL-504038_p.jpg',
            $first->photoUrls,
        );
        assertNull($first->packagingUrl);
    }

    public function testInvalidRowIsCollectedInErrors(): void
    {
        $parser = new XlsxParser();
        $result = $parser->parse(__DIR__ . '/../../../files/import_invalid_rows.xlsx');

        self::assertGreaterThan(0, $result['total']);
        self::assertNotEmpty($result['errors']);

        $first = $result['errors'][0];
        self::assertGreaterThan(0, $first->rowNumber);
        self::assertNotSame('', $first->getMessage());
    }
}
