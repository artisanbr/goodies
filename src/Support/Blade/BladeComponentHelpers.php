<?php
/*
 * Copyright (c) 2024.  Todos os Direitos Reservados - Artisan Digital
 * Desenvolvido por Renalcio Carlos Jr. aos cuidados de artisan.dev.br
 */

namespace ArtisanBR\Goodies\Support\Blade;

use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

class BladeComponentHelpers
{
    public static function getAttributesBagGroup(string $groupName, ComponentAttributeBag $attributes): ComponentAttributeBag
    {
        $filteredAttributes = [];

        foreach ($attributes->getAttributes() as $key => $value) {
            if (Str::startsWith($key, "{$groupName}-")) {
                // Remove the prefix from the attribute key
                $filteredKey = Str::after($key, "{$groupName}-");
                $filteredAttributes[$filteredKey] = $value;
            }
        }

        return new ComponentAttributeBag($filteredAttributes);
    }
}
