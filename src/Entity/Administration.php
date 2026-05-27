<?php

namespace App\Entity;

use App\Repository\AdministrationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdministrationRepository::class)]
class Administration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Nom de l'administration
    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    // Code administratif (souvent alphanumérique)
    #[ORM\Column(length: 20, unique: true)]
    private ?string $code = null;

    #[ORM\ManyToOne(inversedBy: 'administrations')]
    private ?Province $province = null;

    /**
     * @var Collection<int, UniteOperationnelle>
     */
    #[ORM\OneToMany(targetEntity: UniteOperationnelle::class, mappedBy: 'administration')]
    private Collection $uniteOperationnelles;

    public function __construct()
    {
        $this->uniteOperationnelles = new ArrayCollection();
    }

    // ======================
    // GETTERS & SETTERS
    // ======================

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

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getProvince(): ?Province
    {
        return $this->province;
    }

    public function setProvince(?Province $province): static
    {
        $this->province = $province;

        return $this;
    }

    /**
     * @return Collection<int, UniteOperationnelle>
     */
    public function getUniteOperationnelles(): Collection
    {
        return $this->uniteOperationnelles;
    }

    public function addUniteOperationnelle(UniteOperationnelle $uniteOperationnelle): static
    {
        if (!$this->uniteOperationnelles->contains($uniteOperationnelle)) {
            $this->uniteOperationnelles->add($uniteOperationnelle);
            $uniteOperationnelle->setAdministration($this);
        }

        return $this;
    }

    public function removeUniteOperationnelle(UniteOperationnelle $uniteOperationnelle): static
    {
        if ($this->uniteOperationnelles->removeElement($uniteOperationnelle)) {
            // set the owning side to null (unless already changed)
            if ($uniteOperationnelle->getAdministration() === $this) {
                $uniteOperationnelle->setAdministration(null);
            }
        }

        return $this;
    }
}