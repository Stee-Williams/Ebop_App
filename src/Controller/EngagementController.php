<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\Engagement;
use App\Entity\User;
use App\Repository\EngagementRepository;
use App\Repository\FournisseurRepository;
use App\Repository\LigneBudgetaireRepository;
use App\Repository\PosteComptableRepository;
use App\Repository\UserRepository;
use App\Security\Voter\PermissionVoter;
use App\Service\EngagementWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/engagements')]
final class EngagementController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_engagements_list', methods: ['GET'])]
    #[IsGranted(PermissionVoter::READ_ENGAGEMENTS)]
    public function list(EngagementRepository $repository): JsonResponse
    {
        $items = $repository->findBy([], ['date' => 'DESC']);

        return $this->success(array_map(fn (Engagement $e) => $this->serialize($e), $items));
    }

    #[Route('/vises', name: 'api_engagements_vises', methods: ['GET'])]
    #[IsGranted(PermissionVoter::READ_ENGAGEMENTS)]
    public function vises(EngagementRepository $repository): JsonResponse
    {
        $items = $repository->findBy(['statut' => 'Visé'], ['date' => 'DESC']);

        return $this->success(array_map(fn (Engagement $e) => $this->serialize($e), $items));
    }

    #[Route('/next-numero', name: 'api_engagements_next_numero', methods: ['GET'])]
    #[IsGranted(PermissionVoter::MANAGE_ENGAGEMENTS)]
    public function nextNumero(Request $request, EngagementRepository $repository): JsonResponse
    {
        $year = $request->query->getInt('annee') ?: (int) date('Y');
        $numero = $repository->generateNextNumero($year);

        return $this->success([
            'numero' => $numero,
            'annee' => $year,
        ]);
    }

    #[Route('/{id}', name: 'api_engagements_show', methods: ['GET'])]
    #[IsGranted(PermissionVoter::READ_ENGAGEMENTS)]
    public function show(int $id, EngagementRepository $repository): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Engagement introuvable', Response::HTTP_NOT_FOUND);
        }

        return $this->success($this->serialize($item));
    }

    #[Route('', name: 'api_engagements_create', methods: ['POST'])]
    #[IsGranted(PermissionVoter::MANAGE_ENGAGEMENTS)]
    public function create(
        Request $request,
        EngagementRepository $engagementRepository,
        LigneBudgetaireRepository $ligneRepository,
        PosteComptableRepository $posteRepository,
        FournisseurRepository $fournisseurRepository,
        UserRepository $userRepository,
        EngagementWorkflowService $workflow,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        if (
            !$data
            || empty($data['titre'])
            || !isset($data['montant'])
            || empty($data['date'])
            || empty($data['statut'])
        ) {
            return $this->error('Titre, montant, date et statut sont obligatoires');
        }

        $date = new \DateTime($data['date']);
        $year = (int) $date->format('Y');
        $numero = !empty($data['numero'])
            ? trim((string) $data['numero'])
            : $engagementRepository->generateNextNumero($year);

        $item = new Engagement();
        $item->setNumero($numero);
        $item->setTitre(trim((string) $data['titre']));
        $item->setMontant((string) $data['montant']);
        $item->setDate($date);
        $item->setStatut($data['statut']);

        if (!empty($data['ligne_budgetaire_id'])) {
            $ligne = $ligneRepository->find($data['ligne_budgetaire_id']);
            if (!$ligne) {
                return $this->error('Ligne budgétaire introuvable', Response::HTTP_NOT_FOUND);
            }
            $item->setLigneBudgetaire($ligne);
        }
        if (!empty($data['poste_comptable_id'])) {
            $poste = $posteRepository->find($data['poste_comptable_id']);
            if (!$poste) {
                return $this->error('Poste comptable introuvable', Response::HTTP_NOT_FOUND);
            }
            $item->setPosteComptable($poste);
        }
        if (!empty($data['fournisseur_id'])) {
            $fournisseur = $fournisseurRepository->find($data['fournisseur_id']);
            if (!$fournisseur) {
                return $this->error('Fournisseur introuvable', Response::HTTP_NOT_FOUND);
            }
            $item->setFournisseur($fournisseur);
        }
        if (!empty($data['user_id'])) {
            $user = $userRepository->find($data['user_id']);
            if (!$user) {
                return $this->error('Utilisateur introuvable', Response::HTTP_NOT_FOUND);
            }
            $item->setUsers($user);
        }

        try {
            $workflow->onCreate($item);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage());
        }

        $em->persist($item);
        $em->flush();

        return $this->success(['message' => 'Engagement créé', 'data' => $this->serialize($item)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_engagements_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        EngagementRepository $repository,
        LigneBudgetaireRepository $ligneRepository,
        PosteComptableRepository $posteRepository,
        FournisseurRepository $fournisseurRepository,
        UserRepository $userRepository,
        EngagementWorkflowService $workflow,
        EntityManagerInterface $em,
    ): JsonResponse {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Engagement introuvable', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);

        if (isset($data['statut'])) {
            $newStatut = (string) $data['statut'];
            if (in_array($newStatut, ['Visé', 'Rejeté'], true)) {
                $this->denyAccessUnlessGranted(PermissionVoter::VISA_ENGAGEMENTS);
            } else {
                $this->denyAccessUnlessGranted(PermissionVoter::MANAGE_ENGAGEMENTS);
            }

            $actor = $this->getUser();
            if (!$actor instanceof User) {
                return $this->error('Utilisateur non authentifié', Response::HTTP_UNAUTHORIZED);
            }

            try {
                $workflow->changeStatut($item, $newStatut, $actor, $data['motif_rejet'] ?? null);
            } catch (\DomainException $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->denyAccessUnlessGranted(PermissionVoter::MANAGE_ENGAGEMENTS);
        }

        if (isset($data['numero'])) {
            $item->setNumero($data['numero']);
        }
        if (isset($data['titre'])) {
            $item->setTitre($data['titre']);
        }
        if (isset($data['montant'])) {
            $item->setMontant((string) $data['montant']);
        }
        if (isset($data['date'])) {
            $item->setDate(new \DateTime($data['date']));
        }
        if (array_key_exists('ligne_budgetaire_id', $data)) {
            if ($data['ligne_budgetaire_id'] === null) {
                $item->setLigneBudgetaire(null);
            } else {
                $ligne = $ligneRepository->find($data['ligne_budgetaire_id']);
                if (!$ligne) {
                    return $this->error('Ligne budgétaire introuvable', Response::HTTP_NOT_FOUND);
                }
                $item->setLigneBudgetaire($ligne);
                try {
                    $workflow->ensureBudgetEngaged($item);
                } catch (\DomainException $e) {
                    return $this->error($e->getMessage());
                }
            }
        }
        if (array_key_exists('poste_comptable_id', $data)) {
            if ($data['poste_comptable_id'] === null) {
                $item->setPosteComptable(null);
            } else {
                $poste = $posteRepository->find($data['poste_comptable_id']);
                if (!$poste) {
                    return $this->error('Poste comptable introuvable', Response::HTTP_NOT_FOUND);
                }
                $item->setPosteComptable($poste);
            }
        }
        if (array_key_exists('fournisseur_id', $data)) {
            if ($data['fournisseur_id'] === null) {
                $item->setFournisseur(null);
            } else {
                $fournisseur = $fournisseurRepository->find($data['fournisseur_id']);
                if (!$fournisseur) {
                    return $this->error('Fournisseur introuvable', Response::HTTP_NOT_FOUND);
                }
                $item->setFournisseur($fournisseur);
            }
        }
        if (array_key_exists('user_id', $data)) {
            if ($data['user_id'] === null) {
                $item->setUsers(null);
            } else {
                $user = $userRepository->find($data['user_id']);
                if (!$user) {
                    return $this->error('Utilisateur introuvable', Response::HTTP_NOT_FOUND);
                }
                $item->setUsers($user);
            }
        }

        $em->flush();

        return $this->success(['message' => 'Engagement mis à jour', 'data' => $this->serialize($item)]);
    }

    #[Route('/{id}', name: 'api_engagements_delete', methods: ['DELETE'])]
    #[IsGranted(PermissionVoter::MANAGE_ENGAGEMENTS)]
    public function delete(
        int $id,
        EngagementRepository $repository,
        EngagementWorkflowService $workflow,
        EntityManagerInterface $em,
    ): JsonResponse {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Engagement introuvable', Response::HTTP_NOT_FOUND);
        }

        $workflow->releaseBudget($item);
        $em->remove($item);
        $em->flush();

        return $this->success(['message' => 'Engagement supprimé']);
    }

    private function serialize(Engagement $item): array
    {
        $ligne = $item->getLigneBudgetaire();
        $budget = $ligne?->getBudget();
        $uo = $budget?->getUniteOperationnelle();
        $administration = $uo?->getAdministration();
        $province = $administration?->getProvince();
        $fournisseur = $item->getFournisseur();
        $user = $item->getUsers();
        $visePar = $item->getVisePar();

        return [
            'id' => $item->getId(),
            'numero' => $item->getNumero(),
            'titre' => $item->getTitre(),
            'montant' => (float) $item->getMontant(),
            'date' => $item->getDate()?->format('Y-m-d'),
            'statut' => $item->getStatut(),
            'ligne_budgetaire_id' => $ligne?->getId(),
            'ligne_budgetaire_libelle' => $ligne?->getLibelle(),
            'poste_comptable_id' => $item->getPosteComptable()?->getId(),
            'poste_comptable_libelle' => $item->getPosteComptable()?->getLibelle(),
            'fournisseur_id' => $fournisseur?->getId(),
            'fournisseur' => $fournisseur?->getNom(),
            'user_id' => $user?->getId(),
            'demandeur' => $user?->getNom(),
            'vise_par' => $visePar?->getNom(),
            'date_visa' => $item->getDateVisa()?->format('Y-m-d H:i:s'),
            'motif_rejet' => $item->getMotifRejet(),
            'budget_engage' => $item->isBudgetEngage(),
            'objet' => $item->getTitre() ?? $ligne?->getLibelle(),
            'province_id' => $province?->getId(),
            'province_nom' => $province?->getNom(),
            'administration_id' => $administration?->getId(),
            'administration_nom' => $administration?->getNom(),
            'unite_operationnelle_id' => $uo?->getId(),
            'unite_operationnelle_nom' => $uo?->getNom(),
            'administration' => $administration?->getNom(),
        ];
    }
}
