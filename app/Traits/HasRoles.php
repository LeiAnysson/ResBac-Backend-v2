<?php

namespace App\Traits;

trait HasRoles
{
    public function isAdmin(): bool
    {
        return $this->role_id === 1;
    }

    public function isMdrrmo(): bool
    {
        return $this->role_id === 2;
    }

    public function isResponder(): bool
    {
        return $this->role_id === 3;
    }

    public function isResident(): bool
    {
        return $this->role_id === 4;
    }

    public function hasRole(int|string $role): bool
    {
        $map = [
            'admin' => 1,
            'mdrrmo' => 2,
            'responder' => 3,
            'resident' => 4,
        ];

        if (is_string($role) && isset($map[strtolower($role)])) {
            return $this->role_id === $map[strtolower($role)];
        }

        return $this->role_id === (int) $role;
    }
}
