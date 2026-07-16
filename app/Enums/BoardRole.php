<?php

namespace App\Enums;

enum BoardRole: string
{
    case Viewer = 'viewer';
    case Editor = 'editor';
}
