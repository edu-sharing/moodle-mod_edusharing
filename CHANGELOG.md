# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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