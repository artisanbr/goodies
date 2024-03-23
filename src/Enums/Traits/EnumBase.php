<?php

namespace Adminx\Common\Enums\Traits;

trait EnumBase
{
    use EnumToArray;

    public function is($type): bool
    {
        return $this->value === $type;
    }

}
