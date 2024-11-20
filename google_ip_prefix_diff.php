<?php
/**
 * Script for retrieving and processing Google IPv4 and IPv6 prefixes.
 *
 * This script fetches IPv4 and IPv6 prefixes from two Google URLs and calculates the difference
 * in IP ranges. It returns the IPv4 and IPv6 prefixes as arrays that are present in 'goog.json'
 * but not in 'cloud.json'.
 *
 * This is a faster PHP implementation of the Python script provided by Google:
 * https://cloud.google.com/vpc/docs/configure-private-google-access#ip-addr-defaults
 *
 * MIT License
 *
 * Copyright (c) 2024 Andreas Schnederle-Wagner, Futureweb GmbH
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * Prerequisites:
 * - PHP GMP extension enabled
 * OR
 * - PHP BCMath extension enabled.
 *
 * Note: If both GMP and BCMath are enabled, GMP will be preferred for performance.
 *
 * @author      Andreas Schnederle-Wagner, Futureweb GmbH
 * @version     2.0.0
 * @date        2024-11-20
 * @url         https://github.com/futureweb/google-ip-prefix-diff-php
 */

// URLs to fetch IP ranges
$goog_url = "https://www.gstatic.com/ipranges/goog.json";
$cloud_url = "https://www.gstatic.com/ipranges/cloud.json";

// Determine which math extension to use
if (extension_loaded('gmp')) {
    $math_mode = 'gmp';
} elseif (extension_loaded('bcmath')) {
    $math_mode = 'bcmath';
} else {
    die("Error: Neither GMP nor BCMath extensions are enabled. Please enable one to proceed.\n");
}

/**
 * Math Operations Wrapper
 *
 * Defines arithmetic and bitwise operations based on the available extension.
 */
