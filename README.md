# MemberDash

The most powerful, easy to use and flexible membership plugin for WordPress sites available.

[![Release](https://github.com/stellarwp/memberdash/actions/workflows/release.yml/badge.svg)](https://github.com/stellarwp/memberdash/actions/workflows/release.yml)

[![Code Standards](https://github.com/stellarwp/memberdash/actions/workflows/ci-standards.yml/badge.svg)](https://github.com/stellarwp/memberdash/actions/workflows/ci-standards.yml)
[![PHPStan](https://github.com/stellarwp/memberdash/actions/workflows/ci-analysis.yml/badge.svg)](https://github.com/stellarwp/memberdash/actions/workflows/ci-analysis.yml)
[![PHP Compatibility](https://github.com/stellarwp/memberdash/actions/workflows/ci-compatibility.yml/badge.svg)](https://github.com/stellarwp/memberdash/actions/workflows/ci-compatibility.yml)

## Table of content

- [MemberDash](#memberdash)
    - [Table of content](#table-of-content)
    - [Contributing guide](#contributing-guide)
    - [Release instructions](#release-instructions)
        - [Pre-release](#pre-release)
        - [Release](#release)

## Contributing guide

Please follow the [contributing guide](CONTRIBUTING.md) for the MemberDash.

## Release instructions

### Pre-release

A release branch must be merged into the `main` branch through a pull request. Before merging the release Pull Request, please do the following:

- In the related release branch (e.g., `release/L23.something`):
    - Run the `composer set_numeric_version <numeric_version>` (e.g., `composer set_numeric_version 1.0.0`) to:
        - Replace `TBD` in the code with the actual release version number.
        - Replace the plugin tags in the [memberdash.php](memberdash.php) file to reflect the actual version number. The automation will prevent you from releasing a version with a version number different from the tag version.
    - Verify and push the changes to the release branch.
    - Make sure all Pull Request checks are green (passing).

### Release

Create a tag in the repository with the version number you want to release. For example, if you want to release the version **1.0.0** then create a tag with the name `v1.0.0`. Example:

```bash
# make sure you have the latest version of the repository
git checkout main && git pull

# generate and push the new tag
git tag v1.0.0 && git push origin v1.0.0
```

Wait for the release to be published on the [GitHub Releases](https://github.com/stellarwp/memberdash/releases) page. The release will have the `memberdash.zip` file attached to it. Download the file and upload it to the licensing system.
