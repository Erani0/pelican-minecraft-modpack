# Contributing to Minecraft Modpacks Plugin

First off, thank you for considering contributing to this plugin! It's people like you that make this project better for everyone.

## 🤝 Code of Conduct

This project and everyone participating in it is governed by our commitment to providing a welcoming and inspiring community for all. Please be respectful and constructive in your interactions.

## 🐛 Found a Bug?

If you find a bug in the source code, you can help by submitting an issue to our [GitHub Repository](https://github.com/timvida/pelican-minecraft-modpack/issues). Even better, you can submit a Pull Request with a fix.

### Before Submitting a Bug Report

- **Check existing issues** to see if the problem has already been reported
- **Check the documentation** to ensure you're using the plugin correctly
- **Update to the latest version** to see if the issue still exists

### How to Submit a Good Bug Report

Include the following information:

- **Clear title and description** of the issue
- **Steps to reproduce** the behavior
- **Expected behavior** vs actual behavior
- **Screenshots** if applicable
- **Environment details**:
  - Pelican Panel version
  - PHP version
  - Plugin version
  - Which provider you were using
- **Relevant logs** from `/var/www/pelican/storage/logs/`

## 💡 Want to Contribute Code?

### Getting Started

1. **Fork the repository** and clone it locally
2. **Create a branch** for your changes:
   ```bash
   git checkout -b feature/my-new-feature
   # or
   git checkout -b fix/issue-123
   ```

3. **Make your changes** following our coding standards (see below)

4. **Test your changes** thoroughly

5. **Commit your changes** with clear commit messages:
   ```bash
   git commit -m "Add feature: XYZ"
   # or
   git commit -m "Fix: Resolve issue with CurseForge API"
   ```

6. **Push to your fork**:
   ```bash
   git push origin feature/my-new-feature
   ```

7. **Open a Pull Request** with a clear title and description

### Coding Standards

This project follows PSR-12 coding standards. Please ensure your code:

- Uses **PSR-12** formatting
- Includes **type hints** for parameters and return values
- Has **meaningful variable names**
- Includes **PHPDoc comments** for classes and methods
- Follows **SOLID principles**

Example:

```php
<?php

namespace TimVida\MinecraftModpacks\Services\Providers;

use TimVida\MinecraftModpacks\Contracts\ModpackServiceInterface;

/**
 * Provider implementation for Example Platform.
 */
class ExampleProvider implements ModpackServiceInterface
{
    /**
     * Fetch modpacks from the provider.
     *
     * @param string|null $query Search query
     * @param int $limit Number of results per page
     * @param int $offset Pagination offset
     * @return array{items: array, total: int}
     */
    public function fetchModpacks(?string $query = null, int $limit = 20, int $offset = 0): array
    {
        // Implementation
    }
}
```

### Testing

Before submitting your PR, ensure:

- Your code works with PHP 8.1, 8.2, and 8.3
- You've tested with Pelican Panel
- API integrations are working correctly
- Error handling is in place

## 🎨 Adding a New Provider

To add support for a new modpack platform:

1. **Create a new provider class**:
   ```php
   src/Services/Providers/YourProviderName.php
   ```

2. **Implement the interface**:
   ```php
   class YourProvider implements ModpackServiceInterface
   {
       public function fetchModpacks(?string $query = null, int $limit = 20, int $offset = 0): array { }
       public function fetchVersions(string $modpackId): array { }
       public function fetchDetails(string $modpackId): ?array { }
       public function fetchDownloadInfo(string $modpackId, string $versionId): ?array { }
   }
   ```

3. **Add to the enum**:
   ```php
   // src/Enums/ModpackProvider.php
   case YOURPROVIDER = 'yourprovider';
   ```

4. **Register in ModpackManager**:
   ```php
   // src/Services/ModpackManager.php
   private function initializeProviders(): void
   {
       $this->providers = [
           // ... existing providers
           ModpackProvider::YOURPROVIDER->value => new YourProvider(),
       ];
   }
   ```

5. **Update documentation**:
   - Add to README.md features list
   - Update provider comparison table
   - Add any configuration requirements

## 📝 Documentation

Improvements to documentation are always welcome! This includes:

- Fixing typos or unclear wording
- Adding examples
- Improving installation instructions
- Adding troubleshooting guides

## 🔄 Pull Request Process

1. **Update documentation** if needed (README.md, inline comments)
2. **Follow the coding standards** mentioned above
3. **Keep PRs focused** - one feature or fix per PR
4. **Write clear commit messages**
5. **Respond to feedback** from maintainers

### PR Title Format

Use conventional commit format:

- `feat: Add support for new provider`
- `fix: Resolve CurseForge API timeout issue`
- `docs: Update installation instructions`
- `refactor: Improve caching mechanism`
- `style: Fix code formatting`
- `test: Add tests for Modrinth provider`

## 💬 Communication

- **GitHub Issues**: For bug reports and feature requests
- **GitHub Discussions**: For questions and general discussion
- **Pull Request Comments**: For code review discussions

## 🏆 Recognition

Contributors will be recognized in:
- The project README
- Release notes
- GitHub contributors page

## ❓ Questions?

Feel free to:
- Open a [GitHub Discussion](https://github.com/timvida/pelican-minecraft-modpack/discussions)
- Comment on existing issues
- Reach out to the maintainers

## 📜 License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing to making Minecraft server management easier for everyone! 🎮
