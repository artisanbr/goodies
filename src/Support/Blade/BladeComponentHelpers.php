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
    public static function getAttributesBagGroup(string|array $groupNames, ComponentAttributeBag $attributes): ComponentAttributeBag
    {
        $filteredAttributes = [];

        if(!is_iterable($groupNames)){
            $groupNames = [$groupNames];
        }

        $groupNames = collect($groupNames)->map(fn($name) => "{$name}-")->toArray();

        foreach ($attributes->getAttributes() as $key => $value) {

            foreach ($groupNames as $groupName){
                if (Str::startsWith($key, $groupName)) {
                    // Remove the prefix from the attribute key
                    $filteredKey = str($key)->after($groupName);
                    $filteredAttributes[$filteredKey] = $value;
                }
            }

        }

        return new ComponentAttributeBag($filteredAttributes);
    }
}