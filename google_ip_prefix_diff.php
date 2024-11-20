<?php
/**
 * Script for retrieving and processing Google IPv4 prefixes.
 *
 * This script fetches IPv4 prefixes from two Google URLs and calculates the difference
 * in IP ranges. It returns the IPv4 prefixes as an array that are present in 'goog.json'
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
 * @author      Andreas Schnederle-Wagner, Futureweb GmbH
 * @version     1.0
 * @date        2024-11-19
 */

$goog_url = "https://www.gstatic.com/ipranges/goog.json";
$cloud_url = "https://www.gstatic.com/ipranges/cloud.json";

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
        /* @todo add error handling */
        return [];
    }

    $json = json_decode($s, true);
    if ($json === null) {
        /* @todo add error handling */
        return [];
    }

    return $json;
}

/**
 * Converts a CIDR block into a start and end IP range as integers.
 *
 * @param string $cidr The CIDR block (e.g., '192.168.0.0/24').
 * @return array An array containing the start and end IP as integers.
 */
function cidr_to_range($cidr)
{
    list($ip, $prefix) = explode('/', $cidr);
    $ip_long = ip2long($ip);
    $prefix = (int)$prefix;
    $mask = -1 << (32 - $prefix);
    $network = $ip_long & $mask;
    $broadcast = $network | (~$mask & 0xFFFFFFFF);
    return array($network, $broadcast);
}

/**
 * Calculates the difference between two IP ranges.
 *
 * @param int $r1_start Start IP of the first range as an integer.
 * @param int $r1_end End IP of the first range as an integer.
 * @param int $r2_start Start IP of the second range as an integer.
 * @param int $r2_end End IP of the second range as an integer.
 * @return array An array of ranges representing the difference.
 */
function range_difference($r1_start, $r1_end, $r2_start, $r2_end)
{
    // Returns an array of ranges representing r1 - r2
    if ($r2_end < $r1_start || $r2_start > $r1_end) {
        // No overlap
        return [[$r1_start, $r1_end]];
    }
    if ($r2_start <= $r1_start && $r2_end >= $r1_end) {
        // r1 is fully contained within r2
        return [];
    }
    $result = [];
    if ($r2_start > $r1_start) {
        $left = [$r1_start, $r2_start - 1];
        if ($left[0] <= $left[1]) {
            $result[] = $left;
        }
    }
    if ($r2_end < $r1_end) {
        $right = [$r2_end + 1, $r1_end];
        if ($right[0] <= $right[1]) {
            $result[] = $right;
        }
    }
    return $result;
}

/**
 * Calculates the difference between two arrays of IP ranges.
 *
 * @param array $ranges_a The first array of IP ranges.
 * @param array $ranges_b The second array of IP ranges.
 * @return array The difference of the ranges.
 */
function ranges_difference($ranges_a, $ranges_b)
{
    $result = [];
    foreach ($ranges_a as $a) {
        $current_ranges = [$a];
        foreach ($ranges_b as $b) {
            $new_current_ranges = [];
            foreach ($current_ranges as $c) {
                $diff = range_difference($c[0], $c[1], $b[0], $b[1]);
                $new_current_ranges = array_merge($new_current_ranges, $diff);
            }
            $current_ranges = $new_current_ranges;
            if (empty($current_ranges)) {
                break; // no more ranges
            }
        }
        $result = array_merge($result, $current_ranges);
    }
    return $result;
}

/**
 * Converts an IP range into a list of CIDR blocks.
 *
 * @param int $start Start IP of the range as an integer.
 * @param int $end End IP of the range as an integer.
 * @return array An array of CIDR blocks.
 */
function ip_range_to_cidrs($start, $end)
{
    $result = [];
    while ($start <= $end) {
        $max_size = 32;
        while ($max_size > 0) {
            $mask = -1 << (32 - ($max_size - 1));
            $mask = $mask & 0xFFFFFFFF;
            $masked_base = $start & $mask;
            if ($masked_base != $start) {
                break;
            }
            $max_size--;
        }
        $x = floor(log($end - $start + 1) / log(2));
        $max_size = max($max_size, 32 - $x);
        $ip = long2ip($start);
        $result[] = "$ip/$max_size";
        $start += pow(2, (32 - $max_size));
    }
    return $result;
}

/**
 * Main function of the script.
 *
 * Fetches the JSON data, calculates the difference of IP ranges,
 * and returns the results as an array.
 *
 * @return array The list of IPv4 prefixes as an array.
 */
function get_google_ip_prefixes_difference()
{
    global $goog_url, $cloud_url;

    $goog_json = read_url($goog_url);
    $cloud_json = read_url($cloud_url);

    if (!empty($goog_json) && !empty($cloud_json)) {
        $goog_ranges = [];
        foreach ($goog_json['prefixes'] as $e) {
            if (isset($e['ipv4Prefix'])) {
                list($start, $end) = cidr_to_range($e['ipv4Prefix']);
                $goog_ranges[] = [$start, $end];
            }
        }

        $cloud_ranges = [];
        foreach ($cloud_json['prefixes'] as $e) {
            if (isset($e['ipv4Prefix'])) {
                list($start, $end) = cidr_to_range($e['ipv4Prefix']);
                $cloud_ranges[] = [$start, $end];
            }
        }

        // Calculate the difference between Google and Cloud IP ranges
        $difference_ranges = ranges_difference($goog_ranges, $cloud_ranges);

        // Convert the resulting ranges back into CIDRs
        $difference_cidrs = [];
        foreach ($difference_ranges as $range) {
            $cidrs = ip_range_to_cidrs($range[0], $range[1]);
            $difference_cidrs = array_merge($difference_cidrs, $cidrs);
        }

        return $difference_cidrs;

    } else {
        return false;
    }
}

// Check if the script is being run directly
if (php_sapi_name() === 'cli') {
    $ip_prefixes = get_google_ip_prefixes_difference();
    
    // Output each CIDR to the console
    foreach ($ip_prefixes as $cidr) {
        echo $cidr . PHP_EOL;
    }
}
?>
