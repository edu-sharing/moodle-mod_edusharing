# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [10.1.0] - 2025-07-23

## Added

- Optional setting for repository target chooser (default: off)
- Button group for repository targets (landing pages)

## [10.0.0] - 2025-07-14

## Added

- Compatibility with ES-Repository versions >=10

## Fixed

- Bug in metadata import logic which led to missing repository app ids in moodle config

## Changed

- Bumped the PHP version in the CI pipeline to 8.4
- Bumped the MYSQL version in the CI pipeline to 9.3.0
- Bumped the Moodle version in the CI pipeline to 5.0
- Ensured compatibility with Moodle 5.0

## [9.0.0] - 2024-12-17

### Added

- French language files
- Support for render moodle docker deployment (automatic registration with repo)

## [8.1.7] - 2024-11-07

### Changed

- Updated Moodle CI and updated files to match changed code style criteria
- Ensured compatibility with Moodle 4.5

## [8.1.6] - 2024-08-13

### Fixed

- Login requirement for app metadata query is now optional

### Changed

- Plugin Logo now uses new Moodle Monologo standard

## [8.1.5] - 2024-07-26

### Fixed

- Minor bug in Edu-Sharing API client library. Trying to sign data without a private key being set no longer leads to a type error.

## [8.1.4] - 2024-07-17

### Fixed

- Potential parsing error in restore logic caused by imprecise regular expressions. Replaced with DOM parsing
- Fatal error on course restoration with corrupted HTML in ES objects
- Activity duplication now creates new Edu-Sharing resources in moodle database

## [8.1.3] - 2024-06-03

### Added

- WYSIWYG visibility capability to replace old atto capability

### Fixed

- Ambiguous replies from the repository usage creation endpoint could lead to a type error during course restoration. A proper check has been added to prevent a runtime error.

## [8.1.2] - 2024-05-17

### Added

- Better logging in get_usage method

### Fixed

- Critical bug: Compatibility with Moodle versions < 4.2 restored (external_api)

## [8.1.1] - 2024-05-08

### Added

- Option to pseudonomyze Moodle users in the connected Edu-Sharing repository

## [8.1.0] - 2024-05-03

### Changed

- Major refactoring to update plugin to current Moodle CI requirements

### Added

- GitLab CI pipeline including Moodle CI checks

### Fixed

- Course restoration does no longer crash on missing user rights for contained ES-object
- Deleting courses with Edu-Sharing objects no longer leads to an SQL error

## [8.0.9] - 2024-03-15

### Fixed

- Type errors in course restoration and duplication

## [8.0.8] - 2024-03-14

### Fixed

- Backwards compatibility issue with external_api caused problems in Moodle versions < 4.2

## [8.0.7] - 2024-02-15

### Fixed

- Type error message which occurred when editing a course section

## [8.0.6] - 2024-02-04

### Fixed

- Javascript logic bug which led to error when embedding objects with missing version property 

## [8.0.5] - 2024-02-01

### Fixed

- Javascript syntax error in activity embedding form logic

## [8.0.4] - 2024-01-29

### Added

- New config entry for authentication suffix to be added to submitted authentication parameter

### Fixed

- When adding a node with an empty version array as an activity, the UI-elements in the form are now properly filled and no JS error occurs

## [8.0.3] - 2024-01-14

### Added 

- Rest service to update instances
- Service function to fetch preview images
- Preview image script now moved to activity plugin
- Embedded ES-Objects are now tracked

### Fixed

- Choosing an object version is now longer possible when editing Edu-Sharing activity
- App ID can now contain periods

### Changed

- Refactored code and doc blocks to conform with moodle guidelines 

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
