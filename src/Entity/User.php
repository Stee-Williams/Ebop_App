<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $matricule = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Role $role = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Role $Role = null;

    /**
     * @var Collection<int, Engagement>
     */
    #[ORM\OneToMany(targetEntity: Engagement::class, mappedBy: 'users')]
    private Collection $engagements;

    public function __construct()
    {
        $this->engagements = new ArrayCollection();
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

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(string $matricule): static
    {
        $this->matricule = $matricule;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;
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
            $engagement->setUsers($this);
        }

        return $this;
    }

    public function removeEngagement(Engagement $engagement): static
    {
        if ($this->engagements->removeElement($engagement)) {
            // set the owning side to null (unless already changed)
            if ($engagement->getUsers() === $this) {
                $engagement->setUsers(null);
            }
        }

        return $this;
    }
}