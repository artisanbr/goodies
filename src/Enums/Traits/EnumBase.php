<?php

namespace ArtisanBR\Goodies\Enums\Traits;

use BackedEnum;


trait EnumBase
{
    

    use EnumToArray;

    public function is($type): bool
    {
        /**
         * @var BackedEnum $this
         */
        
        return $this->value === $type;
    }

    public function isAny(...$types): bool
    {
        /**
         * @var BackedEnum $this
         */

        foreach($types as $type){
            if($this->value === $type) {return true;}
        }
        
        return false;
    }

}
