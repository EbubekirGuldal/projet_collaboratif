<?php

namespace App\Enum;

enum ResourceStatus: string
{
    case PRIVATE = 'private';
    case SHARED = 'shared';
    case PUBLIC = 'public';
    case UNDER_REVIEW = 'under_review';
}
