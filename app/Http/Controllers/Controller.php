<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Read a date (Y-m-d) or month (Y-m) from the query string. Invalid or mangled values
     * (e.g. from a shared link) fall back to $default instead of crashing the page.
     */
    protected function dateInput(Request $request, string $key, ?Carbon $default = null, string $format = 'Y-m-d'): ?Carbon
    {
        $value = (string) $request->input($key, '');
        if ($value === '') {
            return $default;
        }
        try {
            $date = Carbon::createFromFormat('!'.$format, $value);
        } catch (\Throwable) {
            return $default;
        }
        $errors = Carbon::getLastErrors();

        return ($date === false || ($errors && ($errors['warning_count'] || $errors['error_count']))) ? $default : $date;
    }
}
