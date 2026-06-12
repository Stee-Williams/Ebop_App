<?php

namespace App\Security;

use App\Entity\User;

final class PermissionService
{
    public function normalizeRole(?string $role): ?string
    {
        if ($role === null || $role === '') {
            return null;
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $role);
        $normalized = strtoupper(str_replace(' ', '_', $ascii ?: $role));

        if (
            $normalized === 'SUPER_ADMIN'
            || (str_contains($normalized, 'SUPER') && str_contains($normalized, 'ADMIN'))
        ) {
            return 'super_admin';
        }
        if ($normalized === 'DBA') {
            return 'dba';
        }
        if (str_contains($normalized, 'INFORMATICIEN')) {
            return 'informaticien';
        }
        if (str_contains($normalized, 'TRESORIER')) {
            return 'tresorier';
        }
        if (str_contains($normalized, 'PRINCIPAL')) {
            return 'controleur_budgetaire_principal';
        }
        if (str_contains($normalized, 'CONTROLEUR')) {
            return 'controleur_budgetaire';
        }
        if (str_contains($normalized, 'ASSISTANT')) {
            return 'assistant_gestionnaire';
        }

        return null;
    }

    public function isSuperAdmin(User $user): bool
    {
        return $this->normalizeRole($user->getRole()?->getNom()) === 'super_admin';
    }

    public function canManageUsers(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $role = $this->normalizeRole($user->getRole()?->getNom());

        return in_array($role, ['dba', 'informaticien'], true);
    }

    public function canManageReglements(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->normalizeRole($user->getRole()?->getNom()) === 'tresorier';
    }

    public function canVisaEngagements(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->normalizeRole($user->getRole()?->getNom()) === 'controleur_budgetaire';
    }

    public function canManageEngagements(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return in_array(
            $this->normalizeRole($user->getRole()?->getNom()),
            ['controleur_budgetaire', 'assistant_gestionnaire'],
            true
        );
    }

    public function canReadEngagements(User $user): bool
    {
        return $this->canManageEngagements($user)
            || $this->canManageReglements($user)
            || $this->canManageUsers($user);
    }

    public function canReadBudget(User $user): bool
    {
        return $this->canManageEngagements($user)
            || $this->canManageReglements($user)
            || $this->isSuperAdmin($user);
    }

    public function canManageLignesBudgetaires(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->normalizeRole($user->getRole()?->getNom()) === 'controleur_budgetaire_principal';
    }

    public function canManageAdministrations(User $user): bool
    {
        return $this->canManageLignesBudgetaires($user);
    }

    public function canReadLignesBudgetaires(User $user): bool
    {
        return $this->canManageLignesBudgetaires($user)
            || $this->canManageEngagements($user);
    }
}
