#!/bin/bash
#
# This script is used to run phpcs only on the files lines that have been changed in the current branch.
#
# Parameters
# $1 - operation_mode (diff or selected) (default: diff)
# $2 - base branch (default: origin/main)
# $3 - file list (if operation_mode is selected)
#
# parameters
OPERATION_MODE="${1:-diff}"
BASE_BRANCH="${2:-origin/main}"

# check if operation mode is valid
if [ "$OPERATION_MODE" != "diff" ] && [ "$OPERATION_MODE" != "selected" ]; then
	echo "Invalid operation mode: $OPERATION_MODE. Valid values are: diff, selected."
	exit 1
fi

# if operation mode is selected, check if file list is provided
if [ "$OPERATION_MODE" = "selected" ]; then
	if [ -z "$3" ]; then
		echo "File list is required for operation mode: selected."
		exit 1
	fi
fi

# constants
DIFF_FILE="diff.txt"
DIFF_PHPCS_FILE="diffcs.txt"
PHPCS_JSON_FILE="phpcs.json"

# generate diff of php files
if [ "$OPERATION_MODE" = "diff" ]; then
	git diff "$BASE_BRANCH" --name-only --diff-filter=ACMR -- '*.php' >$DIFF_PHPCS_FILE
elif [ "$OPERATION_MODE" = "selected" ]; then
	shopt -s globstar
	echo "$3" >$DIFF_PHPCS_FILE
fi

# if empty, exit
if [ ! -s $DIFF_PHPCS_FILE ]; then
	echo "No php files to check."
	exit 0
fi

# generate diff of lines
git diff "$BASE_BRANCH" >$DIFF_FILE

# checking files
phpcs --standard=phpcs.xml --report=json --file-list=$DIFF_PHPCS_FILE >$PHPCS_JSON_FILE || true

# validate the phpcs result
if [ "$(head -c 1 $PHPCS_JSON_FILE)" != "{" ]; then
	echo "Invalid json file generated:"
	cat $PHPCS_JSON_FILE
	exit 1
fi

# filter the diff file
vendor/bin/diffFilter --phpcs $DIFF_FILE $PHPCS_JSON_FILE
