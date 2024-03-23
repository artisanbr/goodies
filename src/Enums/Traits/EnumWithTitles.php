<?php

namespace Adminx\Common\Enums\Traits;

trait EnumWithTitles
{
    
    public function title(): string
    {
        return self::getTitleTo($this);
    }

    public static function getTitleTo($type): string
    {
        return match ($type) {
            default => 'Nenhum',
        };
    }

    public static function titles(): array
    {
        return array_combine(array_column(self::cases(), 'value'), array_map(
            fn(self $item) => $item->title(),
            self::cases()
        ));
    }

}
