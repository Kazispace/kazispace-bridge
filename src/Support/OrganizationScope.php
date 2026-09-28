<?php

namespace Kazispace\Bridge\Support;

final class OrganizationScope
{
    public static function fromUser(mixed $user): ?string
    {
        if (! is_object($user) || ! isset($user->company_uuid)) {
            return null;
        }

        $organizationId = $user->company_uuid;
        if (! is_string($organizationId) || $organizationId === '') {
            return null;
        }

        return $organizationId;
    }
}
