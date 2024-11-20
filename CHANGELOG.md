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
- **Enhanced IP Conversion Functions:** Updated IP conversion functions to support IPv6 addresses using `inet_pton` and `inet_ntop`.
- **Composer Dependencies:** Updated `composer.json` to include dependencies for PHP extensions **GMP** and **BCMath**, along with scripts to enforce their presence.
- **Comprehensive Documentation:** Enhanced script comments and documentation to reflect IPv6 support and the associated changes in functionality.

### Changed
- **CIDR Conversion Functions:** Refactored CIDR to range and range to CIDR conversion functions to accommodate IPv6 addressing.
- **Autoload Configuration:** Ensured the main script `google_ip_prefix_diff.php` is automatically included when the package is installed via Composer.
- **Versioning:** Updated project version to `2.0.0` to signify major enhancements and feature additions.

### Fixed
- **Range Difference Logic:** Corrected the range difference calculations to ensure accurate subtraction of cloud IP ranges from Google's IP ranges for both IPv4 and IPv6.

## [1.0.0] - 2024-11-19

### Added
- **IPv4 Support:** Introduced initial release supporting IPv4 prefixes only.
- **IP Range Difference Calculation:** Implemented functionality to fetch and process IP ranges from `goog.json` and `cloud.json`, calculating the difference between them.
- **Basic Documentation:** Provided initial comments and documentation detailing script functionality and usage.
- **Composer Autoloading:** Configured `composer.json` to autoload the main script `google_ip_prefix_diff.php`.
