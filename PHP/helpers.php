<?php
/*
 * Shared display helpers.
 *
 * bike_image() assumes the calling page lives in /PHP, so the returned
 * path is relative to that directory.
 */

// Formats an amount as Nepali Rupees, e.g. 10800 -> "Rs. 10,800.00"
function npr($amount) {
    return 'Rs. ' . number_format((float) $amount, 2);
}

// Formats an hourly rental rate, e.g. 450 -> "Rs. 450 /hour"
function npr_rate($amount) {
    return 'Rs. ' . number_format((float) $amount, 0) . ' /hour';
}

// Lowercase filename-safe form of a bike name, so 'ApacheRTR' -> 'apachertr'
function bike_slug($name) {
    return strtolower(preg_replace('/[^A-Za-z0-9]/', '', (string) $name));
}

// Resolves a bike photo to a servable path, in order of preference:
//   1. the file named in the bike.image column, when that column exists
//   2. the conventional Images/bikes/<bike_id>-<slug>.svg placeholder photo
//   3. Images/bikes/placeholder.svg
function bike_image($filename, $bike_id = null, $bike_name = null) {
    $dir = '../Images/bikes/';
    $onDisk = dirname(__DIR__) . '/Images/bikes/';

    // 1. the value stored in the database
    $stored = basename((string) $filename);
    if ($stored !== '' && @is_file($onDisk . $stored)) {
        return $dir . $stored;
    }

    // 2. conventional per-bike photo, so cards render before any upload exists
    if ($bike_id !== null && $bike_name !== null) {
        $convention = bike_slug($bike_id) . '-' . bike_slug($bike_name) . '.svg';
        if (@is_file($onDisk . $convention)) {
            return $dir . $convention;
        }
    }

    return $dir . 'placeholder.svg';
}

// Returns the lowercase column names that exist on the `bike` table.
function bike_columns($conn) {
    $result = @mysqli_query($conn, 'SHOW COLUMNS FROM `bike`');
    if (!$result) {
        return array();
    }

    $present = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $present[] = strtolower($row['Field']);
    }

    return $present;
}

// Given the column list from bike_columns(), returns the name of a
// registration-number column if the project has one, otherwise null.
// Callers render the field only when this is non-null, so a project without
// the column simply omits the row instead of erroring.
function bike_reg_column($columns) {
    $candidates = array(
        'regno', 'reg_no', 'reg_num', 'reg_number',
        'registration', 'registration_no', 'registration_number',
        'number_plate', 'plate_no', 'plate_number',
    );

    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

// Escapes a value for safe output inside HTML.
function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
