<?php

use ArtisanBR\Goodies\Support\Blade\BladeComponentHelpers;
use Illuminate\View\ComponentAttributeBag;

if (!function_exists("attributesBagGroup")) {
    function attributesBagGroup(string $groupName, ComponentAttributeBag $attributes): ComponentAttributeBag
    {

        return BladeComponentHelpers::getAttributesBagGroup($groupName, $attributes);
    }
}