<?php

function rsToArray($rs) {
    $arr = $rs->fetchAll(PDO::FETCH_ASSOC);
    $res = array();
    for ($i = 0; $i < count($arr); $i++)
        $res[] = array_change_key_case($arr[$i], CASE_LOWER);
    return $res;
}

function objectToArray($obj, $ignoreClasses = array()) {
    return _objectToArray($obj, $ignoreClasses);
}

function _objectToArray($obj, $ignoreClasses = array()) {
    $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
    if (is_array($_arr)) {
        $arr = array();
        foreach ($_arr as $key => $val) {
            if (is_object($val) && in_array(get_class($val), $ignoreClasses))
                continue;
            else {
                $val = (is_array($val) || is_object($val)) ? _objectToArray($val, $ignoreClasses) : $val;
                $arr[$key] = $val;
            }
        }
    } else
        $arr = null;
    return $arr;
}

function eveNum($n, $precision = 2) {
    return number_format((float) $n, $precision, '.', ',');
}

function eveNumInt($n) {
    return number_format((int) $n, 0, '.', ',');
}

// stolen from http://www.go4expert.com/forums/showthread.php?t=4948
// A function to return the Roman Numeral, given an integer
function eveRoman($num) {
    // Make sure that we only use the integer portion of the value
    $n = intval($num);
    $result = '';

    // Declare a lookup array that we will use to traverse the number:
    $lookup = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
        'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
        'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);

    foreach ($lookup as $roman => $value) {
        // Determine the number of matches
        $matches = intval($n / $value);

        // Store that many characters
        $result .= str_repeat($roman, $matches);

        // Substract that from the number
        $n = $n % $value;
    }

    if (empty($result))
        $result = '0';

    // The Roman numeral should be built, return it
    return $result;
}

function formatTime($time) {
    $d = 0;
    $h = 0;
    $m = 0;
    $s = abs($time);

    if ($s > 3600) {
        $h = floor($s / 3600);
        $s -= $h * 3600;
    }
    if ($h > 24) {
        $d = floor($h / 24);
        $h -= $d * 24;
    }
    $m = floor($s / 60);
    $s -= $m * 60;

    $res = '';

    if ($d > 0)
        $res = $res . $d . 'd ';
    if ($h > 0)
        $res = $res . $h . 'h ';
    if ($m > 0)
        $res = $res . $m . 'm ';
    //$res = $res . $s . 's';

    return trim($res);
}

function yesNo($bool) {
    return $bool ? 'Yes' : 'No';
}

function getKey() {
    if (!file_exists($GLOBALS['config']['site']['keypass'])) {
        die('<h3>API key encryption file not found at "' . $GLOBALS['config']['site']['keypass'] . '"</h3>');
    }

    $key = trim(file_get_contents($GLOBALS['config']['site']['keypass']));

    if (empty($key)) {
        die('<h3>API key encryption file at "' . $GLOBALS['config']['site']['keypass'] . '" is empty!</h3>');
    }

    return $key;
}

function encryptKey($apiKey) {
    if (!empty($GLOBALS['config']['site']['keypass'])) {
        $result = enc_encrypt($apiKey, getKey());
    } else {
        $result = $apiKey;
    }
    return $result;
}

function decryptKey($apiKey) {
    if (!empty($GLOBALS['config']['site']['keypass']) && (substr($apiKey, -1) == '=')) {
        $result = enc_decrypt($apiKey, getKey());
    } else {
        $result = $apiKey;
    }
    return $result;
}

function enc_encrypt($string, $key) {
    $result = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $char = substr($string, $i, 1);
        $keychar = substr($key, ($i % strlen($key)) - 1, 1);
        $char = chr(ord($char) + ord($keychar));
        $result .= $char;
    }

    return base64_encode($result);
}

function enc_decrypt($string, $key) {
    $result = '';
    $string = base64_decode($string);

    for ($i = 0; $i < strlen($string); $i++) {
        $char = substr($string, $i, 1);
        $keychar = substr($key, ($i % strlen($key)) - 1, 1);
        $char = chr(ord($char) - ord($keychar));
        $result .= $char;
    }

    return $result;
}

if (!function_exists('json_encode')) {
    require_once('json.php');

    function json_encode($value) {
        $json = new Services_JSON();
        return $json->encode($value);
    }

    function json_decode($value) {
        $json = new Services_JSON();
        return $json->decode($value);
    }

}
?>