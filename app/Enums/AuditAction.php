<?php

namespace App\Enums;

enum AuditAction: string
{
    case UserSuspended = 'user.suspended';
    case UserUnsuspended = 'user.unsuspended';
    case UserRoleChanged = 'user.role_changed';
    case UserDeleted = 'user.deleted';
    case BoardViewedByAdmin = 'board.viewed_by_admin';
    case BoardDeleted = 'board.deleted';
    case BoardShareRevoked = 'board.share_revoked';
    case AccountSelfDeleted = 'account.self_deleted';
}
