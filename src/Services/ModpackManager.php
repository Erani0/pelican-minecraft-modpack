<?php

namespace TimVida\MinecraftModpacks\Services;

use TimVida\MinecraftModpacks\Contracts\ModpackServiceInterface;
use TimVida\MinecraftModpacks\Enums\ModpackProvider;
use TimVida\MinecraftModpacks\Services\Providers\ATLauncherProvider;
use TimVida\MinecraftModpacks\Services\Providers\CurseForgeProvider;
use TimVida\MinecraftModpacks\Services\Providers\FeedTheBeastProvider;
use TimVida\MinecraftModpacks\Services\Providers\ModrinthProvider;
use TimVida\MinecraftModpacks\Services\Providers\TechnicProvider;
use TimVida\MinecraftModpacks\Services\Providers\VoidsWrathProvider;
use Illuminate\Support\Facades\Cache;

class ModpackManager
{
    private array $providers = [];

    public function __construct()
    {
        $this->initializeProviders();
    }

    private function initializeProviders(): void
    {
        $this->providers = [
            ModpackProvider::MODRINTH->value => new ModrinthProvider(),
            ModpackProvider::CURSEFORGE->value => new CurseForgeProvider(),
            ModpackProvider::ATLAUNCHER->value => new ATLauncherProvider(),
            ModpackProvider::FEEDTHEBEAST->value => new FeedTheBeastProvider(),
            ModpackProvider::TECHNIC->value => new TechnicProvider(),
            ModpackProvider::VOIDSWRATH->value => new VoidsWrathProvider(),
        ];
    }

    public function getProvider(ModpackProvider $provider): ModpackServiceInterface
    {
        return $this->providers[$provider->value];
    }

    public function searchModpacks(
        ModpackProvider $provider,
        ?string $query = null,
        int $page = 1,
        int $perPage = 20
    ): array {
        $cacheKey = $this->buildCacheKey('search', $provider, $query, $page, $perPage);

        return Cache::remember($cacheKey, config('minecraft-modpacks.cache_duration', 1800), function () use ($provider, $query, $page, $perPage) {
            $offset = ($page - 1) * $perPage;
            $service = $this->getProvider($provider);

            return $service->fetchModpacks($query, $perPage, $offset);
        });
    }

    public function getModpackVersions(ModpackProvider $provider, string $modpackId): array
    {
        $cacheKey = $this->buildCacheKey('versions', $provider, $modpackId);

        return Cache::remember($cacheKey, config('minecraft-modpacks.cache_duration', 1800), function () use ($provider, $modpackId) {
            $service = $this->getProvider($provider);

            return $service->fetchVersions($modpackId);
        });
    }

    public function getModpackDetails(ModpackProvider $provider, string $modpackId): ?array
    {
        $cacheKey = $this->buildCacheKey('details', $provider, $modpackId);

        return Cache::remember($cacheKey, config('minecraft-modpacks.cache_duration', 1800), function () use ($provider, $modpackId) {
            $service = $this->getProvider($provider);

            return $service->fetchDetails($modpackId);
        });
    }

    public function getDownloadInfo(ModpackProvider $provider, string $modpackId, string $versionId): ?array
    {
        $cacheKey = $this->buildCacheKey('download', $provider, $modpackId, $versionId);

        return Cache::remember($cacheKey, config('minecraft-modpacks.cache_duration', 1800), function () use ($provider, $modpackId, $versionId) {
            $service = $this->getProvider($provider);

            return $service->fetchDownloadInfo($modpackId, $versionId);
        });
    }

    public function clearCache(?ModpackProvider $provider = null): void
    {
        if ($provider) {
            Cache::flush();
        } else {
            Cache::flush();
        }
    }

    private function buildCacheKey(string $operation, ModpackProvider $provider, ...$params): string
    {
        $paramString = implode(':', array_filter($params, fn($p) => $p !== null));

        return sprintf(
            'modpacks:%s:%s:%s',
            $operation,
            $provider->value,
            md5($paramString)
        );
    }

    public function getAllProviders(): array
    {
        return ModpackProvider::cases();
    }
}
