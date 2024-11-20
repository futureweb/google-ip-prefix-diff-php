# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2024-11-20

### Added
- **IPv6 Support:** Extended the script to handle both IPv4 and IPv6 prefixes, enabling comprehensive IP range processing.
- **Separate Processing Pipelines:** Implemented distinct processing logic for IPv4 and IPv6 ranges to optimize performance and maintain code clarity.
- **Range Merging and Sorting:** Added functionality to merge and sort cloud IP ranges upfront, reducing redundancy and enhancing subtraction efficiency.
- **Optimized Bitwise Operations:** Improved bitwise operations for both GMP and BCMath extensions to minimize computational overhead and speed up processing.
- **Enhanced Functionality:**
  - Updated IP conversion functions to support IPv6 addresses using `inet_pton` and `inet_ntop`.
  - Refactored CIDR to range and range to CIDR conversion functions to accommodate IPv6 addressing.
- **Composer Dependencies:** Updated `composer.json` to include dependencies for PHP extensions **GMP** and **BCMath**, along with scripts to enforce their presence.
- **Comprehensive Documentation:** Enhanced script comments and documentation to reflect IPv6 support and the associated changes in functionality.

### Changed
- **CIDR Conversion Functions:** Refactored CIDR to range and range to CIDR conversion functions to accommodate IPv6 addressing.
- **Autoload Configuration:** Ensured the main script `google_ip_prefix_diff.php` is automatically included when the package is installed via Composer.
- **Versioning:** Updated project version to `2.0.0` to signify major enhancements and feature additions.

### Fixed
- **Range Difference Logic:** Corrected the range difference calculations to ensure accurate subtraction of cloud IP ranges from Google's IP ranges for both IPv4 and IPv6.

### Performance Optimizations
- **Execution Speed:** The script now executes in under **500ms** on our test server, a significant improvement from the original Python script's **1.5 seconds** for the same task. This enhancement ensures faster IP range calculations, making the script more efficient and responsive.

## [1.0.1] - 2024-11-20
### **New Feature: Direct CLI Output**

This release introduces the ability for the script to output the calculated IP prefixes directly to the CLI when executed from the command line.

#### **What's New?**
- When the script is run via CLI (`php google_ip_prefix_diff.php`), it now outputs the resulting IP prefixes (CIDR blocks) line by line.
- This makes it easier to redirect the output to a file for caching or further processing:
  ```bash
  php google_ip_prefix_diff.php > cached_ip_ranges.txt
  ```

#### **Benefits:**
- **Automation-Friendly**: Enables easy integration with cron jobs or other automation tools.
- **Improved Usability**: Users no longer need to manually handle the returned array when running the script via CLI.

## [1.0.0] - 2024-11-19

### Added
- **IPv4 Support:** Introduced initial release supporting IPv4 prefixes only.
- **IP Range Difference Calculation:** Implemented functionality to fetch and process IP ranges from `goog.json` and `cloud.json`, calculating the difference between them.
- **Basic Documentation:** Provided initial comments and documentation detailing script functionality and usage.
- **Composer Autoloading:** Configured `composer.json` to autoload the main script `google_ip_prefix_diff.php`.
