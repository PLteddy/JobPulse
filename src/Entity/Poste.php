<?php

namespace App\Entity;

use App\Enum\Contrat;
use App\Enum\Duree;
use App\Enum\Type_presence;
use App\Repository\PosteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PosteRepository::class)]
class Poste
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $intitule = null;

    #[ORM\Column(length: 500)]
    private ?string $description = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, enumType: Contrat::class)]
    private array $contrat_type = [];

    #[ORM\Column(length: 255)]
    private ?string $domaine = null;

    #[ORM\Column(length: 255)]
    private ?string $localisation = null;

    #[ORM\Column(length: 255)]
    private ?string $profil_recherche = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $info_supp = null;

    #[ORM\Column(length: 500)]
    private ?string $presentation_entreprise = null;

    #[ORM\Column(length: 255)]
    private ?string $contact = null;

    #[ORM\Column]
    private ?int $salaire = null;

    #[ORM\Column(enumType: Type_presence::class)]
    private ?Type_presence $presence = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, enumType: Duree::class)]
    private array $duree = [];

    #[ORM\Column(length: 500)]
    private ?string $missions = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntitule(): ?string
    {
        return $this->intitule;
    }

    public function setIntitule(string $intitule): static
    {
        $this->intitule = $intitule;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(string $Description): static
    {
        $this->Description = $Description;

        return $this;
    }

    /**
     * @return Contrat[]
     */
    public function getContratType(): array
    {
        return $this->contrat_type;
    }

    public function setContratType(array $contrat_type): static
    {
        $this->contrat_type = $contrat_type;

        return $this;
    }

    public function getDomaine(): ?string
    {
        return $this->domaine;
    }

    public function setDomaine(string $domaine): static
    {
        $this->domaine = $domaine;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->Localisation;
    }

    public function setLocalisation(string $Localisation): static
    {
        $this->Localisation = $Localisation;

        return $this;
    }

    public function getProfilRecherche(): ?string
    {
        return $this->profil_recherche;
    }

    public function setProfilRecherche(string $profil_recherche): static
    {
        $this->profil_recherche = $profil_recherche;

        return $this;
    }

    public function getInfoSupp(): ?string
    {
        return $this->info_supp;
    }

    public function setInfoSupp(?string $info_supp): static
    {
        $this->info_supp = $info_supp;

        return $this;
    }

    public function getPresentationEntreprise(): ?string
    {
        return $this->presentation_entreprise;
    }

    public function setPresentationEntreprise(string $presentation_entreprise): static
    {
        $this->presentation_entreprise = $presentation_entreprise;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getSalaire(): ?int
    {
        return $this->salaire;
    }

    public function setSalaire(int $salaire): static
    {
        $this->salaire = $salaire;

        return $this;
    }

    public function getPresence(): ?Type_presence
    {
        return $this->presence;
    }

    public function setPresence(Type_presence $presence): static
    {
        $this->presence = $presence;

        return $this;
    }

    /**
     * @return Duree[]
     */
    public function getDuree(): array
    {
        return $this->duree;
    }

    public function setDuree(array $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getMissions(): ?string
    {
        return $this->missions;
    }

    public function setMissions(string $missions): static
    {
        $this->missions = $missions;

        return $this;
    }
}
