<?php

namespace App\Service;

class ErrorService
{
    public function message():string
    {
        return "Une erreur interne est survenue. Veuillez réessayer plus tard. Si le problème persiste, contactez le support.";
    }
}
