<?php

namespace App\Entity;

use App\Enum\Etat;
use App\Repository\CandidatureRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Utilisateur; 
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
class Candidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: Etat::class)]
    private ?Etat $etat = null;


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motivation = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $cvCandidature = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtat(): ?Etat
    {
        return $this->etat;
    }

    public function setEtat(Etat $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    

    public function getMotivation(): ?string
    {
        return $this->motivation ;
    }

    public function setMotivation (?string $motivation): static
    {
        $this->motivation = $motivation ;

        return $this;
    }

    public function getCvCandidature(): ?string
    {
        return $this->cvCandidature;
    }

    public function setCvCandidature(string $cvCandidature): static
    {
        $this->cvCandidature = $cvCandidature;

        return $this;
    }
    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
    
    
    
    
    
    private ?Poste $poste = null;

    public function getPoste(): ?Poste
    {
        return $this->poste;
    }

    public function setPoste(?Poste $poste): self
    {
        $this->poste = $poste;
        return $this;
    }
}
