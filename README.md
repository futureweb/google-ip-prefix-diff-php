# Google IP Prefix Difference (PHP)

This repository contains a PHP implementation of the Google-provided Python script for comparing Google Cloud IP ranges. It calculates the difference in IP ranges based on the `goog.json` and `cloud.json` files provided by Google.

The script determines the IP ranges used by the default domains for Google APIs and services, such as `*.googleapis.com` and `*.gcr.io`. These IP ranges are calculated by subtracting the ranges in `cloud.json` (external IP ranges for Google Cloud customer resources) from those in `goog.json` (the complete list of Google IP ranges available on the internet).

For more information, see the [Google Documentation](https://cloud.google.com/vpc/docs/configure-private-google-access#ip-addr-defaults).

---

## Features

- **Fetches Google Cloud IP ranges** from official URLs:
  - [`goog.json`](https://www.gstatic.com/ipranges/goog.json): Complete list of Google IP ranges.
  - [`cloud.json`](https://www.gstatic.com/ipranges/cloud.json): External IP ranges for Google Cloud resources.
- **Compares the IP ranges** in `goog.json` and `cloud.json`.
- **Outputs the resulting CIDR ranges** used by Google APIs and services.
- Fully implemented in PHP with **no external libraries required**.

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

   foreach ($ip_prefixes as $cidr) {
       echo $cidr . PHP_EOL;
   }

## Manual Installation

1. Clone this repository:
   ```bash
   git clone https://github.com/your-username/google-ip-prefix-diff-php.git
2. Include the script in your Script:
   ```bash
   $ip_prefixes = include 'google_ip_prefix_diff.php';

   foreach ($ip_prefixes as $cidr) {
       echo $cidr . PHP_EOL;
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
