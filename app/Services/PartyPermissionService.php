<?php

namespace App\Services;

use App\Models\User;

class PartyPermissionService
{
    public const VIEW_PARTIES = 'view_parties';
    public const MANAGE_PARTIES = 'manage_parties';
    public const MANAGE_PARTY_NOTES = 'manage_party_notes';
    public const MANAGE_PARTY_ATTACHMENTS = 'manage_party_attachments';
    public const MANAGE_PARTY_FOLLOW_UPS = 'manage_party_follow_ups';
    public const MANAGE_PARTY_CLASSIFICATIONS = 'manage_party_classifications';
    public const EXPORT_PARTIES = 'export_parties';
    public const VIEW_PARTY_FINANCIALS = 'view_party_financials';

    public function permissions(): array
    {
        return [
            self::VIEW_PARTIES => 'عرض العملاء والموردين',
            self::MANAGE_PARTIES => 'إنشاء وتعديل العملاء والموردين',
            self::MANAGE_PARTY_NOTES => 'إدارة ملاحظات العملاء والموردين',
            self::MANAGE_PARTY_ATTACHMENTS => 'إدارة مرفقات العملاء والموردين',
            self::MANAGE_PARTY_FOLLOW_UPS => 'إدارة المتابعات وسجلات التواصل',
            self::MANAGE_PARTY_CLASSIFICATIONS => 'إدارة التصنيفات',
            self::EXPORT_PARTIES => 'تصدير العملاء والموردين وكشوف الحساب',
            self::VIEW_PARTY_FINANCIALS => 'عرض الملخصات والكشوف المالية',
        ];
    }

    public function rolePermissions(): array
    {
        return [
            'owner' => array_keys($this->permissions()),
            'admin' => array_keys($this->permissions()),

            'manager' => [
                self::VIEW_PARTIES,
                self::MANAGE_PARTIES,
                self::MANAGE_PARTY_NOTES,
                self::MANAGE_PARTY_ATTACHMENTS,
                self::MANAGE_PARTY_FOLLOW_UPS,
                self::MANAGE_PARTY_CLASSIFICATIONS,
                self::EXPORT_PARTIES,
                self::VIEW_PARTY_FINANCIALS,
            ],

            'accountant' => [
                self::VIEW_PARTIES,
                self::MANAGE_PARTY_FOLLOW_UPS,
                self::EXPORT_PARTIES,
                self::VIEW_PARTY_FINANCIALS,
            ],

            'viewer' => [
                self::VIEW_PARTIES,
            ],

            'user' => [
                self::VIEW_PARTIES,
            ],

            'staff' => [
                self::VIEW_PARTIES,
            ],
        ];
    }

    public function can(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        $role = $this->resolveRole($user);

        if ($role === null) {
            return true;
        }

        $role = mb_strtolower(trim($role));

        if (in_array($role, ['owner', 'admin'], true)) {
            return true;
        }

        return in_array($permission, $this->rolePermissions()[$role] ?? [], true);
    }

    public function resolveRole(User $user): ?string
    {
        foreach (['role', 'type', 'user_type'] as $field) {
            if (array_key_exists($field, $user->getAttributes()) && filled($user->{$field})) {
                return (string) $user->{$field};
            }
        }

        return null;
    }
}
