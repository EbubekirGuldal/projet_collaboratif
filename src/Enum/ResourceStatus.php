<?php

namespace App\Enum;

enum ResourceStatus: string
{
    case PUBLIC = 'public';
    case UNDER_REVIEW = 'under_review';
}