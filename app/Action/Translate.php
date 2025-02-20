<?php

namespace App\Action;

class Translate
{
    protected array $translations = [
        'editor' => 'Éditeur',
        'admin' => 'Administrateur',
        'visitor' => 'Visiteur',
    ];
    public function __invoke(string $text):string
    {
        return $this->translations[$text] ?? $text;
    }
}
