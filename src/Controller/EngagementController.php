<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\Engagement;
use App\Repository\EngagementRepository;
use App\Repository\FournisseurRepository;
use App\Repository\LigneBudgetaireRepository;
use App\Repository\PosteComptableRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/engagements')]
final class EngagementController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_engagements_list', methods: ['GET'])]
    public function list(EngagementRepository $repository): JsonResponse
    {
        $items = $repository->findBy([], ['date' => 'DESC']);

        return $this->success(array_map(fn (Engagement $e) => $this->serialize($e), $items));
    }

    #[Route('/vises', name: 'api_engagements_vises', methods: ['GET'])]
    public function vises(EngagementRepository $repository): JsonResponse
    {
        $items = $repository->findBy(['statut' => 'Visé'], ['date' => 'DESC']);

        return $this->success(array_map(fn (Engagement $e) => $this->serialize($e), $items));
    }

    #[Route('/{id}', name: 'api_engagements_show', methods: ['GET'])]
    public function show(int $id, EngagementRepository $repository): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Engagement introuvable', Response::HTTP_NOT_FOUND);
        }

        return $this->success($this->serialize($item));
    }

    #[Route('', name: 'api_engagements_create', methods: ['POST'])]
    public function create(
        Request $request,
        LigneBudgetaireRepository $ligneRepository,
        PosteComptableRepository $posteRepository,
        FournisseurRepository $fournisseurRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        if (
            !$data
            || empty($data['numero'])
            || !isset($data['montant'])
            || empty($data['date'])
            || empty($data['statut'])
        ) {
            return $this->error('Numéro, montant, date et statut sont obligatoires');
        }

        $item = new Engagement();
        $item->setNumero($data['numero']);
        $item->setMontant((string) $data['montant']);
        $item->setDate(new \DateTime($data['date']));
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
        EntityManagerInterface $em,
    ): JsonResponse {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Engagement introuvable', Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if (isset($data['numero'])) {
            $item->setNumero($data['numero']);
        }
        if (isset($data['montant'])) {
            $item->setMontant((string) $data['montant']);
        }
        if (isset($data['date'])) {
            $item->setDate(new \DateTime($data['date']));
        }
        if (isset($data['statut'])) {
            $item->setStatut($data['statut']);
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
    public function delete(int $id, EngagementRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        $item = $repository->find($id);
        if (!$item) {
            return $this->error('Engagement introuvable', Response::HTTP_NOT_FOUND);
        }

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

        return [
            'id' => $item->getId(),
            'numero' => $item->getNumero(),
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
            'objet' => $ligne?->getLibelle(),
            'titre' => $ligne?->getLibelle(),
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
