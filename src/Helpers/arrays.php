<?php
if (!function_exists("array_filter_recursive")) {
    function array_filter_recursive(array &$array, ?callable $callback, int $mode = 0): void
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                array_filter_recursive($value);
            }
        }
        $array = array_filter($array, $callback, $mode);
    }
}