<?php

declare(strict_types=1);

namespace NeneServe\Measurement\Dsr;

interface DataSubjectRequestUseCaseInterface
{
    public function export(ExportVisitorDataInput $input): ExportVisitorDataOutput;

    public function erase(EraseVisitorDataInput $input): EraseVisitorDataOutput;
}
