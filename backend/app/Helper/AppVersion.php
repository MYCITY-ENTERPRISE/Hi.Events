<?php

namespace HiEvents\Helper;

class AppVersion
{
    /**
     * The version of this instance, from the VERSION file.
     */
    public static function get(): string
    {
        return trim(@file_get_contents(base_path('VERSION')) ?: 'unknown');
    }
}
