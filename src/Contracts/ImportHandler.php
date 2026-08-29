<?php

namespace Akbarjimi\ExcelImporter\Contracts;

interface ImportHandler
{
    public function handle(int $fileId, iterable $rows): void;
}