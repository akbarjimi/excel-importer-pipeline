<?php

namespace Akbarjimi\ExcelImporter\Events;

final readonly class AllRowsExtracted
{
    public function __construct(
        public int $fileId,
    ) {}
}
