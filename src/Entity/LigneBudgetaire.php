<?php

namespace App\Entity;

use App\Repository\LigneBudgetaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneBudgetaireRepository::class)]
class LigneBudgetaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 150)]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantAlloue = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantUtilise = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['default' => '0'])]
    private string $montantDecaisse = '0';

    #[ORM\ManyToOne(inversedBy: 'ligneBudgetaires')]
    private ?Budget $budget = null;

    /**
     * @var Collection<int, Engagement>
     */
    #[ORM\OneToMany(targetEntity: Engagement::class, mappedBy: 'ligneBudgetaire')]
    private Collection $engagements;

    public function __construct()
    {
        $this->engagements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

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

    public function getMontantAlloue(): ?string
    {
        return $this->montantAlloue;
    }

    public function setMontantAlloue(string $montantAlloue): static
    {
        $this->montantAlloue = $montantAlloue;

        return $this;
    }

    public function getMontantUtilise(): ?string
    {
        return $this->montantUtilise;
    }

    public function setMontantUtilise(string $montantUtilise): static
    {
        $this->montantUtilise = $montantUtilise;

        return $this;
    }

    public function getMontantDecaisse(): string
    {
        return $this->montantDecaisse;
    }

    public function setMontantDecaisse(string $montantDecaisse): static
    {
        $this->montantDecaisse = $montantDecaisse;

        return $this;
    }

    public function getBudget(): ?Budget
    {
        return $this->budget;
    }

    public function setBudget(?Budget $budget): static
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * @return Collection<int, Engagement>
     */
    public function getEngagements(): Collection
    {
        return $this->engagements;
    }

    public function addEngagement(Engagement $engagement): static
    {
        if (!$this->engagements->contains($engagement)) {
            $this->engagements->add($engagement);
            $engagement->setLigneBudgetaire($this);
        }

        return $this;
    }

    public function removeEngagement(Engagement $engagement): static
    {
        if ($this->engagements->removeElement($engagement)) {
            // set the owning side to null (unless already changed)
            if ($engagement->getLigneBudgetaire() === $this) {
                $engagement->setLigneBudgetaire(null);
            }
        }

        return $this;
    }
}