if ($math_mode === 'gmp') {
    /**
     * Adds two numbers.
     *
     * @param mixed $a First number (GMP resource).
     * @param mixed $b Second number (GMP resource).
     * @return mixed Sum of the numbers (GMP resource).
     */
    function add($a, $b) {
        return gmp_add($a, $b);
    }

    /**
     * Subtracts the second number from the first.
     *
     * @param mixed $a First number (GMP resource).
     * @param mixed $b Second number (GMP resource).
     * @return mixed Difference of the numbers (GMP resource).
     */
    function sub($a, $b) {
        return gmp_sub($a, $b);
    }

    /**
     * Raises a number to a power.
     *
     * @param mixed $a Base number (GMP resource).
     * @param int $b Exponent.
     * @return mixed Result of the exponentiation (GMP resource).
     */
    function powm($a, $b) {
        return gmp_pow($a, $b);
    }

    /**
     * Performs a bitwise AND operation.
     *
     * @param mixed $a First number (GMP resource).
     * @param mixed $b Second number (GMP resource).
     * @return mixed Result of bitwise AND (GMP resource).
     */
    function bitwise_and($a, $b) {
        return gmp_and($a, $b);
    }

    /**
     * Shifts a number to the right by a specified number of bits.
     *
     * @param mixed $a Number to shift (GMP resource).
     * @param int $bits Number of bits to shift.
     * @return mixed Shifted number (GMP resource).
     */
    function shift_right($a, $bits) {
        return gmp_div_q($a, gmp_pow(2, $bits));
    }

    /**
     * Converts a hexadecimal string to a GMP number.
     *
     * @param string $hex Hexadecimal string.
     * @return mixed GMP number.
     */
    function hexdec_custom($hex) {
        return gmp_init($hex, 16);
    }

    /**
     * Converts a GMP number to a hexadecimal string.
     *
     * @param mixed $dec GMP number.
     * @return string Hexadecimal representation.
     */
    function dechex_custom($dec) {
        return gmp_strval($dec, 16);
    }

    /**
     * Converts a GMP number to a binary string.
     *
     * @param mixed $dec GMP number.
     * @return string Binary representation.
     */
    function decbin_custom($dec) {
        return gmp_strval($dec, 2);
    }

    /**
     * Converts a binary string to a GMP number.
     *
     * @param string $bin Binary string.
     * @return mixed GMP number.
     */
    function bindec_custom($bin) {
        return gmp_init($bin, 2);
    }

    /**
     * Compares two GMP numbers.
     *
     * @param mixed $a First number (GMP resource).
     * @param mixed $b Second number (GMP resource).
     * @return int -1 if $a < $b, 0 if equal, 1 if $a > $b.
     */
    function compare($a, $b) {
        return gmp_cmp($a, $b);
    }

} elseif ($math_mode === 'bcmath') {
    /**
     * Converts a hexadecimal string to a decimal string using BCMath.
     *
     * @param string $hex Hexadecimal string.
     * @return string Decimal representation.
     */
    function hexdec_custom($hex) {
        $dec = '0';
        $len = strlen($hex);
        for ($i = 0; $i < $len; $i++) {
            $digit = hexdec($hex[$i]);
            $dec = bcmul($dec, '16');
            $dec = bcadd($dec, (string)$digit);
        }
        return $dec;
    }

    /**
     * Converts a decimal string to a hexadecimal string using BCMath.
     *
     * @param string $dec Decimal string.
     * @return string Hexadecimal representation.
     */
    function dechex_custom($dec) {
        $hex = '';
        do {
            $remainder = bcmod($dec, '16');
            $hex = dechex((int)$remainder) . $hex;
            $dec = bcdiv($dec, '16', 0);
        } while (bccomp($dec, '0') > 0);
        return $hex === '' ? '0' : $hex;
    }

    /**
     * Converts a decimal string to a binary string using BCMath.
     *
     * @param string $dec Decimal string.
     * @return string Binary representation.
     */
    function decbin_custom($dec) {
        $bin = '';
        while (bccomp($dec, '0') > 0) {
            $bin = bcmod($dec, '2') . $bin;
            $dec = bcdiv($dec, '2', 0);
        }
        return $bin === '' ? '0' : $bin;
    }

    /**
     * Converts a binary string to a decimal string using BCMath.
     *
     * @param string $bin Binary string.
     * @return string Decimal representation.
     */
    function bindec_custom($bin) {
        $dec = '0';
        $len = strlen($bin);
        for ($i = 0; $i < $len; $i++) {
            $dec = bcmul($dec, '2');
            if ($bin[$i] === '1') {
                $dec = bcadd($dec, '1');
            }
        }
        return $dec;
    }

    /**
     * Performs a bitwise AND operation on two decimal strings using BCMath.
     *
     * @param string $a First decimal string.
     * @param string $b Second decimal string.
     * @return string Result of bitwise AND as a decimal string.
     */
    function bitwise_and($a, $b) {
        $bin_a = decbin_custom($a);
        $bin_b = decbin_custom($b);
        $max_len = max(strlen($bin_a), strlen($bin_b));
        $bin_a = str_pad($bin_a, $max_len, '0', STR_PAD_LEFT);
        $bin_b = str_pad($bin_b, $max_len, '0', STR_PAD_LEFT);
        $result_bin = '';
        for ($i = 0; $i < $max_len; $i++) {
            $result_bin .= ($bin_a[$i] === '1' && $bin_b[$i] === '1') ? '1' : '0';
        }
        return bindec_custom($result_bin);
    }

    /**
     * Shifts a decimal string to the right by a specified number of bits using BCMath.
     *
     * @param string $num Decimal string.
     * @param int $bits Number of bits to shift.
     * @return string Shifted decimal string.
     */
    function shift_right($num, $bits) {
        return bcdiv($num, bcpow('2', (string)$bits), 0);
    }

    /**
     * Adds two decimal strings using BCMath.
     *
     * @param string $a First number.
     * @param string $b Second number.
     * @return string Sum of the numbers.
     */
    function add($a, $b) {
        return bcadd($a, $b);
    }

    /**
     * Subtracts the second decimal string from the first using BCMath.
     *
     * @param string $a First number.
     * @param string $b Second number.
     * @return string Difference of the numbers.
     */
    function sub($a, $b) {
        return bcsub($a, $b);
    }

    /**
     * Raises a decimal string to a power using BCMath.
     *
     * @param string $a Base number.
     * @param int $b Exponent.
     * @return string Result of the exponentiation.
     */
    function powm($a, $b) {
        return bcpow($a, (string)$b);
    }

    /**
     * Compares two decimal strings using BCMath.
     *
     * @param string $a First number.
     * @param string $b Second number.
     * @return int -1 if $a < $b, 0 if equal, 1 if $a > $b.
     */
    function compare($a, $b) {
        return bccomp($a, $b);
    }
}

