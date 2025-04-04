<?php

namespace App\Entity;

use App\Enum\Etat;
use App\Repository\CandidatureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
class Candidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: Etat::class)]
    private ?Etat $etat = null;

    #[ORM\Column]
    private ?bool $enregistre = null;

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

    public function isEnregistre(): ?bool
    {
        return $this->enregistre;
    }

    public function setEnregistre(bool $enregistre): static
    {
        $this->enregistre = $enregistre;

        return $this;
    }
}
