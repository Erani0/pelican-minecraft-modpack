<?php

namespace TimVida\MinecraftModpacks\Services\Providers;

use TimVida\MinecraftModpacks\Contracts\ModpackServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoidsWrathProvider implements ModpackServiceInterface
{
    // VoidsWrath uses a static JSON file, not a real API
    private const JSON_URL = 'https://www.ric-rac.org/minecraft-modpack-server-installer/voidswrath.json';

    public function fetchModpacks(?string $query = null, int $limit = 20, int $offset = 0): array
    {
        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::JSON_URL);

            if (!$response->successful()) {
                Log::warning('VoidsWrath JSON request failed', ['status' => $response->status()]);
                return ['items' => [], 'total' => 0];
            }

            $packs = $response->json();

            if (!is_array($packs)) {
                Log::error('VoidsWrath response is not an array');
                return ['items' => [], 'total' => 0];
            }

            // Map all packs first
            $allItems = collect($packs)->map(function ($pack) {
                return [
                    'id' => (string) ($pack['id'] ?? ''),
                    'name' => $pack['displayName'] ?? 'Unknown',
                    'summary' => $pack['description'] ?? '',
                    'icon' => $pack['logo'] ?? null,
                    'author' => 'Voids Wrath',
                    'downloads' => 0,
                    'updated_at' => null,
                ];
            })->values();

            $total = $allItems->count();

            // Apply pagination
            $items = $allItems->slice($offset, $limit)->values()->toArray();

            return [
                'items' => $items,
                'total' => $total,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching VoidsWrath modpacks', ['error' => $e->getMessage()]);
            return ['items' => [], 'total' => 0];
        }
    }

    public function fetchVersions(string $modpackId): array
    {
        // VoidsWrath only has latest version
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

    public function fetchDetails(string $modpackId): ?array
    {
        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::JSON_URL);

            if (!$response->successful()) {
                return null;
            }

            $packs = $response->json();

            if (!is_array($packs)) {
                return null;
            }

            $pack = collect($packs)->firstWhere('id', (int) $modpackId);

            if (!$pack) {
                Log::warning('VoidsWrath modpack not found', ['id' => $modpackId]);
                return null;
            }

            return [
                'id' => (string) $pack['id'],
                'name' => $pack['displayName'] ?? 'Unknown',
                'summary' => $pack['description'] ?? '',
                'body' => $pack['description'] ?? '',
                'icon' => $pack['logo'] ?? null,
                'author' => 'Voids Wrath',
                'downloads' => 0,
                'followers' => 0,
                'published_at' => null,
                'updated_at' => null,
                'url' => $pack['platformUrl'] ?? 'https://www.voidswrath.com/',
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching VoidsWrath details', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function fetchDownloadInfo(string $modpackId, string $versionId): ?array
    {
        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::JSON_URL);

            if (!$response->successful()) {
                return null;
            }

            $packs = $response->json();

            if (!is_array($packs)) {
                return null;
            }

            $pack = collect($packs)->firstWhere('id', (int) $modpackId);

            if (!$pack || !isset($pack['serverPackUrl'])) {
                return null;
            }

            return [
                'url' => $pack['serverPackUrl'],
                'filename' => basename($pack['serverPackUrl']),
                'size' => 0,
                'hash' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching VoidsWrath download info', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
