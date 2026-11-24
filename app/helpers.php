<?php

use App\Support\SiteContent;

if (! function_exists('site')) {
    /** Editable website text (Admin → Website content), placeholders filled in. */
    function site(string $key): string
    {
        return SiteContent::text($key);
    }
}

if (! function_exists('site_image')) {
    /** Editable website image URL (uploaded, or the built-in default). */
    function site_image(string $key): string
    {
        return SiteContent::image($key);
    }
}
