<?php

namespace App\Entity;

use App\Repository\ProvinceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProvinceRepository::class)]
class Province
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $code = null;

    /**
     * @var Collection<int, Administration>
     */
    #[ORM\OneToMany(targetEntity: Administration::class, mappedBy: 'province')]
    private Collection $administrations;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'province')]
    private Collection $users;

    public function __construct()
    {
        $this->administrations = new ArrayCollection();
        $this->users = new ArrayCollection();
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

    /**
     * @return Collection<int, Administration>
     */
    public function getAdministrations(): Collection
    {
        return $this->administrations;
    }

    public function addAdministration(Administration $administration): static
    {
        if (!$this->administrations->contains($administration)) {
            $this->administrations->add($administration);
            $administration->setProvince($this);
        }

        return $this;
    }

    public function removeAdministration(Administration $administration): static
    {
        if ($this->administrations->removeElement($administration)) {
            // set the owning side to null (unless already changed)
            if ($administration->getProvince() === $this) {
                $administration->setProvince(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setProvince($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getProvince() === $this) {
                $user->setProvince(null);
            }
        }

        return $this;
    }
}