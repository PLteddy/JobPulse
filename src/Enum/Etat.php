<?php

namespace App\Enum;

enum Etat : string 
{
    case ACCEPTE = 'Accepte';
    case EN_ATTENTE = 'En attente';
    case REFUSE = 'Refuse';

}
?>