<?php

namespace App\Entity;

use App\Enum\Type;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Poste;


#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]

class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(enumType: Type::class)]
    private ?Type $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoProfil = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $formation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etablissement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $decription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $experience = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cv = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $plus_sur_moi = null;

    #[ORM\OneToMany(targetEntity: Poste::class, mappedBy: 'entreprise')]
    private Collection $postes;
    
    #[ORM\ManyToMany(targetEntity: Poste::class)]
    #[ORM\JoinTable(name: 'utilisateur_poste_sauvegarde')]
    private Collection $postesSauvegardes;

    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'tuteurs')]
    #[ORM\JoinTable(name: 'tuteur_etudiant')]
    private Collection $etudiants;

    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'etudiants')]
    private Collection $tuteurs;

    public function __construct()
    {
        $this->postes = new ArrayCollection();
        $this->postesSauvegardes = new ArrayCollection();
        $this->etudiants = new ArrayCollection();
        $this->tuteurs = new ArrayCollection();
    }

    /**
     * @return Collection<int, Poste>
     */
    public function getPostesSauvegardes(): Collection
    {
        return $this->postesSauvegardes;
    }

    public function addPosteSauvegarde(Poste $poste): static
    {
        if (!$this->postesSauvegardes->contains($poste)) {
            $this->postesSauvegardes->add($poste);
        }

        return $this;
    }

    public function removePosteSauvegarde(Poste $poste): static
    {
        $this->postesSauvegardes->removeElement($poste);

        return $this;
    }

    public function hasPosteSauvegarde(Poste $poste): bool
    {
        return $this->postesSauvegardes->contains($poste);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }
    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        // Retourne toujours ROLE_USER + le rôle spécifique basé sur le type
        return ['ROLE_USER', 'ROLE_'.$this->type->name];
    }


    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function setType(Type $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPhotoProfil(): ?string
    {
        return $this->photoProfil;
    }

    public function setPhotoProfil(?string $photoProfil): static
    {
        $this->photoProfil = $photoProfil;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): static
    {
        $this->siret = $siret;

        return $this;
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

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getFormation(): ?string
    {
        return $this->formation;
    }

    public function setFormation(string $formation): static
    {
        $this->formation = $formation;

        return $this;
    }

    public function getEtablissement(): ?string
    {
        return $this->etablissement;
    }

    public function setEtablissement(string $etablissement): static
    {
        $this->etablissement = $etablissement;

        return $this;
    }

    public function getDecription(): ?string
    {
        return $this->decription;
    }

    public function setDecription(?string $decription): static
    {
        $this->decription = $decription;

        return $this;
    }

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(?string $experience): static
    {
        $this->experience = $experience;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getCv(): ?string
    {
        return $this->cv;
    }

    public function setCv(?string $cv): static
    {
        $this->cv = $cv;

        return $this;
    }

/**
 * @return Collection<int, Poste>
 */
public function getPostes(): Collection
{
    return $this->postes;
}

public function addPoste(Poste $poste): static
{
    if (!$this->postes->contains($poste)) {
        $this->postes->add($poste);
        $poste->setEntreprise($this);
    }
    return $this;
}

public function removePoste(Poste $poste): static
{
    if ($this->postes->removeElement($poste)) {
        // set the owning side to null (unless already changed)
        if ($poste->getEntreprise() === $this) {
            $poste->setEntreprise(null);
        }
    }
    return $this;
}



    public function getPlusSurMoi(): ?string
    {
        return $this->plus_sur_moi;
    }

    public function setPlusSurMoi(?string $plus_sur_moi): static
    {
        $this->plus_sur_moi = $plus_sur_moi;

        return $this;
    }


    /**
 * @return Collection<int, Utilisateur>
 */
public function getEtudiants(): Collection
{
    return $this->etudiants;
}

public function addEtudiant(self $etudiant): static
{
    if (!$this->etudiants->contains($etudiant)) {
        $this->etudiants->add($etudiant);
        $etudiant->addTuteur($this);
    }

    return $this;
}

public function removeEtudiant(self $etudiant): static
{
    if ($this->etudiants->removeElement($etudiant)) {
        $etudiant->removeTuteur($this);
    }

    return $this;
}

/**
 * @return Collection<int, Utilisateur>
 */
public function getTuteurs(): Collection
{
    return $this->tuteurs;
}

public function addTuteur(self $tuteur): static
{
    if (!$this->tuteurs->contains($tuteur)) {
        $this->tuteurs->add($tuteur);
    }

    return $this;
}

public function removeTuteur(self $tuteur): static
{
    $this->tuteurs->removeElement($tuteur);

    return $this;
}

public function hasEtudiant(self $etudiant): bool
{
    return $this->etudiants->contains($etudiant);
}
}
