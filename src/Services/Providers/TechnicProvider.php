<?php

namespace TimVida\MinecraftModpacks\Services\Providers;

use TimVida\MinecraftModpacks\Contracts\ModpackServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TechnicProvider implements ModpackServiceInterface
{
    private const API_BASE = 'https://api.technicpack.net';

    public function fetchModpacks(?string $query = null, int $limit = 20, int $offset = 0): array
    {
        try {
            // Technic requires a search query, use 'Technic' as default
            $searchQuery = empty($query) ? trans('minecraft-modpacks::modpacks.ui.providers.technic') : $query;

            $build = $this->getBuild();

            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . '/search', [
                    'q' => $searchQuery,
                    'build' => $build,
                ]);

            if (!$response->successful()) {
                Log::warning(trans('minecraft-modpacks::modpacks.providers.technic.warning.log1'), ['status' => $response->status()]);
                return ['items' => [], 'total' => 0];
            }

            $data = $response->json();
            $packs = $data['modpacks'] ?? [];

            $items = collect($packs)->take($limit)->map(function ($pack) {
                return [
                    'id' => $pack['slug'] ?? $pack['name'],
                    'name' => $pack['name'] ?? trans('minecraft-modpacks::modpacks.ui.providers.unknown'),
                    'summary' => $pack['description'] ?? '',
                    'icon' => $pack['iconUrl'] ?? null,
                    'author' => trans('minecraft-modpacks::modpacks.ui.providers.technic'),
                    'downloads' => 0,
                    'updated_at' => null,
                ];
            })->values()->toArray();

            return [
                'items' => $items,
                'total' => count($items),
            ];
        } catch (\Exception $e) {
            Log::error(trans('minecraft-modpacks::modpacks.providers.technic.error.log1'), ['error' => $e->getMessage()]);
            return ['items' => [], 'total' => 0];
        }
    }

    public function fetchVersions(string $modpackId): array
    {
        $details = $this->fetchDetails($modpackId);

        if (!$details || empty($details['version'])) {
            return [];
        }

        return [
            [
                'id' => $details['version'],
                'name' => $details['version'],
                'version_number' => $details['version'],
                'published_at' => null,
                'downloads' => 0,
                'changelog' => '',
            ],
        ];
    }

    private function getBuild(): string
    {
        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . '/launcher/version/stable4');

            if ($response->successful()) {
                $data = $response->json();
                return (string) ($data['build'] ?? '822');
            }
        } catch (\Exception $e) {
            Log::error(trans('minecraft-modpacks::modpacks.providers.technic.error.log2'), ['error' => $e->getMessage()]);
        }

        return '822'; // Fallback build number
    }

    public function fetchDetails(string $modpackId): ?array
    {
        try {
            $build = $this->getBuild();

            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . '/modpack/' . urlencode($modpackId), [
                    'build' => $build,
                ]);

            if (!$response->successful()) {
                return null;
            }

            $pack = $response->json();

            if (!isset($pack['name'])) {
                return null;
            }

            return [
                'id' => $modpackId,
                'name' => $pack['displayName'] ?? $pack['name'] ?? trans('minecraft-modpacks::modpacks.ui.providers.unknown'),
                'summary' => $pack['description'] ?? '',
                'body' => $pack['description'] ?? '',
                'icon' => $pack['icon']['url'] ?? null,
                'author' => $pack['user'] ?? trans('minecraft-modpacks::modpacks.ui.providers.technic'),
                'downloads' => $pack['runs'] ?? 0,
                'followers' => 0,
                'published_at' => null,
                'updated_at' => null,
                'url' => $pack['platformUrl'] ?? "https://www.technicpack.net/modpack/{$modpackId}",
                'version' => $pack['version'] ?? trans('minecraft-modpacks::modpacks.ui.common.latest'),
            ];
        } catch (\Exception $e) {
            Log::error(trans('minecraft-modpacks::modpacks.providers.technic.error.log3'), ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function fetchDownloadInfo(string $modpackId, string $versionId): ?array
    {
        $details = $this->fetchDetails($modpackId);

        if (!$details) {
            return null;
        }

        return [
            'url' => null,
            'filename' => "technic-{$modpackId}.zip",
            'size' => 0,
            'hash' => null,
            'requires_launcher' => true,
        ];
    }
}