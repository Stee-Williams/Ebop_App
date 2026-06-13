<?php

namespace App\Controller;

use App\Controller\Trait\ApiResponseTrait;
use App\Entity\Reglement;
use App\Entity\User;
use App\Repository\EngagementRepository;
use App\Repository\ReglementRepository;
use App\Security\Voter\PermissionVoter;
use App\Service\EngagementWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/reglements')]
final class ReglementController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_reglements_list', methods: ['GET'])]
    #[IsGranted(PermissionVoter::MANAGE_REGLEMENTS)]
    public function list(ReglementRepository $repository): JsonResponse
    {
        $items = $repository->findBy([], ['dateReglement' => 'DESC', 'id' => 'DESC']);

        return $this->success(array_map(fn (Reglement $r) => $this->serialize($r), $items));
    }

    #[Route('', name: 'api_reglements_create', methods: ['POST'])]
    #[IsGranted(PermissionVoter::MANAGE_REGLEMENTS)]
    public function create(
        Request $request,
        EngagementRepository $engagementRepository,
        ReglementRepository $reglementRepository,
        EngagementWorkflowService $workflow,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = $this->decodeJson($request);
        if (!$data || empty($data['engagement_id']) || empty($data['mode_paiement']) || empty($data['date_reglement'])) {
            return $this->error('engagement_id, mode_paiement et date_reglement sont obligatoires');
        }

        $engagement = $engagementRepository->find($data['engagement_id']);
        if (!$engagement) {
            return $this->error('Engagement introuvable', Response::HTTP_NOT_FOUND);
        }

        if ($engagement->getStatut() !== 'Visé') {
            return $this->error('Seuls les engagements visés peuvent être réglés');
        }

        if (!$engagement->getReglements()->isEmpty()) {
            return $this->error('Cet engagement possède déjà un règlement', Response::HTTP_CONFLICT);
        }

        $modePaiement = trim((string) $data['mode_paiement']);
        if (strcasecmp($modePaiement, 'Virement') === 0) {
            $numeroCompte = trim((string) ($data['numero_compte'] ?? ''));
            $banqueFournisseur = trim((string) ($data['banque_fournisseur'] ?? ''));
            if ($numeroCompte === '' || $banqueFournisseur === '') {
                return $this->error('Le numéro de compte et la banque du fournisseur sont obligatoires pour un virement');
            }
        }

        $date = new \DateTime($data['date_reglement']);
        $year = (int) $date->format('Y');
        $reference = !empty($data['reference'])
            ? trim((string) $data['reference'])
            : $reglementRepository->generateNextReference($year);

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->error('Utilisateur non authentifié', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $workflow->onReglement($engagement);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage());
        }

        $reglement = new Reglement();
        $reglement->setReference($reference);
        $reglement->setEngagement($engagement);
        $reglement->setMontant($engagement->getMontant());
        $reglement->setModePaiement($modePaiement);
        $reglement->setNumeroCompte(
            strcasecmp($modePaiement, 'Virement') === 0
                ? trim((string) $data['numero_compte'])
                : null
        );
        $reglement->setBanqueFournisseur(
            strcasecmp($modePaiement, 'Virement') === 0
                ? trim((string) $data['banque_fournisseur'])
                : null
        );
        $reglement->setDateReglement($date);
        $reglement->setCreePar($user);

        $engagement->setStatut('Réglé');
        $engagement->addReglement($reglement);

        $em->persist($reglement);
        $em->flush();

        return $this->success([
            'message' => 'Règlement enregistré',
            'data' => $this->serialize($reglement),
        ], Response::HTTP_CREATED);
    }

    private function serialize(Reglement $item): array
    {
        $engagement = $item->getEngagement();
        $ligne = $engagement?->getLigneBudgetaire();
        $budget = $ligne?->getBudget();
        $uo = $budget?->getUniteOperationnelle();
        $administration = $uo?->getAdministration();
        $province = $administration?->getProvince();
        $fournisseur = $engagement?->getFournisseur();
        $saisiPar = $engagement?->getUsers();
        $visePar = $engagement?->getVisePar();

        return [
            'id' => $item->getId(),
            'reference' => $item->getReference(),
            'montant' => (float) $item->getMontant(),
            'mode_paiement' => $item->getModePaiement(),
            'numero_compte' => $item->getNumeroCompte(),
            'banque_fournisseur' => $item->getBanqueFournisseur(),
            'date_reglement' => $item->getDateReglement()?->format('Y-m-d'),
            'created_at' => $item->getCreatedAt()?->format('Y-m-d H:i:s'),
            'cree_par' => $item->getCreePar()?->getNom(),
            'engagement_id' => $engagement?->getId(),
            'engagement_numero' => $engagement?->getNumero(),
            'engagement_titre' => $engagement?->getTitre(),
            'engagement_statut' => $engagement?->getStatut(),
            'fournisseur' => $fournisseur?->getNom(),
            'demandeur' => $visePar?->getNom(),
            'saisi_par' => $saisiPar?->getNom(),
            'user_id' => $visePar?->getId() ?? $saisiPar?->getId(),
            'province_id' => $province?->getId(),
            'province_nom' => $province?->getNom(),
            'administration_id' => $administration?->getId(),
            'administration_nom' => $administration?->getNom(),
            'unite_operationnelle_id' => $uo?->getId(),
            'unite_operationnelle_nom' => $uo?->getNom(),
            'ligne_budgetaire_id' => $ligne?->getId(),
            'ligne_budgetaire_libelle' => $ligne?->getLibelle(),
            'poste_comptable_libelle' => $engagement?->getPosteComptable()?->getLibelle(),
        ];
    }
}
