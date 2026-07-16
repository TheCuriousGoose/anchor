<?php

namespace App\Enums;

enum NotificationType: string
{
    case BoardShared = 'board_shared';
    case BoardRoleChanged = 'board_role_changed';
    case BoardAccessRevoked = 'board_access_revoked';
}