/**
 * Reads the content of a URL and decodes the JSON response.
 *
 * @param string $url The URL to fetch data from.
 * @return array The decoded JSON as an associative array, or an empty array on failure.
 */
function read_url($url)
{
    $context = stream_context_create([
        "http" => ["method" => "GET", "header" => "Accept-language: en\r\n"]
    ]);

    $s = @file_get_contents($url, false, $context);
    if ($s === false) {
        // Log the error or handle it as needed
        return [];
    }

    $json = json_decode($s, true);
    if ($json === null) {
        // Log the error or handle it as needed
        return [];
    }

    return $json;
}

/**
 * Converts an IP address to a number using the available math extension.
 *
 * @param string $ip The IP address.
 * @return mixed The number representation of the IP address (GMP resource or decimal string).
 */
function ip_to_number($ip)
{
    global $math_mode;
    $packed = inet_pton($ip);
    $hex = bin2hex($packed);
    return hexdec_custom($hex);
}

/**
 * Converts a number back to an IP address using the available math extension.
 *
 * @param mixed $number The number representation of the IP address (GMP resource or decimal string).
 * @param bool $is_ipv6 True if IPv6, false if IPv4.
 * @return string The IP address.
 */
function number_to_ip($number, $is_ipv6)
{
    global $math_mode;
    $hex = dechex_custom($number);

    // Pad the hex string with leading zeros
    if ($is_ipv6) {
        $hex = str_pad($hex, 32, '0', STR_PAD_LEFT);
    } else {
        $hex = str_pad($hex, 8, '0', STR_PAD_LEFT);
    }

    $packed = hex2bin($hex);
    return inet_ntop($packed);
}

/**
 * Converts a CIDR block into a start and end IP range.
 *
 * @param string $cidr The CIDR block (e.g., '192.168.0.0/24' or '2001:db8::/32').
 * @return array An array containing the start and end IP as numbers, a boolean for IPv6, and the prefix length.
 */
function cidr_to_range($cidr)
{
    global $math_mode;
    list($ip, $prefix) = explode('/', $cidr);
    $ip = trim($ip);
    $prefix = (int) $prefix;
    if (strpos($ip, ':') === false) {
        // IPv4
        $ip_num = ip_to_number($ip);
        $max_bits = 32;
        $is_ipv6 = false;
    } else {
        // IPv6
        $ip_num = ip_to_number($ip);
        $max_bits = 128;
        $is_ipv6 = true;
    }

    $host_bits = $max_bits - $prefix;
    $network_mask = sub(powm('2', $max_bits), powm('2', $host_bits));
    $network = bitwise_and($ip_num, $network_mask);
    $broadcast = add($network, sub(powm('2', $host_bits), '1'));

    return array($network, $broadcast, $is_ipv6, $prefix);
}

/**
 * Merges overlapping or adjacent ranges.
 *
 * @param array $ranges Array of ranges with 'start' and 'end'.
 * @return array Merged array of ranges.
 */
function merge_ranges($ranges) {
    if (empty($ranges)) {
        return [];
    }

    // Sort ranges by start
    usort($ranges, function($a, $b) {
        return compare($a['start'], $b['start']);
    });

    $merged = [];
    $current = $ranges[0];

    for ($i = 1; $i < count($ranges); $i++) {
        $next = $ranges[$i];
        // Check if current and next overlap or are adjacent
        $one = add($current['end'], '1');
        if (compare($next['start'], $one) <= 0) {
            // Merge
            $current['end'] = max_ip($current['end'], $next['end']);
        } else {
            $merged[] = $current;
            $current = $next;
        }
    }
    $merged[] = $current;

    return $merged;
}

/**
 * Returns the maximum of two IP numbers.
 *
 * @param mixed $a First IP number.
 * @param mixed $b Second IP number.
 * @return mixed Maximum IP number.
 */
function max_ip($a, $b) {
    return (compare($a, $b) >= 0) ? $a : $b;
}

/**
 * Subtracts a list of ranges from a range.
 *
 * @param mixed $start Start IP of the range (GMP resource or decimal string).
 * @param mixed $end End IP of the range (GMP resource or decimal string).
 * @param array $subtract_ranges An array of ranges to subtract.
 * @return array An array of remaining ranges.
 */
