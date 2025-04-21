<?php

namespace App\Entity;

use App\Enum\Contrat;
use App\Enum\Duree;
use App\Enum\Type_presence;
use App\Repository\PosteRepository;
use App\Entity\Utilisateur;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(enumType: Contrat::class)]
    private ?Contrat $contrat_type = null;


    #[ORM\Column(length: 255)]
    private ?string $domaine = null;

    #[ORM\Column(length: 255)]
    private ?string $localisation = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $profil_recherche = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $info_supp = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $presentation_entreprise = null;

    #[ORM\Column(length: 255)]
    private ?string $contact = null;

    #[ORM\Column]
    private ?int $salaire = null;

    #[ORM\Column(enumType: Type_presence::class)]
    private ?Type_presence $presence = null;

    #[ORM\Column(enumType: Duree::class)]
    private ?Duree $duree = null;


    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'postes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $entreprise = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $missions = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'poste', targetEntity: Candidature::class, cascade: ['persist', 'remove'])]
    private Collection $candidatures;

    public function __construct()
    {
        $this->candidatures = new ArrayCollection();
    }

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
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Contrat|null
     */
    public function getContratType(): ?Contrat
    {
        return $this->contrat_type;
    }

    public function setContratType(?Contrat $contrat_type): static
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
        return $this->localisation;
    }

    public function setLocalisation(string $localisation): static
    {
        $this->localisation = $localisation;

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
    public function getDuree(): ?Duree
    {
        return $this->duree;
    }
    
    public function setDuree(?Duree $duree): static
    {
        $this->duree = $duree;
        return $this;
    }


    public function getEntreprise(): ?Utilisateur
    {
        return $this->entreprise;
    }
    
    public function setEntreprise(?Utilisateur $entreprise): static
    {
        $this->entreprise = $entreprise;
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Collection<int, Candidature>
     */
    public function getCandidatures(): Collection
    {
        return $this->candidatures;
    }

    public function addCandidature(Candidature $candidature): self
    {
        if (!$this->candidatures->contains($candidature)) {
            $this->candidatures->add($candidature);
            $candidature->setPoste($this);
        }

        return $this;
    }

    public function removeCandidature(Candidature $candidature): self
    {
        if ($this->candidatures->removeElement($candidature)) {
            // Set the owning side to null (unless already changed)
            if ($candidature->getPoste() === $this) {
                $candidature->setPoste(null);
            }
        }

        return $this;
    }
}
