<?php

namespace App\Enums;

enum DriverLocationWriteResult: string
{
    case Stored = 'stored';
    case Invalid = 'invalid';
    case Stale = 'stale';
}
