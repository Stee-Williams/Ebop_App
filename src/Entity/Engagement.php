<?php

namespace App\Entity;

use App\Repository\EngagementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EngagementRepository::class)]
class Engagement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    
    #[ORM\Column(length: 50, unique: true)]
    private ?string $numero = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'engagements')]
    private ?LigneBudgetaire $ligneBudgetaire = null;

    #[ORM\ManyToOne(inversedBy: 'engagements')]
    private ?PosteComptable $posteComptable = null;

    #[ORM\ManyToOne(inversedBy: 'engagements')]
    private ?Fournisseur $fournisseur = null;

    #[ORM\ManyToOne(inversedBy: 'engagements')]
    private ?User $users = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $visePar = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateVisa = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motifRejet = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $budgetEngage = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $budgetDecaisse = false;

    /**
     * @var Collection<int, Reglement>
     */
    #[ORM\OneToMany(targetEntity: Reglement::class, mappedBy: 'engagement')]
    private Collection $reglements;

    public function __construct()
    {
        $this->reglements = new ArrayCollection();
    }

    // GETTERS & SETTERS
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getLigneBudgetaire(): ?LigneBudgetaire
    {
        return $this->ligneBudgetaire;
    }

    public function setLigneBudgetaire(?LigneBudgetaire $ligneBudgetaire): static
    {
        $this->ligneBudgetaire = $ligneBudgetaire;

        return $this;
    }

    public function getPosteComptable(): ?PosteComptable
    {
        return $this->posteComptable;
    }

    public function setPosteComptable(?PosteComptable $posteComptable): static
    {
        $this->posteComptable = $posteComptable;

        return $this;
    }

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseur $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getUsers(): ?User
    {
        return $this->users;
    }

    public function setUsers(?User $users): static
    {
        $this->users = $users;

        return $this;
    }

    public function getVisePar(): ?User
    {
        return $this->visePar;
    }

    public function setVisePar(?User $visePar): static
    {
        $this->visePar = $visePar;

        return $this;
    }

    public function getDateVisa(): ?\DateTimeImmutable
    {
        return $this->dateVisa;
    }

    public function setDateVisa(?\DateTimeImmutable $dateVisa): static
    {
        $this->dateVisa = $dateVisa;

        return $this;
    }

    public function getMotifRejet(): ?string
    {
        return $this->motifRejet;
    }

    public function setMotifRejet(?string $motifRejet): static
    {
        $this->motifRejet = $motifRejet;

        return $this;
    }

    public function isBudgetEngage(): bool
    {
        return $this->budgetEngage;
    }

    public function setBudgetEngage(bool $budgetEngage): static
    {
        $this->budgetEngage = $budgetEngage;

        return $this;
    }

    public function isBudgetDecaisse(): bool
    {
        return $this->budgetDecaisse;
    }

    public function setBudgetDecaisse(bool $budgetDecaisse): static
    {
        $this->budgetDecaisse = $budgetDecaisse;

        return $this;
    }

    /**
     * @return Collection<int, Reglement>
     */
    public function getReglements(): Collection
    {
        return $this->reglements;
    }

    public function addReglement(Reglement $reglement): static
    {
        if (!$this->reglements->contains($reglement)) {
            $this->reglements->add($reglement);
            $reglement->setEngagement($this);
        }

        return $this;
    }
}