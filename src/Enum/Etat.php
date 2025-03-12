<?php

namespace App\Enum;

enum Etat : string 
{
    case ACCEPTED = 'ACCEPTED';
    case WAITING = 'WAITING';
    case REFUSED = 'REFUSED';

}
?>