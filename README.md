# Google IP Prefix Difference PHP

![License](https://img.shields.io/github/license/futureweb/google-ip-prefix-diff-php)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)

## tl;dr
This script outputs **all IP ranges used by Google's own services**, excluding IP ranges assigned to Google Cloud customers. It is useful for identifying the IPs utilized exclusively by Google's infrastructure.

## Overview

Google IP Prefix Difference PHP is a robust and efficient PHP script designed to retrieve and process Google's IP prefixes. It calculates the difference between two sets of IP ranges provided by Google, specifically those present in `goog.json` but not in `cloud.json`. 

The script determines the IP ranges used by the default domains for Google APIs and services, such as `*.googleapis.com` and `*.gcr.io`. These IP ranges are calculated by subtracting the ranges in `cloud.json` (external IP ranges for Google Cloud customer resources) from those in `goog.json` (the complete list of Google IP ranges available on the internet).

For more information, see the [Google Documentation](https://cloud.google.com/vpc/docs/configure-private-google-access#ip-addr-defaults).

**Version 2** of the script introduces comprehensive support for both **IPv4 and IPv6** addresses, expanding its applicability and ensuring compatibility with modern network configurations.

## Features

- **Fetches Google Cloud IP ranges** from official URLs:
  - [`goog.json`](https://www.gstatic.com/ipranges/goog.json): Complete list of Google IP ranges.
  - [`cloud.json`](https://www.gstatic.com/ipranges/cloud.json): External IP ranges for Google Cloud resources.
- **Compares the IP ranges** in `goog.json` and `cloud.json`.
- **Outputs the resulting CIDR ranges** used by Google APIs and services.

## Version 2 Highlights
- **IPv4 and IPv6 Support:** Handles both IPv4 and IPv6 prefixes, allowing for comprehensive IP range processing.
- **Efficient Range Processing:** Merges and sorts cloud IP ranges upfront to minimize redundancy and optimize subtraction operations.
- **Optimized Bitwise Operations:** Enhancements for both GMP and BCMath extensions to ensure fast and efficient bitwise computations.
- **Flexible Math Extensions:** Dynamically utilizes either the GMP or BCMath PHP extensions based on availability, preferring GMP for superior performance.
- **Easy Integration:** Designed to be included in other PHP projects seamlessly, preventing direct access for enhanced security.
- **Clear Documentation:** Comprehensive comments and documentation to facilitate easy understanding and maintenance.

## Prerequisites

- **PHP 7.4 or higher**
- **PHP Extensions:**
  - **GMP** (preferred for optimal performance) **OR**
  - **BCMath**

Ensure that at least one of these extensions is enabled in your PHP environment.

---

## How It Works

1. The script fetches the JSON files from:
   - [https://www.gstatic.com/ipranges/goog.json](https://www.gstatic.com/ipranges/goog.json)
   - [https://www.gstatic.com/ipranges/cloud.json](https://www.gstatic.com/ipranges/cloud.json)
2. It parses the JSON data and extracts the IPv4 prefixes.
3. It calculates the difference between the IP ranges in `goog.json` and `cloud.json`, determining the IP ranges used by Google APIs and services.
4. It outputs the resulting prefixes as an array of CIDRs.

### Why This Is Important

- These ranges are allocated dynamically and **change often**, so it's not possible to define static IP ranges for individual services or APIs.
- Google recommends **maintaining an up-to-date list** by automating this script to run daily or using alternatives like the `private.googleapis.com` VIP or Private Service Connect.

---

## Installation via Composer

1. Clone this repository:
   ```bash
   composer require futureweb/google-ip-prefix-diff-php
2. Once installed, the script will be autoloaded by Composer, and you can use it in your project:
   ```bash
   <?php
   require 'vendor/autoload.php';

   // Use the functions defined in the script
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

## Manual Installation

1. Clone this repository:
   ```bash
   git clone https://github.com/your-username/google-ip-prefix-diff-php.git
2. Include the script in your Script:
   ```bash
   $ip_prefixes = include 'google_ip_prefix_diff.php';

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

## Important Note: Avoid Direct Inclusion in Executed Scripts

This script downloads large JSON files and processes the differences in IP ranges, which can be **time-consuming and resource-intensive**. For this reason:

- **Do not include the script directly** in frequently executed scripts or APIs.
- Implement a **local caching mechanism** to store the resulting CIDR ranges for repeated use.

### Suggested Basic Caching Logic

1. Run the script periodically (e.g., hourly) to generate the CIDR ranges:
   ```bash
   php google_ip_prefix_diff.php > google_ip_ranges.txt
2. Load the cached results in your executed scripts:
   ```bash
   $ip_ranges = file('google_ip_ranges.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

   foreach ($ip_ranges as $cidr) {
       echo $cidr . PHP_EOL;
   }
3. Automate the caching process using a cron job or similar scheduling tool:
   ```bash
   # Run the script daily at midnight to refresh the cache
   0 0 * * * /usr/bin/php /path/to/google_ip_prefix_diff.php > /path/to/cached_ip_ranges.txt

## Reference
This is a PHP implementation of the Python script provided by Google:
[Private Google Access IP Address Defaults](https://cloud.google.com/vpc/docs/configure-private-google-access#ip-addr-defaults)

## License
This project is licensed under the MIT License. See the LICENSE file for details.
