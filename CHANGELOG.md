# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- Tweaked regular expressions for token matching/replacing.
- Fixed/simplified vertical whitespace issues.
- Issue with fenced code blocks: their formatting is now untouched, and they no longer interfere with compiling the document or parsing headings.

### Changed

- Replaced Table of Contents Markdown syntax with [[_TOC_]] (from Gitlab Flavoured Markdown)

## [0.0.4] - 2022-10-30

### Fixed

- Added back `$mdOut` property, removing it broke the `saveFile()` method.

### Changed

- Minor code refactor to `runCompile()`/`saveFile()` (`$isCompiled` property removed as it is redundant).

## [0.0.3] - 2022-10-29

### Fixed

- Issue where the main document heading was being removed when the Table of Contents was inserted immediately after it.
- Issue where more than one Table of Contents would be inserted, now only the first occurance of the '`toc`' Markdown syntax will be replaced.
- Various issues with the regex for finding/replacing the Markdown syntax for file includes and Table of Contents and parsing headings.

### Changed

- The `runCompiler()` method now no longer stores the raw compiled document (before any Table of Contents is inserted) as a class property.

### Removed

- Removed the `getOutput()` method and `$mdOut` property.

## [0.0.2] - 2022-10-27

### Fixed

- Refactored/improved various bits of code
- Heading level adjustments now correct
- Existing heading IDs/anchors no longer overwritten
- Generated heading IDs/anchors now based on heading text (alphanumeric ascii chars only, plus dashes)
- Fixed incorrect namespace and references to the `phpmdcompiler` repo dir name.

### Added

- CHANGELOG.md

## [0.0.1] - 2022-10-25

### Added

- Initial commit.

[Unreleased]: https://github.com/lmd-code/phpmdcompiler/compare/v0.0.4...HEAD
[0.0.4]: https://github.com/lmd-code/phpmdcompiler/releases/tag/v0.0.4
[0.0.3]: https://github.com/lmd-code/phpmdcompiler/releases/tag/v0.0.3
[0.0.2]: https://github.com/lmd-code/phpmdcompiler/releases/tag/v0.0.2
[0.0.1]: https://github.com/lmd-code/phpmdcompiler/releases/tag/v0.0.1
