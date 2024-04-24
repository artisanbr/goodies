<?php
/*
 * Copyright (c) 2024.  Todos os Direitos Reservados - Artisan Digital
 * Desenvolvido por Renalcio Carlos Jr. aos cuidados de artisan.dev.br
 */

namespace ArtisanBR\Goodies\Support\Blade;

class BladeComponentPrefix
{
    public function __construct(private ?string $prefix = null)
    {

    }

    public function __invoke(string $component): string
    {
        if (blank($this->prefix) || (bool) $this->prefix === false) {
            return $component;
        }

        return str($component)->start($this->prefix)->value();
    }
}
