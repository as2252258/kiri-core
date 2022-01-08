<?php


function version($oldVersion, $newVersion): bool
{
    $first = explode('.', $oldVersion);
    $end = explode('.', $newVersion);
    while (count($first) > 0) {
        $shift = (int)array_shift($first);
        $endShift = (int)array_shift($end);
        if ($endShift == $shift) {
            continue;
        }
        if ($endShift < $shift) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    return FALSE;
}

var_dump(version('1.4.4', '1.4.3'));
