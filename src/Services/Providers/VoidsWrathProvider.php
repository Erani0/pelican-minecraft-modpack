<?php

namespace TimVida\MinecraftModpacks\Services\Providers;

use TimVida\MinecraftModpacks\Contracts\ModpackServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoidsWrathProvider implements ModpackServiceInterface
{
    private const API_BASE = 'https://api.voidswrath.com/v1';

    public function fetchModpacks(?string $query = null, int $limit = 20, int $offset = 0): array
    {
        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . '/modpacks');

            if (!$response->successful()) {
                Log::warning('VoidsWrath API request failed', ['status' => $response->status()]);
                return ['items' => [], 'total' => 0];
            }

            $packs = $response->json();

            $items = collect($packs)->map(function ($pack) {
                return [
                    'id' => $pack['slug'] ?? $pack['id'] ?? '',
                    'name' => $pack['name'] ?? 'Unknown',
                    'summary' => $pack['description'] ?? '',
                    'icon' => $pack['icon'] ?? null,
                    'author' => 'VoidsWrath',
                    'downloads' => 0,
                    'updated_at' => null,
                ];
            })->slice($offset, $limit)->values()->toArray();

            return [
                'items' => $items,
                'total' => count($packs),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching VoidsWrath modpacks', ['error' => $e->getMessage()]);
            return ['items' => [], 'total' => 0];
        }
    }

    public function fetchVersions(string $modpackId): array
    {
        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . "/modpacks/{$modpackId}/versions");

            if (!$response->successful()) {
                return [
                    [
                        'id' => 'latest',
                        'name' => 'Latest',
                        'version_number' => 'latest',
                        'published_at' => null,
                        'downloads' => 0,
                        'changelog' => '',
                    ],
                ];
            }

            $versions = $response->json();

            if (empty($versions)) {
                return [
                    [
                        'id' => 'latest',
                        'name' => 'Latest',
                        'version_number' => 'latest',
                        'published_at' => null,
                        'downloads' => 0,
                        'changelog' => '',
                    ],
                ];
            }

            return collect($versions)->map(function ($version) {
                return [
                    'id' => $version['id'] ?? 'latest',
                    'name' => $version['name'] ?? 'Latest',
                    'version_number' => $version['version'] ?? 'latest',
                    'published_at' => $version['released_at'] ?? null,
                    'downloads' => 0,
                    'changelog' => '',
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Error fetching VoidsWrath versions', ['error' => $e->getMessage()]);
            return [
                [
                    'id' => 'latest',
                    'name' => 'Latest',
                    'version_number' => 'latest',
                    'published_at' => null,
                    'downloads' => 0,
                    'changelog' => '',
                ],
            ];
        }
    }

    public function fetchDetails(string $modpackId): ?array
    {
        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . "/modpacks/{$modpackId}");

            if (!$response->successful()) {
                return null;
            }

            $pack = $response->json();

            return [
                'id' => $pack['slug'] ?? $pack['id'] ?? $modpackId,
                'name' => $pack['name'] ?? 'Unknown',
                'summary' => $pack['description'] ?? '',
                'body' => $pack['description'] ?? '',
                'icon' => $pack['icon'] ?? null,
                'author' => 'VoidsWrath',
                'downloads' => 0,
                'followers' => 0,
                'published_at' => null,
                'updated_at' => null,
                'url' => "https://www.voidswrath.com/modpacks/{$modpackId}",
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching VoidsWrath details', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function fetchDownloadInfo(string $modpackId, string $versionId): ?array
    {
        return [
            'url' => null,
            'filename' => "voidswrath-{$modpackId}-{$versionId}.zip",
            'size' => 0,
            'hash' => null,
            'requires_launcher' => true,
        ];
    }
}
