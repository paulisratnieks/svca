<?php

if (!function_exists('stringify')) {
    function stringify(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
