<?php

namespace App\Security;

use App\Entity\Administration;
use App\Entity\Budget;
use App\Entity\Engagement;
use App\Entity\LigneBudgetaire;
use App\Entity\PosteComptable;
use App\Entity\UniteOperationnelle;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ProvinceScopeService
{
    public function __construct(
        private readonly PermissionService $permissionService,
        private readonly Security $security,
    ) {
    }

    public function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }

    public function canAccessAllProvinces(?User $user = null): bool
    {
        $user ??= $this->getCurrentUser();

        return $user !== null && $this->permissionService->canAccessAllProvinces($user);
    }

    public function getRestrictedProvinceId(?User $user = null): ?int
    {
        if ($this->canAccessAllProvinces($user)) {
            return null;
        }

        $user ??= $this->getCurrentUser();

        return $user?->getProvince()?->getId();
    }

    public function matchesRestrictedProvince(?int $provinceId, ?User $user = null): bool
    {
        $restricted = $this->getRestrictedProvinceId($user);
        if ($restricted === null) {
            return true;
        }

        return $provinceId !== null && $provinceId === $restricted;
    }

    public function assertCanAccessProvinceId(?int $provinceId, ?User $user = null): void
    {
        if (!$this->matchesRestrictedProvince($provinceId, $user)) {
            throw new AccessDeniedException('Accès refusé : données hors de votre province.');
        }
    }

    /**
     * @template T
     *
     * @param T[] $items
     * @param callable(T): ?int $provinceIdResolver
     *
     * @return T[]
     */
    public function filterByProvince(array $items, callable $provinceIdResolver): array
    {
        $restricted = $this->getRestrictedProvinceId();
        if ($restricted === null) {
            return $items;
        }

        return array_values(array_filter(
            $items,
            static fn ($item) => $provinceIdResolver($item) === $restricted
        ));
    }

    public function resolveProvinceIdForCreate(?int $requestedProvinceId): ?int
    {
        $restricted = $this->getRestrictedProvinceId();
        if ($restricted !== null) {
            return $restricted;
        }

        return $requestedProvinceId;
    }

    public function getProvinceIdFromAdministration(Administration $administration): ?int
    {
        return $administration->getProvince()?->getId();
    }

    public function getProvinceIdFromUnite(UniteOperationnelle $uo): ?int
    {
        return $uo->getAdministration()?->getProvince()?->getId();
    }

    public function getProvinceIdFromBudget(Budget $budget): ?int
    {
        return $budget->getUniteOperationnelle()?->getAdministration()?->getProvince()?->getId();
    }

    public function getProvinceIdFromLigne(LigneBudgetaire $ligne): ?int
    {
        $budget = $ligne->getBudget();
        if ($budget === null) {
            return null;
        }

        return $this->getProvinceIdFromBudget($budget);
    }

    public function getProvinceIdFromEngagement(Engagement $engagement): ?int
    {
        $ligne = $engagement->getLigneBudgetaire();
        if ($ligne !== null) {
            return $this->getProvinceIdFromLigne($ligne);
        }

        return $engagement->getPosteComptable()?->getProvince()?->getId();
    }

    public function getProvinceIdFromPoste(PosteComptable $poste): ?int
    {
        return $poste->getProvince()?->getId();
    }

    public function getProvinceIdFromUser(User $user): ?int
    {
        return $user->getProvince()?->getId();
    }

    public function assertCanAccessAdministration(Administration $administration): void
    {
        $this->assertCanAccessProvinceId($this->getProvinceIdFromAdministration($administration));
    }

    public function assertCanAccessUnite(UniteOperationnelle $uo): void
    {
        $this->assertCanAccessProvinceId($this->getProvinceIdFromUnite($uo));
    }

    public function assertCanAccessBudget(Budget $budget): void
    {
        $this->assertCanAccessProvinceId($this->getProvinceIdFromBudget($budget));
    }

    public function assertCanAccessLigne(LigneBudgetaire $ligne): void
    {
        $this->assertCanAccessProvinceId($this->getProvinceIdFromLigne($ligne));
    }

    public function assertCanAccessEngagement(Engagement $engagement): void
    {
        $this->assertCanAccessProvinceId($this->getProvinceIdFromEngagement($engagement));
    }

    public function assertCanAccessPoste(PosteComptable $poste): void
    {
        $this->assertCanAccessProvinceId($this->getProvinceIdFromPoste($poste));
    }

    public function assertCanAccessUser(User $user): void
    {
        $this->assertCanAccessProvinceId($this->getProvinceIdFromUser($user));
    }
}
