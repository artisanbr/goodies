<?php

namespace ArtisanBR\Goodies\Enums\Traits;

trait EnumWithTitles
{
    
    public function title(): string
    {
        return self::getTitleTo($this);
    }

    public static function getTitleTo(self $type): string
    {
        return __(str(str($type->name)->ucsplit()->implode(' '))->squish()->toString());
    }

    public static function titles(): array
    {
        return array_combine(array_column(self::cases(), 'value'), array_map(
            fn(self $item) => $item->title(),
            self::cases()
        ));
    }

}
