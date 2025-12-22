# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Additional provider support
- Enhanced caching strategies
- Improved error messages
- Modpack update notifications

## [1.0.0] - 2025-01-XX

### Added
- Initial release of Minecraft Modpacks plugin
- Support for 6 modpack providers:
  - Modrinth (full support)
  - CurseForge (full support with API key)
  - ATLauncher (search & browse)
  - Feed The Beast (search & browse)
  - Technic (search required)
  - Voids Wrath (browse only)
- Modpack search functionality across all providers
- Version selection for modpack installations
- One-click modpack installation
- Optional server file deletion before installation
- Smart caching system with configurable duration
- Modern Filament-based UI with:
  - Grid/table view of modpacks
  - Provider filtering dropdown
  - Search functionality
  - Clear cache button
  - Installation dialogs
- Admin configuration panel for:
  - CurseForge API key management
  - Cache duration settings
  - Request timeout settings
- Comprehensive error handling and logging
- Multi-language support structure (English by default)

### Features
- Interface-based provider architecture for easy extensibility
- Centralized ModpackManager service with caching
- Dedicated ModpackInstaller service for safe installations
- PSR-12 compliant codebase
- PHP 8.1, 8.2, and 8.3 compatibility
- Full integration with Pelican Panel permissions system

### Documentation
- Comprehensive README with installation instructions
- Contributing guidelines (CONTRIBUTING.md)
- Security policy (SECURITY.md)
- MIT License
- GitHub issue templates
- Pull request template

### Developer Experience
- Clean, modular architecture
- Well-documented code with PHPDoc
- Type-safe implementation with strict types
- Easy to extend with new providers
- Follows Laravel/Pelican best practices

[Unreleased]: https://github.com/timvida/pelican-minecraft-modpack/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/timvida/pelican-minecraft-modpack/releases/tag/v1.0.0
