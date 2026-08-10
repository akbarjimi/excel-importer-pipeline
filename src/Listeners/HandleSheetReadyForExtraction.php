<?php

namespace Akbarjimi\ExcelImporter\Listeners;

use Akbarjimi\ExcelImporter\Events\SheetReadyForExtraction;
use Akbarjimi\ExcelImporter\Repositories\ExcelSheetRepository;
use Akbarjimi\ExcelImporter\Services\RowExtractionService;

final readonly class HandleSheetReadyForExtraction
{
    public function __construct(
        private readonly ExcelSheetRepository $sheetRepo,
        private RowExtractionService          $extractor,
    )
    {
    }

    public function handle(SheetReadyForExtraction $event): void
    {
        $sheet = $this->sheetRepo->getByFileId($event->sheetId);
        $this->extractor->extract($sheet);
    }
}
