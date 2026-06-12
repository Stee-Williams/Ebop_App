<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Security\PermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed>
 */
final class PermissionVoter extends Voter
{
    public const MANAGE_USERS = 'manage_users';
    public const MANAGE_REGLEMENTS = 'manage_reglements';
    public const MANAGE_ENGAGEMENTS = 'manage_engagements';
    public const VISA_ENGAGEMENTS = 'visa_engagements';
    public const READ_ENGAGEMENTS = 'read_engagements';
    public const READ_BUDGET = 'read_budget';
    public const MANAGE_LIGNES_BUDGETAIRES = 'manage_lignes_budgetaires';
    public const READ_LIGNES_BUDGETAIRES = 'read_lignes_budgetaires';
    public const MANAGE_ADMINISTRATIONS = 'manage_administrations';

    public function __construct(
        private readonly PermissionService $permissionService,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::MANAGE_USERS,
            self::MANAGE_REGLEMENTS,
            self::MANAGE_ENGAGEMENTS,
            self::VISA_ENGAGEMENTS,
            self::READ_ENGAGEMENTS,
            self::READ_BUDGET,
            self::MANAGE_LIGNES_BUDGETAIRES,
            self::READ_LIGNES_BUDGETAIRES,
            self::MANAGE_ADMINISTRATIONS,
        ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::MANAGE_USERS => $this->permissionService->canManageUsers($user),
            self::MANAGE_REGLEMENTS => $this->permissionService->canManageReglements($user),
            self::MANAGE_ENGAGEMENTS => $this->permissionService->canManageEngagements($user),
            self::VISA_ENGAGEMENTS => $this->permissionService->canVisaEngagements($user),
            self::READ_ENGAGEMENTS => $this->permissionService->canReadEngagements($user),
            self::READ_BUDGET => $this->permissionService->canReadBudget($user),
            self::MANAGE_LIGNES_BUDGETAIRES => $this->permissionService->canManageLignesBudgetaires($user),
            self::READ_LIGNES_BUDGETAIRES => $this->permissionService->canReadLignesBudgetaires($user),
            self::MANAGE_ADMINISTRATIONS => $this->permissionService->canManageAdministrations($user),
            default => false,
        };
    }
}