function subtract_ranges($start, $end, $subtract_ranges)
{
    $remaining = [['start' => $start, 'end' => $end]];

    foreach ($subtract_ranges as $sub) {
        $new_remaining = [];
        foreach ($remaining as $r) {
            $diff = range_difference($r['start'], $r['end'], $sub['start'], $sub['end']);
            foreach ($diff as $d) {
                $new_remaining[] = ['start' => $d[0], 'end' => $d[1]];
            }
        }
        $remaining = $new_remaining;
        if (empty($remaining)) {
            break;
        }
    }

    return $remaining;
}

/**
 * Calculates the difference between two IP ranges.
 *
 * @param mixed $r1_start Start IP of the first range (GMP resource or decimal string).
 * @param mixed $r1_end End IP of the first range (GMP resource or decimal string).
 * @param mixed $r2_start Start IP of the second range (GMP resource or decimal string).
 * @param mixed $r2_end End IP of the second range (GMP resource or decimal string).
 * @return array An array of ranges representing the difference.
 */
function range_difference($r1_start, $r1_end, $r2_start, $r2_end)
{
    // Returns an array of ranges representing r1 - r2
    if (compare($r2_end, $r1_start) < 0 || compare($r2_start, $r1_end) > 0) {
        // No overlap
        return [[$r1_start, $r1_end]];
    }

    $result = [];
    if (compare($r2_start, $r1_start) > 0) {
        $left = [add($r1_start, '0'), sub($r2_start, '1')]; // Ensure correct start
        if (compare($left[0], $left[1]) <= 0) {
            $result[] = $left;
        }
    }
    if (compare($r2_end, $r1_end) < 0) {
        $right = [add($r2_end, '1'), $r1_end];
        if (compare($right[0], $right[1]) <= 0) {
            $result[] = $right;
        }
    }
    return $result;
}

/**
 * Converts an IP range back to CIDRs using an optimized algorithm.
 *
 * @param mixed $start Start IP of the range (GMP resource or decimal string).
 * @param mixed $end End IP of the range (GMP resource or decimal string).
 * @param bool $is_ipv6 True if IPv6, false if IPv4.
 * @return array An array of CIDR blocks.
 */
function range_to_cidrs($start, $end, $is_ipv6)
{
    global $math_mode;
    $max_bits = $is_ipv6 ? 128 : 32;
    $cidrs = [];

    while (compare($start, $end) <= 0) {
        // Find the number of zero bits on the right
        $zeros = 0;
        $current = $start;
        while (compare(bitwise_and($current, '1'), '0') == 0 && $zeros < $max_bits) {
            $zeros++;
            $current = shift_right($current, 1);
        }

        // Calculate the maximum size of the block
        $max_size = $max_bits - $zeros;

        // Calculate the largest possible prefix length that doesn't exceed the range
        $block_size = powm('2', $max_bits - $max_size);
        $remaining = sub($end, $start);
        $remaining_plus_one = add($remaining, '1');

        while (compare($block_size, $remaining_plus_one) > 0 && $max_size < $max_bits) {
            $max_size++;
            $block_size = powm('2', $max_bits - $max_size);
        }

        // Ensure prefix length is within bounds
        if ($max_size > $max_bits) {
            $max_size = $max_bits;
        }

        // Add the CIDR block
        $cidr = number_to_ip($start, $is_ipv6) . "/$max_size";
        $cidrs[] = $cidr;

        // Move to the next block
        $start = add($start, $block_size);
    }

    return $cidrs;
}

/**
 * Merges overlapping or adjacent ranges.
 *
 * @param array $ranges Array of ranges with 'start' and 'end'.
 * @return array Merged array of ranges.
 */
function merge_and_sort_ranges($ranges) {
    return merge_ranges($ranges);
}

/**
 * Converts a CIDR range into start and end numbers.
 *
 * @param string $cidr The CIDR notation.
 * @return array The start and end of the range as numbers, is_ipv6 flag, and prefix length.
 */
function cidr_to_range_with_prefix($cidr) {
    return cidr_to_range($cidr);
}

/**
 * Main function of the script.
 *
 * Fetches the JSON data, calculates the difference of IP prefixes,
 * and returns the results as an array for both IPv4 and IPv6.
 *
 * @return array|false The list of IP prefixes as an array or false on failure.
 */
