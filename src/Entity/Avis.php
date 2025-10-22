<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvisRepository::class)]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $noteGlobale = null;

    #[ORM\Column(nullable: true)]
    private ?int $notePropreteConformite = null;

    #[ORM\Column(nullable: true)]
    private ?int $noteEmplacement = null;

    #[ORM\Column(nullable: true)]
    private ?int $noteQualitePrix = null;

    #[ORM\Column(nullable: true)]
    private ?int $noteCommunication = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(length: 20)]
    private ?string $typeAuteur = null;

    #[ORM\Column]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(nullable:true)]
    private ?\DateTime $datePublication = null;

    #[ORM\Column]
    private ?bool $estPublie = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reponse = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateReponse = null;

    #[ORM\ManyToOne(inversedBy: 'avis')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reservation $reservation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNoteGlobale(): ?int
    {
        return $this->noteGlobale;
    }

    public function setNoteGlobale(int $noteGlobale): static
    {
        $this->noteGlobale = $noteGlobale;

        return $this;
    }

    public function getNotePropreteConformite(): ?int
    {
        return $this->notePropreteConformite;
    }

    public function setNotePropreteConformite(?int $notePropreteConformite): static
    {
        $this->notePropreteConformite = $notePropreteConformite;

        return $this;
    }

    public function getNoteEmplacement(): ?int
    {
        return $this->noteEmplacement;
    }

    public function setNoteEmplacement(?int $noteEmplacement): static
    {
        $this->noteEmplacement = $noteEmplacement;

        return $this;
    }

    public function getNoteQualitePrix(): ?int
    {
        return $this->noteQualitePrix;
    }

    public function setNoteQualitePrix(?int $noteQualitePrix): static
    {
        $this->noteQualitePrix = $noteQualitePrix;

        return $this;
    }

    public function getNoteCommunication(): ?int
    {
        return $this->noteCommunication;
    }

    public function setNoteCommunication(?int $noteCommunication): static
    {
        $this->noteCommunication = $noteCommunication;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getTypeAuteur(): ?string
    {
        return $this->typeAuteur;
    }

    public function setTypeAuteur(string $typeAuteur): static
    {
        $this->typeAuteur = $typeAuteur;

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

    public function getDatePublication(): ?\DateTime
    {
        return $this->datePublication;
    }

    public function setDatePublication(?\DateTime $datePublication): static
    {
        $this->datePublication = $datePublication;

        return $this;
    }

    public function isEstPublie(): ?bool
    {
        return $this->estPublie;
    }

    public function setEstPublie(bool $estPublie): static
    {
        $this->estPublie = $estPublie;

        return $this;
    }

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(?string $reponse): static
    {
        $this->reponse = $reponse;

        return $this;
    }

    public function getDateReponse(): ?\DateTime
    {
        return $this->dateReponse;
    }

    public function setDateReponse(\DateTime $dateReponse): static
    {
        $this->dateReponse = $dateReponse;

        return $this;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): static
    {
        $this->reservation = $reservation;

        return $this;
    }
}
