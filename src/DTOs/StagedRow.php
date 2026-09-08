<?php

declare(strict_types=1);

namespace Akbarjimi\ExcelImporter\DTOs;

final  class StagedRow
{
    public function __construct(
        public readonly int    $sheetId,
        public readonly string $content,
        public readonly string $hashAlgo,
        public readonly string $contentHash,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'excel_sheet_id' => $this->sheetId,
            'content' => $this->content,
            'hash_algo' => $this->hashAlgo,
            'content_hash' => $this->contentHash,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}