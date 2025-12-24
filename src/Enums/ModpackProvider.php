<?php

namespace TimVida\MinecraftModpacks\Enums;

enum ModpackProvider: string
{
    case MODRINTH = 'modrinth';
    case CURSEFORGE = 'curseforge';
    case ATLAUNCHER = 'atlauncher';
    case FEEDTHEBEAST = 'feedthebeast';
    case TECHNIC = 'technic';
    case VOIDSWRATH = 'voidswrath';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::MODRINTH => 'Modrinth',
            self::CURSEFORGE => 'CurseForge',
            self::ATLAUNCHER => 'ATLauncher',
            self::FEEDTHEBEAST => 'Feed The Beast',
            self::TECHNIC => 'Technic',
            self::VOIDSWRATH => 'Voids Wrath',
        };
    }

    public function getWebsiteUrl(): string
    {
        return match ($this) {
            self::MODRINTH => 'https://modrinth.com',
            self::CURSEFORGE => 'https://www.curseforge.com',
            self::ATLAUNCHER => 'https://atlauncher.com',
            self::FEEDTHEBEAST => 'https://www.feed-the-beast.com',
            self::TECHNIC => 'https://www.technicpack.net',
            self::VOIDSWRATH => 'https://www.voidswrath.com',
        };
    }

    public function supportsSearch(): bool
    {
        return match ($this) {
            self::MODRINTH, self::CURSEFORGE, self::ATLAUNCHER,
            self::FEEDTHEBEAST, self::TECHNIC => true,
            self::VOIDSWRATH => false,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MODRINTH => 'success',
            self::CURSEFORGE => 'warning',
            self::ATLAUNCHER => 'info',
            self::FEEDTHEBEAST => 'danger',
            self::TECHNIC => 'primary',
            self::VOIDSWRATH => 'gray',
        };
    }
}