function get_google_ip_prefixes_difference()
{
    global $goog_url, $cloud_url;

    $goog_json = read_url($goog_url);
    $cloud_json = read_url($cloud_url);

    if (!empty($goog_json) && !empty($cloud_json)) {
        // Prepare cloud ranges
        $cloud_ipv4 = [];
        $cloud_ipv6 = [];
        foreach ($cloud_json['prefixes'] as $e) {
            if (isset($e['ipv4Prefix'])) {
                list($start, $end, $is_ipv6, $prefix) = cidr_to_range_with_prefix($e['ipv4Prefix']);
                $cloud_ipv4[] = ['start' => $start, 'end' => $end];
            }
            if (isset($e['ipv6Prefix'])) {
                list($start, $end, $is_ipv6, $prefix) = cidr_to_range_with_prefix($e['ipv6Prefix']);
                $cloud_ipv6[] = ['start' => $start, 'end' => $end];
            }
        }

        // Merge and sort cloud ranges to optimize subtraction
        $cloud_ipv4 = merge_and_sort_ranges($cloud_ipv4);
        $cloud_ipv6 = merge_and_sort_ranges($cloud_ipv6);

        // Initialize difference lists
        $difference_ipv4_cidrs = [];
        $difference_ipv6_cidrs = [];

        // Process IPv4 prefixes
        foreach ($goog_json['prefixes'] as $e) {
            if (isset($e['ipv4Prefix'])) {
                $cidr = $e['ipv4Prefix'];
                list($start, $end, $is_ipv6, $original_prefix) = cidr_to_range_with_prefix($cidr);

                // Subtract cloud IPv4 ranges
                $remaining_ranges = subtract_ranges($start, $end, $cloud_ipv4);

                // Convert remaining ranges back to CIDRs
                foreach ($remaining_ranges as $r) {
                    $cidrs = range_to_cidrs($r['start'], $r['end'], $is_ipv6);
                    $difference_ipv4_cidrs = array_merge($difference_ipv4_cidrs, $cidrs);
                }
            }
        }

        // Process IPv6 prefixes
        foreach ($goog_json['prefixes'] as $e) {
            if (isset($e['ipv6Prefix'])) {
                $cidr = $e['ipv6Prefix'];
                list($start, $end, $is_ipv6, $original_prefix) = cidr_to_range_with_prefix($cidr);

                // Subtract cloud IPv6 ranges
                $remaining_ranges = subtract_ranges($start, $end, $cloud_ipv6);

                // Convert remaining ranges back to CIDRs
                foreach ($remaining_ranges as $r) {
                    $cidrs = range_to_cidrs($r['start'], $r['end'], $is_ipv6);
                    $difference_ipv6_cidrs = array_merge($difference_ipv6_cidrs, $cidrs);
                }
            }
        }

        // Sort the prefixes numerically for IPv4
        usort($difference_ipv4_cidrs, function($a, $b) {
            list($a_ip, ) = explode('/', $a);
            list($b_ip, ) = explode('/', $b);
            $a_num = ip_to_number($a_ip);
            $b_num = ip_to_number($b_ip);
            return compare($a_num, $b_num);
        });

        // Sort the prefixes numerically for IPv6
        usort($difference_ipv6_cidrs, function($a, $b) {
            list($a_ip, ) = explode('/', $a);
            list($b_ip, ) = explode('/', $b);
            $a_num = ip_to_number($a_ip);
            $b_num = ip_to_number($b_ip);
            return compare($a_num, $b_num);
        });

        // Return both IPv4 and IPv6 prefixes
        return array(
            'ipv4' => $difference_ipv4_cidrs,
            'ipv6' => $difference_ipv6_cidrs
        );

    } else {
        return false;
    }
}

// Check if the script is being run directly
if (php_sapi_name() === 'cli') {
    $ip_prefixes = get_google_ip_prefixes_difference();

    if ($ip_prefixes !== false) {
        // Output IPv4 prefixes
        foreach ($ip_prefixes['ipv4'] as $cidr) {
            echo $cidr . PHP_EOL;
        }
        // Output IPv6 prefixes
        foreach ($ip_prefixes['ipv6'] as $cidr) {
            echo $cidr . PHP_EOL;
        }
    } else {
        echo "Failed to retrieve IP prefixes." . PHP_EOL;
    }
}
?>
