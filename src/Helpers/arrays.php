<?php
if (!function_exists("array_filter_recursive")) {
    function array_filter_recursive(array $array, ?callable $callback = null, int $mode = 0): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = array_filter_recursive($value, $callback, $mode);
            }
        }

        return array_filter($array, $callback, $mode);
    }
}