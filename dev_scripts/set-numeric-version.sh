#!/bin/bash
#
# This script is used to prepare a release:
# - Update the version number in the plugin file
# - Replace all instances of "TBD" in the code with the version number
#
# Parameters
# $1 - version number (x.x.x or x.x.x.x)
#

base_dir=$(cd "$(dirname "${BASH_SOURCE:-$0}")" && pwd)

# Get the value to replace "TBD" with as an argument
replace_value=$1

function validate() {
	# Check if replace_value is set
	if [ -z "$replace_value" ]; then
		echo "Error: You must pass in the version you wish to replace 'TBD' with."
		exit 1
	fi

	# Check if replace_value is in the correct format
	if ! [[ $replace_value =~ ^[0-9]+\.[0-9]+\.[0-9]+$|^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
		echo "Error: replace_value argument must be in the format 'x.x.x' or 'x.x.x.x'"
		exit 1
	fi
}

function replace_tbd_in_files() {
	# Find all files with "TBD" in them
	# shellcheck disable=SC2062,SC2035
	files_with_tbd=$(grep --exclude-dir={vendor,node_modules,vendor-prefixed,dev_scripts} --exclude='*.md' -Irnw * -e 'TBD' | cut -d':' -f1)

	# Loop through each file
	for file in $files_with_tbd; do
		echo "Replacing TBD with $replace_value in $file"
		sed -i "s/TBD/$replace_value/g" "$file"
	done
}

function update_plugin_file() {
	# Update the version number in the plugin file
	echo "Updating version number in plugin file"
	sed -i "s/Version:.*$/Version: $replace_value/" "$base_dir/../memberdash.php"
	sed -i "s/'MEMBERDASH_VERSION', '[0-9.]*/'MEMBERDASH_VERSION', '$replace_value/" "$base_dir/../memberdash.php"
}

# validate parameters
validate

# replace TBD with version number
replace_tbd_in_files

# update plugin file
update_plugin_file
