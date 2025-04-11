<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getHeaderTemplate', [$this, 'getHeaderTemplate']),
            new TwigFunction('getFooterTemplate', [$this, 'getFooterTemplate']),
        ];
    }

    public function getHeaderTemplate(): string
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return 'partials/header.html.twig';
        }
        
        // Adapte cette logique en fonction de ton entité utilisateur
        if (method_exists($user, 'getRoles')) {
            $roles = $user->getRoles();
            
            if (in_array('ROLE_ETUDIANT', $roles)) {
                return 'partials/header_etu.html.twig';
            } elseif (in_array('ROLE_TUTEUR', $roles)) {
                return 'partials/header_tuteur.html.twig';
            } elseif (in_array('ROLE_ENTREPRISE', $roles)) {
                return 'partials/header_entreprise.html.twig';
            }
        }
        
        // Header par défaut
        return 'partials/header.html.twig';
    }
    public function getFooterTemplate(): string
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return 'partials/footer.html.twig';
        }
        
        // Adapte cette logique en fonction de ton entité utilisateur
        if (method_exists($user, 'getRoles')) {
            $roles = $user->getRoles();
            
            if (in_array('ROLE_ETUDIANT', $roles)) {
                return 'partials/footer_etu.html.twig';
            } elseif (in_array('ROLE_TUTEUR', $roles)) {
                return 'partials/footer_tuteur.html.twig';
            } elseif (in_array('ROLE_ENTREPRISE', $roles)) {
                return 'partials/footer_entreprise.html.twig';
            }
        }
        
        // Header par défaut
        return 'partials/footer.html.twig';
    }
}