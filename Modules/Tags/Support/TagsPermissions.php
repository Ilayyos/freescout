<?php

namespace Modules\Tags\Support;

use App\Option;
use App\User;

class TagsPermissions
{
    public static function allowUserManagement(): bool
    {
        return (bool) Option::get('tags.allow_user_management', false);
    }

    public static function canManage(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!self::allowUserManagement()) {
            return false;
        }

        return $user->hasPermission(User::PERM_EDIT_TAGS);
    }

    public static function canEditConversationTags(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (self::allowUserManagement() && $user->hasPermission(User::PERM_EDIT_TAGS)) {
            return true;
        }

        return false;
    }
}
