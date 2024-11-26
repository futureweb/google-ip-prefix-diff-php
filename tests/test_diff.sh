#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

# Define URLs
GOOG_JSON_URL="https://www.gstatic.com/ipranges/goog.json"
CLOUD_JSON_URL="https://www.gstatic.com/ipranges/cloud.json"
PYTHON_SCRIPT_URL="https://raw.githubusercontent.com/GoogleCloudPlatform/networking-tools-python/main/tools/cidr/cidr.py"

# Determine the repository root
REPO_ROOT="$(git rev-parse --show-toplevel 2>/dev/null)"
if [ -z "$REPO_ROOT" ]; then
    echo "Error: This script must be run within a Git repository."
    exit 1
fi

# Path to the PHP script
PHP_SCRIPT_PATH="$REPO_ROOT/google_ip_prefix_diff.php"

if [ ! -f "$PHP_SCRIPT_PATH" ]; then
    echo "Error: PHP script google_ip_prefix_diff.php not found at $PHP_SCRIPT_PATH."
    exit 1
fi

# Create a temporary directory for testing
TMP_DIR=$(mktemp -d)
echo "Using temporary directory: $TMP_DIR"

# Cleanup on exit
cleanup() {
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

cd "$TMP_DIR"

# Download JSON data
echo "Downloading goog.json..."
wget -q "$GOOG_JSON_URL" -O goog.json

echo "Downloading cloud.json..."
wget -q "$CLOUD_JSON_URL" -O cloud.json

# Download the Python script
echo "Downloading cidr.py..."
wget -q "$PYTHON_SCRIPT_URL" -O cidr.py
chmod +x cidr.py

# Check if Python is installed
if ! command -v python3 &> /dev/null
then
    echo "Error: Python3 could not be found. Please install Python3."
    exit 1
fi

# Run the Python script and extract only CIDR lines
echo "Running Python script..."
python3 cidr.py goog.json cloud.json | grep -E '^([0-9]{1,3}\.){3}[0-9]{1,3}/[0-9]{1,2}$|^([0-9a-fA-F]{1,4}:){1,7}[0-9a-fA-F]{1,4}/[0-9]{1,3}$|^([0-9a-fA-F]{1,4}:){1,7}:/[0-9]{1,3}$|^::/[0-9]{1,3}$' > python_output.txt

# Check if PHP is installed
if ! command -v php &> /dev/null
then
    echo "Error: PHP could not be found. Please install PHP."
    exit 1
fi

# Run the PHP script
echo "Running PHP script..."
php "$PHP_SCRIPT_PATH" > php_output.txt

# Sort both outputs
echo "Sorting outputs..."
sort python_output.txt > python_sorted.txt
sort php_output.txt > php_sorted.txt

# Compare the outputs
echo "Comparing outputs..."
if diff -u python_sorted.txt php_sorted.txt > diff_output.txt; then
    echo "Test Passed: PHP and Python outputs match."
    exit 0
else
    echo "Test Failed: Outputs differ."
    echo "Differences:"
    cat diff_output.txt
    exit 1
fi
