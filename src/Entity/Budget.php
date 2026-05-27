<?php

namespace App\Entity;

use App\Repository\BudgetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BudgetRepository::class)]
class Budget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Année budgétaire (ex: 2026)
    #[ORM\Column]
    private ?int $annee = null;

    // Montant total du budget
    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private ?string $montant = null;

    // Libellé du budget
    #[ORM\Column(length: 150)]
    private ?string $libelle = null;

    #[ORM\ManyToOne(inversedBy: 'budgets')]
    private ?UniteOperationnelle $uniteOperationnelle = null;

    /**
     * @var Collection<int, LigneBudgetaire>
     */
    #[ORM\OneToMany(targetEntity: LigneBudgetaire::class, mappedBy: 'budget')]
    private Collection $ligneBudgetaires;

    public function __construct()
    {
        $this->ligneBudgetaires = new ArrayCollection();
    }

    // ======================
    // GETTERS & SETTERS
    // ======================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(int $annee): static
    {
        $this->annee = $annee;
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

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }

    public function getUniteOperationnelle(): ?UniteOperationnelle
    {
        return $this->uniteOperationnelle;
    }

    public function setUniteOperationnelle(?UniteOperationnelle $uniteOperationnelle): static
    {
        $this->uniteOperationnelle = $uniteOperationnelle;

        return $this;
    }

    /**
     * @return Collection<int, LigneBudgetaire>
     */
    public function getLigneBudgetaires(): Collection
    {
        return $this->ligneBudgetaires;
    }

    public function addLigneBudgetaire(LigneBudgetaire $ligneBudgetaire): static
    {
        if (!$this->ligneBudgetaires->contains($ligneBudgetaire)) {
            $this->ligneBudgetaires->add($ligneBudgetaire);
            $ligneBudgetaire->setBudget($this);
        }

        return $this;
    }

    public function removeLigneBudgetaire(LigneBudgetaire $ligneBudgetaire): static
    {
        if ($this->ligneBudgetaires->removeElement($ligneBudgetaire)) {
            // set the owning side to null (unless already changed)
            if ($ligneBudgetaire->getBudget() === $this) {
                $ligneBudgetaire->setBudget(null);
            }
        }

        return $this;
    }
}