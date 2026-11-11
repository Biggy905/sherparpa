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
        $result = $parser->parse(__DIR__ . '/../../../files/import_example_ok.xlsx');

        self::assertGreaterThan(0, $result['total']);
        self::assertNotEmpty($result['rows']);

        $first = $result['rows'][0];
        self::assertSame('AN-AZxf2i24obi-6ehzeU0', $first->externalCode);
        self::assertSame('780.00', $first->price);
        self::assertSame('520.00', $first->purchasePrice);
        self::assertSame('33.33', $first->discount);
        self::assertArrayHasKey('Размер', $first->attributes);
        self::assertSame('3 (M)', $first->attributes['Размер']);
        self::assertSame('http://catalog.collant.ru/pics/SNL-456097_p.jpg', $first->packagingUrl);
    }

    public function testInvalidRowIsCollectedInErrors(): void
    {
        $parser = new XlsxParser();
        $result = $parser->parse(dirname(__DIR__, 3) . '/files/import_invalid_rows.xlsx');

        self::assertGreaterThan(0, $result['total']);
        self::assertNotEmpty($result['errors']);

        $first = $result['errors'][0];
        self::assertGreaterThan(0, $first->rowNumber);
        self::assertNotSame('', $first->getMessage());
    }
}
