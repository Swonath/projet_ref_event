<?php

namespace App\Entity;

use App\Repository\EmplacementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmplacementRepository::class)]
class Emplacement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titreAnnonce = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $surface = null;

    #[ORM\Column(length: 255, nullable:true)]
    private ?string $localisationPrecise = null;

    #[ORM\Column(length: 50)]
    private ?string $typeEmplacement = null;

    #[ORM\Column(type: Types::TEXT, nullable:true)]
    private ?string $equipements = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $tarifJour = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable:true)]
    private ?string $tarifSemaine = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $tarifMois = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $caution = null;

    #[ORM\Column(nullable: true)]
    private ?int $dureeMinLocation = null;

    #[ORM\Column(nullable: true)]
    private ?int $dureeMaxLocation = null;

    #[ORM\Column(length: 20)]
    private ?string $statutAnnonce = null;

    #[ORM\Column]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(nullable:true)]
    private ?\DateTime $dateModification = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombreVues = null;

    #[ORM\ManyToOne(inversedBy: 'emplacements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CentreCommercial $centreCommercial = null;

    /**
     * @var Collection<int, Photo>
     */
    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: 'emplacement', orphanRemoval: true)]
    private Collection $photos;

    /**
     * @var Collection<int, PeriodeIndisponibilite>
     */
    #[ORM\OneToMany(targetEntity: PeriodeIndisponibilite::class, mappedBy: 'emplacement', orphanRemoval: true)]
    private Collection $periodesIndisponibilite;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'emplacement')]
    private Collection $reservations;

    /**
     * @var Collection<int, Favori>
     */
    #[ORM\OneToMany(targetEntity: Favori::class, mappedBy: 'emplacement', orphanRemoval: true)]
    private Collection $favoris;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->periodesIndisponibilite = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->favoris = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitreAnnonce(): ?string
    {
        return $this->titreAnnonce;
    }

    public function setTitreAnnonce(string $titreAnnonce): static
    {
        $this->titreAnnonce = $titreAnnonce;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSurface(): ?string
    {
        return $this->surface;
    }

    public function setSurface(string $surface): static
    {
        $this->surface = $surface;

        return $this;
    }

    public function getLocalisationPrecise(): ?string
    {
        return $this->localisationPrecise;
    }

    public function setLocalisationPrecise(string $localisationPrecise): static
    {
        $this->localisationPrecise = $localisationPrecise;

        return $this;
    }

    public function getTypeEmplacement(): ?string
    {
        return $this->typeEmplacement;
    }

    public function setTypeEmplacement(string $typeEmplacement): static
    {
        $this->typeEmplacement = $typeEmplacement;

        return $this;
    }

    public function getEquipements(): ?string
    {
        return $this->equipements;
    }

    public function setEquipements(string $equipements): static
    {
        $this->equipements = $equipements;

        return $this;
    }

    public function getTarifJour(): ?string
    {
        return $this->tarifJour;
    }

    public function setTarifJour(string $tarifJour): static
    {
        $this->tarifJour = $tarifJour;

        return $this;
    }

    public function getTarifSemaine(): ?string
    {
        return $this->tarifSemaine;
    }

    public function setTarifSemaine(string $tarifSemaine): static
    {
        $this->tarifSemaine = $tarifSemaine;

        return $this;
    }

    public function getTarifMois(): ?string
    {
        return $this->tarifMois;
    }

    public function setTarifMois(?string $tarifMois): static
    {
        $this->tarifMois = $tarifMois;

        return $this;
    }

    public function getCaution(): ?string
    {
        return $this->caution;
    }

    public function setCaution(?string $caution): static
    {
        $this->caution = $caution;

        return $this;
    }

    public function getDureeMinLocation(): ?int
    {
        return $this->dureeMinLocation;
    }

    public function setDureeMinLocation(?int $dureeMinLocation): static
    {
        $this->dureeMinLocation = $dureeMinLocation;

        return $this;
    }

    public function getDureeMaxLocation(): ?int
    {
        return $this->dureeMaxLocation;
    }

    public function setDureeMaxLocation(?int $dureeMaxLocation): static
    {
        $this->dureeMaxLocation = $dureeMaxLocation;

        return $this;
    }

    public function getStatutAnnonce(): ?string
    {
        return $this->statutAnnonce;
    }

    public function setStatutAnnonce(string $statutAnnonce): static
    {
        $this->statutAnnonce = $statutAnnonce;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateModification(): ?\DateTime
    {
        return $this->dateModification;
    }

    public function setDateModification(\DateTime $dateModification): static
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    public function getNombreVues(): ?int
    {
        return $this->nombreVues;
    }

    public function setNombreVues(?int $nombreVues): static
    {
        $this->nombreVues = $nombreVues;

        return $this;
    }

    public function getCentreCommercial(): ?CentreCommercial
    {
        return $this->centreCommercial;
    }

    public function setCentreCommercial(?CentreCommercial $centreCommercial): static
    {
        $this->centreCommercial = $centreCommercial;

        return $this;
    }

    /**
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setEmplacement($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): static
    {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getEmplacement() === $this) {
                $photo->setEmplacement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PeriodeIndisponibilite>
     */
    public function getPeriodesIndisponibilite(): Collection
    {
        return $this->periodesIndisponibilite;
    }

    public function addPeriodesIndisponibilite(PeriodeIndisponibilite $periodesIndisponibilite): static
    {
        if (!$this->periodesIndisponibilite->contains($periodesIndisponibilite)) {
            $this->periodesIndisponibilite->add($periodesIndisponibilite);
            $periodesIndisponibilite->setEmplacement($this);
        }

        return $this;
    }

    public function removePeriodesIndisponibilite(PeriodeIndisponibilite $periodesIndisponibilite): static
    {
        if ($this->periodesIndisponibilite->removeElement($periodesIndisponibilite)) {
            // set the owning side to null (unless already changed)
            if ($periodesIndisponibilite->getEmplacement() === $this) {
                $periodesIndisponibilite->setEmplacement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setEmplacement($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getEmplacement() === $this) {
                $reservation->setEmplacement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Favori>
     */
    public function getFavoris(): Collection
    {
        return $this->favoris;
    }

    public function addFavori(Favori $favori): static
    {
        if (!$this->favoris->contains($favori)) {
            $this->favoris->add($favori);
            $favori->setEmplacement($this);
        }

        return $this;
    }

    public function removeFavori(Favori $favori): static
    {
        if ($this->favoris->removeElement($favori)) {
            // set the owning side to null (unless already changed)
            if ($favori->getEmplacement() === $this) {
                $favori->setEmplacement(null);
            }
        }

        return $this;
    }
}
