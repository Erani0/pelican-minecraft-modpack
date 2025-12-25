# Minecraft Modpacks Plugin for Pelican

[![License](https://img.shields.io/github/license/timvida/pelican-minecraft-modpack)](https://github.com/timvida/pelican-minecraft-modpack/blob/main/LICENSE)
[![Issues](https://img.shields.io/github/issues/timvida/pelican-minecraft-modpack)](https://github.com/timvida/pelican-minecraft-modpack/issues)
[![Stars](https://img.shields.io/github/stars/timvida/pelican-minecraft-modpack)](https://github.com/timvida/pelican-minecraft-modpack/stargazers)

A comprehensive Pelican Panel plugin that enables browsing and installing Minecraft modpacks from multiple platforms directly within your server management interface.

## 🎯 Features

### Multi-Platform Support
Browse and install modpacks from **6 different platforms**:
- 🟢 **Modrinth** - Modern modpack hosting with full API support
- 🟠 **CurseForge** - Industry-leading mod distribution platform
- 🔵 **ATLauncher** - Community-focused launcher with curated packs
- 🔴 **Feed The Beast** - Classic modpack platform
- ⚪ **Technic** - Popular modpack launcher
- 🟣 **Voids Wrath** - Community modpacks

### Core Functionality
- ✨ **One-Click Installation** - Install modpacks with just a few clicks
- 🔍 **Advanced Search** - Search across all platforms
- 📦 **Version Selection** - Choose specific modpack versions
- 🎨 **Modern UI** - Clean, intuitive Filament-based interface
- ⚡ **Smart Caching** - Automatic API response caching for performance
- 🔄 **Provider Filtering** - Switch between platforms seamlessly
- 🗑️ **Safe Installation** - Optional server file cleanup before installation
- 🌐 **Multi-Language Support (WIP)** - 23 languages: DE, EN, CS, DA, ES, FR, HU, ID, IT, NL, NO, PL, PT, RO, SK, SR, SV, TR, Pirate

## 📋 Requirements

- **Pelican Panel** ^1.0.0
- **PHP** 8.1, 8.2, or 8.3
- **Minecraft Server** (any Java edition)
- **CurseForge API Key** (only for CurseForge provider)

## 🚀 Installation

### Option 1: Via Composer (Recommended)

```bash
cd /var/www/pelican
composer require timvida/pelican-minecraft-modpack
php artisan pelican:plugin:install minecraft-modpacks
```

### Option 2: Manual Installation

1. Download the latest release from [GitHub Releases](https://github.com/timvida/pelican-minecraft-modpack/releases)

2. Extract to your Pelican plugins directory:
   ```bash
   cd /var/www/pelican/plugins
   unzip pelican-minecraft-modpack-main.zip
   mv pelican-minecraft-modpack-main minecraft-modpacks
   ```

3. Install the plugin:
   ```bash
   cd /var/www/pelican
   php artisan pelican:plugin:install minecraft-modpacks
   ```

### Option 3: Git Clone

```bash
cd /var/www/pelican/plugins
git clone https://github.com/timvida/pelican-minecraft-modpack.git minecraft-modpacks
cd /var/www/pelican
php artisan pelican:plugin:install minecraft-modpacks
```

## ⚙️ Configuration

### CurseForge API Key Setup

CurseForge requires an API key to access their platform:

1. Visit [CurseForge Console](https://console.curseforge.com/)
2. Create an account or sign in
3. Navigate to API Keys section
4. Generate a new API key
5. Add the key in Pelican:
   - Go to **Admin** → **Plugins** → **Minecraft Modpacks** → **Settings**
   - Enter your API key in the "CurseForge API Key" field
   - Click "Save Settings"

### Environment Variables

Alternatively, configure via `.env` file:

```env
# CurseForge API Key (required for CurseForge support)
CURSEFORGE_API_KEY=your_api_key_here

# Cache duration in seconds (default: 1800 = 30 minutes)
MODPACKS_CACHE_DURATION=1800

# API request timeout in seconds (default: 10)
MODPACKS_REQUEST_TIMEOUT=10

# Items per page (default: 20)
MODPACKS_PER_PAGE=20
```

## 📖 Usage

### Installing a Modpack

1. Navigate to your Minecraft server in Pelican Panel
2. Click **"Modpacks"** in the server sidebar
3. Select a provider from the dropdown menu
4. Browse or search for your desired modpack
5. Click the **download icon** (⬇️) on the modpack
6. Select the version you want to install
7. Choose whether to delete existing server files (⚠️ Warning: This removes all files!)
8. Click **"Install"** to begin installation

### Provider Details

| Provider | Search | Direct Download | Notes |
|----------|--------|-----------------|-------|
| Modrinth | ✅ | ✅ | Full featured, recommended |
| CurseForge | ✅ | ✅ | Requires API key |
| ATLauncher | ✅ | ⚠️ | Limited direct downloads |
| Feed The Beast | ✅ | ⚠️ | Limited direct downloads |
| Technic | ✅ | ⚠️ | Search required |
| Voids Wrath | ❌ | ⚠️ | Browse only |

⚠️ = Some modpacks may require manual launcher installation

## 🛠️ Development

### Project Structure

```
minecraft-modpacks/
├── plugin.json                      # Plugin metadata
├── composer.json                    # Package dependencies
├── LICENSE                          # MIT License
├── README.md                        # Documentation
├── .gitignore                       # Git ignore rules
├── config/
│   └── minecraft-modpacks.php       # Configuration file
├── database/
│   └── Seeders/
│       └── MinecraftModpacksSeeder.php # Database seeder
├── lang/                            # Multi-language support (23 languages WIP)
│   ├── cs-CZ/modpacks.php           # Czech
│   ├── da-DK/modpacks.php           # Danish
│   ├── de-DE/modpacks.php           # German
│   ├── dutch/modpacks.php           # Dutch
│   ├── en/modpacks.php              # English
│   ├── es-ES/modpacks.php           # Spanish
│   ├── fi-FI/modpacks.php           # Finnish
│   ├── fr-FR/modpacks.php           # French
│   ├── hu-HU/modpacks.php           # Hungarian
│   ├── id-ID/modpacks.php           # Indonesian
│   ├── it-IT/modpacks.php           # Italian
│   ├── lt-LT/modpacks.php           # Lithuanian
│   ├── nl-NL/modpacks.php           # Dutch (NL)
│   ├── no-NO/modpacks.php           # Norwegian
│   ├── pirat/modpacks.php           # Pirate language
│   ├── pl-PL/modpacks.php           # Polish
│   ├── pt-BR/modpacks.php           # Brazilian Portuguese
│   ├── pt-PT/modpacks.php           # Portuguese
│   ├── ro-RO/modpacks.php           # Romanian
│   ├── sk-SK/modpacks.php           # Slovak
│   ├── sr-SP/modpacks.php           # Serbian
│   ├── sv-SE/modpacks.php           # Swedish
│   └── tr-TR/modpacks.php           # Turkish
└── src/
    ├── MinecraftModpacksPlugin.php      # Main plugin class
    ├── Providers/
    │   └── MinecraftModpacksPluginProvider.php # Laravel service provider
    ├── Contracts/
    │   └── ModpackServiceInterface.php  # Service contract
    ├── Enums/
    │   └── ModpackProvider.php          # Provider enumeration
    ├── Services/
    │   ├── ModpackManager.php           # Central manager
    │   ├── ModpackInstaller.php         # Installation handler
    │   └── Providers/
    │       ├── ModrinthProvider.php
    │       ├── CurseForgeProvider.php
    │       ├── ATLauncherProvider.php
    │       ├── FeedTheBeastProvider.php
    │       ├── TechnicProvider.php
    │       └── VoidsWrathProvider.php
    └── Filament/
        └── Server/
            └── Pages/
                └── ModpackBrowser.php   # UI component
```

### Architecture

- **Interface-based design**: All providers implement `ModpackServiceInterface`
- **Centralized caching**: `ModpackManager` handles caching with MD5-based keys
- **Separation of concerns**: Each provider handles its own API integration
- **Filament integration**: Modern UI built with Filament components

### Adding a New Provider

1. Create a new provider class implementing `ModpackServiceInterface`
2. Add the provider to `ModpackProvider` enum
3. Register in `ModpackManager::initializeProviders()`
4. Implement all required methods: `fetchModpacks()`, `fetchVersions()`, `fetchDetails()`, `fetchDownloadInfo()`

## 🐛 Troubleshooting

### Modpacks Not Loading

- **Check internet connection**: Ensure your server can reach external APIs
- **Verify API key**: For CurseForge, ensure your API key is correct
- **Clear cache**: Use the "Clear Cache" button in the plugin
- **Check logs**: Review Pelican logs at `/var/www/pelican/storage/logs/`

### Installation Fails

- **Disk space**: Ensure adequate free space on the server
- **Permissions**: Verify file permissions are correct
- **Compatibility**: Some modpacks may require specific Minecraft versions
- **Launcher requirement**: Some providers need manual launcher-based installation

### Common Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| "CurseForge API key not configured" | Missing API key | Add CurseForge API key in settings |
| "Installation Failed" | Various | Check server logs for details |
| "Could not start modpack installation" | Download URL missing | Modpack may require launcher |

## 🤝 Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

### Quick Start for Contributors

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Credits

- **Author**: [timvida](https://github.com/timvida)
- **Built for**: [Pelican Panel](https://pelican.dev)
- **Supported Platforms**: Modrinth, CurseForge, ATLauncher, Feed The Beast, Technic, Voids Wrath

## 📊 Support

- **Issues**: [GitHub Issues](https://github.com/timvida/pelican-minecraft-modpack/issues)
- **Discussions**: [GitHub Discussions](https://github.com/timvida/pelican-minecraft-modpack/discussions)
- **Security**: See [SECURITY.md](SECURITY.md) for reporting vulnerabilities

## 📈 Changelog

### Version 1.0.0 (Initial Release)

#### Features
- ✨ Multi-platform support (6 providers)
- 🔍 Search and browse functionality
- 📦 Direct modpack installation
- ⚡ Intelligent caching system
- 🎨 Modern Filament UI
- ⚙️ Configurable via admin panel

#### Supported Providers
- Modrinth (full support)
- CurseForge (full support with API key)
- ATLauncher (search & browse)
- Feed The Beast (search & browse)
- Technic (search required)
- Voids Wrath (browse only)

---

**Made with ❤️ for the Minecraft and Pelican communities**
