# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [8.0.2] - 2023-11-17

### Added

- Setting to submit users' names, surnames and email addresses for app auth (default yes)
- Rest services to add and delete instances.
- Rest service to get ticket
- Compatibility with new tinyMCE-Plugin
- Possibility to add moodle host in installConfig.json for automatic registration

### Removed

- Compatibility with legacy ES Tiny-Editor-Plugin

### Fixed

- Type error which occurred when restoring courses containing sections with null values in the summary field of the course_section table
- Logic bug in usage deletion logic which prevented usages from being deleted properly in the repository.

##  [8.0.1] - 2023-10-19

### Fixed

- Upgrade type error blocking update from older versions

##  [8.0.0] - 2023-10-01

### Added

- Unit test folder and unit test classes
- Possibility to define internal docker network URL
- Changelog
- English translation for upload button in edu-sharing embed dialogue modal 

### Removed

- Compatibility with legacy edu-sharing SOAP API, plugin now uses REST by default

### Fixed

- Slashes are now automatically stripped from the end of user provided URLs
- Version picking now works
- Version radio buttons are now longer displayed in the embedding dialogue if the object is a published copy or a reference to a collection. Defaults to current version.
- Restoration of course backup files no longer fails on edu-sharing objects for which the current user lacks publish rights. Instead of throwing an error the restore script omits the respective items.
- Folders can now longer be added as edu-sharing resources. 

### Changed

- Major refactoring in order to match updated moodle criteria as well as to facilitate unit testing