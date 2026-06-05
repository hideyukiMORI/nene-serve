<?php

declare(strict_types=1);

namespace NeneServe\Serving\Creatives;

use NeneServe\Serving\UseCase\CreativeValidationException;

interface CreateCreativeUseCaseInterface
{
    /** @throws CreativeValidationException */
    public function createImage(CreateImageCreativeInput $input): CreateCreativeOutput;

    /** @throws CreativeValidationException */
    public function createVideo(CreateVideoCreativeInput $input): CreateCreativeOutput;

    /** @throws CreativeValidationException */
    public function createHtml5(CreateHtml5CreativeInput $input): CreateCreativeOutput;
}
