<?php

namespace App\Entity;

use App\Repository\AdministrateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity(repositoryClass: AdministrateurRepository::class)]
class Administrateur extends User
{
    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    /**
     * @var Collection<int, CentreCommercial>
     */
    #[ORM\OneToMany(targetEntity: CentreCommercial::class, mappedBy: 'adminValidateur')]
    private Collection $centreValides;

    public function __construct()
    {
        $this->centreValides = new ArrayCollection();
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = parent::getRoles();
        $roles[] = 'ROLE_ADMIN';
        return array_unique($roles);
    }

    /**
     * @return Collection<int, CentreCommercial>
     */
    public function getCentreValides(): Collection
    {
        return $this->centreValides;
    }

    public function addCentreValide(CentreCommercial $centreValide): static
    {
        if (!$this->centreValides->contains($centreValide)) {
            $this->centreValides->add($centreValide);
            $centreValide->setAdminValidateur($this);
        }

        return $this;
    }

    public function removeCentreValide(CentreCommercial $centreValide): static
    {
        if ($this->centreValides->removeElement($centreValide)) {
            // set the owning side to null (unless already changed)
            if ($centreValide->getAdminValidateur() === $this) {
                $centreValide->setAdminValidateur(null);
            }
        }

        return $this;
    }
}
