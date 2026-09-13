<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\Services;

use Akbarjimi\ExcelImporter\Contracts\TransformerInterface;
use Akbarjimi\ExcelImporter\Models\ExcelSheet;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;

final class TransformService
{
    public function __construct(
        private readonly Config $config,
        private readonly Container $container,
    ) {}

    public function apply(array $rawRow, ExcelSheet $sheet): array
    {
        $sheetConfig = $this->config->get("excel-importer-sheets.{$sheet->name}", []);

        $mappedRow = $this->applyMapping($rawRow, $sheetConfig['mapping'] ?? []);

        $transformerClass = $sheetConfig['transformer'] ?? null;

        if ($transformerClass === null) {
            return $mappedRow;
        }

        $transformer = $this->container->make($transformerClass);

        if (! $transformer instanceof TransformerInterface) {
            throw new \RuntimeException(sprintf(
                'Transformer [%s] must implement [%s].',
                $transformerClass,
                TransformerInterface::class,
            ));
        }

        return $transformer->transform($mappedRow, $sheet);
    }

    /**
     * @param  array<string, string>  $mapping  target key => source key (e.g. 'name' => 'A1')
     */
    private function applyMapping(array $rawRow, array $mapping): array
    {
        if ($mapping === []) {
            return $rawRow;
        }

        $mapped = [];

        foreach ($mapping as $targetKey => $sourceKey) {
            $mapped[$targetKey] = $rawRow[$sourceKey] ?? null;
        }

        return $mapped;
    }
}