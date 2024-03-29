<?php

namespace ArtisanBR\Goodies\Enums\Traits;

trait EnumBase
{
    use EnumToArray;

    public function is($type): bool
    {
        return $this->value === $type;
    }

}
