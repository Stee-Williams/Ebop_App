<?php

namespace App\Entity;

use App\Repository\UniteOperationnelleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UniteOperationnelleRepository::class)]
class UniteOperationnelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $code = null;

    #[ORM\ManyToOne(inversedBy: 'uniteOperationnelles')]
    private ?administration $administration = null;

    /**
     * @var Collection<int, Budget>
     */
    #[ORM\OneToMany(targetEntity: Budget::class, mappedBy: 'uniteOperationnelle')]
    private Collection $budgets;

    public function __construct()
    {
        $this->budgets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
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

    public function getAdministration(): ?administration
    {
        return $this->administration;
    }

    public function setAdministration(?administration $administration): static
    {
        $this->administration = $administration;

        return $this;
    }

    /**
     * @return Collection<int, Budget>
     */
    public function getBudgets(): Collection
    {
        return $this->budgets;
    }

    public function addBudget(Budget $budget): static
    {
        if (!$this->budgets->contains($budget)) {
            $this->budgets->add($budget);
            $budget->setUniteOperationnelle($this);
        }

        return $this;
    }

    public function removeBudget(Budget $budget): static
    {
        if ($this->budgets->removeElement($budget)) {
            // set the owning side to null (unless already changed)
            if ($budget->getUniteOperationnelle() === $this) {
                $budget->setUniteOperationnelle(null);
            }
        }

        return $this;
    }
}
