# Google IP Prefix Difference (PHP)

This repository contains a PHP implementation of the Google-provided Python script for comparing Google Cloud IP ranges. It calculates the difference in IP ranges based on the `goog.json` and `cloud.json` files provided by Google.

## Features

- Fetches Google Cloud IP ranges from official URLs.
- Compares the IP ranges in `goog.json` and `cloud.json`.
- Outputs the difference in CIDR format.
- Fully implemented in PHP, no external libraries required.

## How It Works

1. The script fetches the JSON files from:
   - [https://www.gstatic.com/ipranges/goog.json](https://www.gstatic.com/ipranges/goog.json)
   - [https://www.gstatic.com/ipranges/cloud.json](https://www.gstatic.com/ipranges/cloud.json)
2. Parses the JSON data and extracts the IPv4 prefixes.
3. Calculates the difference between `goog.json` and `cloud.json` IP ranges.
4. Outputs the resulting prefixes as an array of CIDRs.

## Usage

1. Clone this repository:
   ```bash
   git clone https://github.com/your-username/google-ip-prefix-diff-php.git
