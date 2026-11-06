<?php

declare(strict_types=1);

namespace App\Enums;

enum WorkerJobType: string
{
    case Import = 'import';
    case ImageDownload = 'image_download';
}
