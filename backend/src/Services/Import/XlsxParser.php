<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\DTO\XlsxRow;
use App\Entities\Product;
use App\Exceptions\InvalidFileException;
use App\Exceptions\InvalidRowException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class XlsxParser
{
    private const string COL_EXTERNAL_CODE = 'Внешний код';
    private const string COL_NAME = 'Наименование';
    private const string COL_DESCRIPTION = 'Описание';
    private const string COL_PRICE = 'Цена: Цена продажи';
    private const string COL_PURCHASE_PRICE = 'Закупочная цена';
    private const string COL_PHOTOS = 'Доп. поле: Ссылки на фото';
    private const string COL_PACKAGING = 'Доп. поле: Ссылка на упаковку';
    private const string ATTR_PREFIX = 'Доп. поле:';

    /**
     * @return array{rows: list<XlsxRow>, total: int, errors: list<InvalidRowException>}
     */
    public function parse(string $filePath): array
    {
        if (!\is_file($filePath) || !\is_readable($filePath)) {
            throw InvalidFileException::notReadable($filePath);
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $headerRow = $this->findHeaderRow($sheet);
        if ($headerRow === null) {
            throw InvalidFileException::missingHeaders();
        }

        /** @var array<string, string> $columns */
        $columns = $this->mapColumns($sheet, $headerRow);

        $rows = [];
        $errors = [];
        $total = 0;

        $highestRow = $sheet->getHighestDataRow();

        for ($r = $headerRow + 1; $r <= $highestRow; ++$r) {
            if ($this->isBlankRow($sheet, $r, $columns)) {
                continue;
            }

            ++$total;

            try {
                $rows[] = $this->parseRow($sheet, $r, $columns);
            } catch (InvalidRowException $e) {
                $errors[] = $e;
            }
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'errors' => $errors,
        ];
    }

    private function findHeaderRow(Worksheet $sheet): ?int
    {
        $highestRow = $sheet->getHighestDataRow();

        for ($r = 1; $r <= $highestRow; ++$r) {
            foreach ($sheet->getRowIterator($r, $r) as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $value = $this->stringifyCell($cell->getValue());
                    if ($value === self::COL_EXTERNAL_CODE) {
                        return $r;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function mapColumns(Worksheet $sheet, int $headerRow): array
    {
        /** @var array<string, string> $map */
        $map = [];

        foreach ($sheet->getRowIterator($headerRow, $headerRow) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $header = $this->stringifyCell($cell->getValue());
                if ($header === null) {
                    continue;
                }
                $map[$header] = $cell->getColumn();
            }
        }

        if (!isset($map[self::COL_EXTERNAL_CODE])) {
            throw InvalidFileException::missingHeaders();
        }

        return $map;
    }

    /**
     * @param array<string, string> $columns
     */
    private function isBlankRow(Worksheet $sheet, int $rowNumber, array $columns): bool
    {
        foreach ($columns as $columnLetter) {
            $value = $this->stringifyCell(
                $sheet->getCell($columnLetter . $rowNumber)->getValue(),
            );
            if ($value !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, string> $columns
     */
    private function parseRow(Worksheet $sheet, int $rowNumber, array $columns): XlsxRow
    {
        $externalCode = $this->readString($sheet, $columns, self::COL_EXTERNAL_CODE, $rowNumber);
        if ($externalCode === null) {
            throw new InvalidRowException($rowNumber, 'Missing external_code');
        }

        $name = $this->readString($sheet, $columns, self::COL_NAME, $rowNumber);
        if ($name === null) {
            throw new InvalidRowException($rowNumber, 'Missing name');
        }

        $description = $this->readString($sheet, $columns, self::COL_DESCRIPTION, $rowNumber);

        $price = $this->readMoney($sheet, $columns, self::COL_PRICE, $rowNumber);
        if ($price === null) {
            throw new InvalidRowException($rowNumber, 'Missing price');
        }

        $purchasePrice = $this->readMoney($sheet, $columns, self::COL_PURCHASE_PRICE, $rowNumber) ?? '0.00';

        $discount = Product::calculateDiscount($price, $purchasePrice);

        return new XlsxRow(
            rowNumber: $rowNumber,
            externalCode: $externalCode,
            name: $name,
            description: $description,
            price: $price,
            purchasePrice: $purchasePrice,
            discount: $discount,
            attributes: $this->collectAttributes($sheet, $columns, $rowNumber),
            photoUrls: $this->collectPhotoUrls($sheet, $columns, $rowNumber),
            packagingUrl: $this->collectPackagingUrl($sheet, $columns, $rowNumber),
        );
    }

    /**
     * @param array<string, string> $columns
     *
     * @return list<string>
     */
    private function collectPhotoUrls(Worksheet $sheet, array $columns, int $rowNumber): array
    {
        /** @var list<string> $urls */
        $urls = [];

        $photos = $this->readString($sheet, $columns, self::COL_PHOTOS, $rowNumber);
        if ($photos !== null) {
            foreach (\explode(',', $photos) as $url) {
                $url = \trim($url);
                if ($url !== '' && \filter_var($url, \FILTER_VALIDATE_URL) !== false) {
                    $urls[] = $url;
                }
            }
        }

        return $urls;
    }

    /**
     * @param array<string, string> $columns
     */
    private function collectPackagingUrl(Worksheet $sheet, array $columns, int $rowNumber): ?string
    {
        $packaging = $this->readString($sheet, $columns, self::COL_PACKAGING, $rowNumber);
        if ($packaging !== null && \filter_var($packaging, \FILTER_VALIDATE_URL) !== false) {
            return $packaging;
        }

        return null;
    }

    /**
     * @param array<string, string> $columns
     */
    private function readString(Worksheet $sheet, array $columns, string $header, int $rowNumber): ?string
    {
        $column = $columns[$header] ?? null;
        if ($column === null) {
            return null;
        }

        return $this->stringifyCell(
            $sheet->getCell($column . $rowNumber)->getValue(),
        );
    }

    /**
     * @param array<string, string> $columns
     */
    private function readMoney(Worksheet $sheet, array $columns, string $header, int $rowNumber): ?string
    {
        $raw = $this->readString($sheet, $columns, $header, $rowNumber);
        if ($raw === null) {
            return null;
        }

        $normalized = \str_replace([' ', "\u{00A0}", ','], ['', '', '.'], $raw);

        if (!\preg_match('/^-?\d+(\.\d{1,2})?$/', $normalized)) {
            throw new InvalidRowException(
                $rowNumber,
                \sprintf('Invalid money value "%s" in "%s"', $raw, $header),
            );
        }

        return \number_format((float) $normalized, 2, '.', '');
    }

    /**
     * @param array<string, string> $columns
     *
     * @return array<string, string>
     */
    private function collectAttributes(Worksheet $sheet, array $columns, int $rowNumber): array
    {
        /** @var array<string, string> $attributes */
        $attributes = [];

        foreach (\array_keys($columns) as $header) {
            if (!\str_starts_with($header, self::ATTR_PREFIX)) {
                continue;
            }

            if ($header === self::COL_PHOTOS || $header === self::COL_PACKAGING) {
                continue;
            }

            $value = $this->readString($sheet, $columns, $header, $rowNumber);
            if ($value === null) {
                continue;
            }

            $key = \trim(\substr($header, \strlen(self::ATTR_PREFIX)));
            if ($key === '') {
                continue;
            }

            $attributes[$key] = $value;
        }

        return $attributes;
    }

    private function stringifyCell(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (\is_object($value) && \method_exists($value, '__toString')) {
            $value = (string) $value;
        }

        if (!\is_scalar($value)) {
            return null;
        }

        $string = \trim((string) $value);

        return $string === '' ? null : $string;
    }
}
