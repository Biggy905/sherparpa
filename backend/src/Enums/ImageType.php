<?php

declare(strict_types=1);

namespace App\Enums;

enum ImageType: string
{
    case Photo = 'photo';
    case Packaging = 'packaging';
}
