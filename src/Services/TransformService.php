<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Contracts\TransformerInterface;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Illuminate\Contracts\Config\Repository as Config;

final class TransformService
{
    public function __construct(
        private Config $config,
        private Application $app,
    ) {}

    public function apply(array $rawRow, ExcelSheet $sheet): array
    {
        $sheetConfig = $this->config->get("excel-importer-sheets.{$sheet->name}", []);

        $mappedRow = $this->applyMapping($rawRow, $sheetConfig['mapping'] ?? []);

        $transformerClass = $sheetConfig['transformer'] ?? null;
        if ($transformerClass && class_exists($transformerClass)) {
            $transformer = $this->app->make($transformerClass);
            if (!$transformer instanceof TransformerInterface) {
                throw new \RuntimeException('Transformer must implement ' . TransformerInterface::class);
            }
            return $transformer->transform($mappedRow, $sheet);
        }

        return $mappedRow;
    }

    private function applyMapping(array $rawRow, array $mapping): array
    {
        $mapped = [];
        foreach ($mapping as $targetKey => $sourceKey) {
            $mapped[$targetKey] = $rawRow[$sourceKey] ?? null;
        }
        return empty($mapping) ? $rawRow : $mapped;
    }
}