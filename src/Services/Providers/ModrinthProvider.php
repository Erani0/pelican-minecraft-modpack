<?php

namespace TimVida\MinecraftModpacks\Services\Providers;

use TimVida\MinecraftModpacks\Contracts\ModpackServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ModrinthProvider implements ModpackServiceInterface
{
    private const API_BASE = 'https://api.modrinth.com/v2';

    public function fetchModpacks(?string $query = null, int $limit = 20, int $offset = 0): array
    {
        try {
            $params = [
                'limit' => $limit,
                'offset' => $offset,
                'facets' => json_encode([['project_type:modpack'], ['server_side:required', 'server_side:optional']]),
            ];

            if ($query) {
                $params['query'] = $query;
            }

            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . '/search', $params);

            if (!$response->successful()) {
                Log::warning('Modrinth API request failed', ['status' => $response->status()]);
                return ['items' => [], 'total' => 0];
            }

            $data = $response->json();
            $items = collect($data['hits'] ?? [])->map(function ($pack) {
                return [
                    'id' => $pack['project_id'],
                    'name' => $pack['title'],
                    'summary' => $pack['description'] ?? '',
                    'icon' => $pack['icon_url'] ?? null,
                    'author' => $pack['author'] ?? 'Unknown',
                    'downloads' => $pack['downloads'] ?? 0,
                    'updated_at' => $pack['date_modified'] ?? null,
                ];
            })->toArray();

            return [
                'items' => $items,
                'total' => $data['total_hits'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching Modrinth modpacks', ['error' => $e->getMessage()]);
            return ['items' => [], 'total' => 0];
        }
    }

    public function fetchVersions(string $modpackId): array
    {
        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . "/project/{$modpackId}/version");

            if (!$response->successful()) {
                return [];
            }

            $versions = $response->json();

            return collect($versions)->map(function ($version) {
                return [
                    'id' => $version['id'],
                    'name' => $version['name'],
                    'version_number' => $version['version_number'],
                    'published_at' => $version['date_published'] ?? null,
                    'downloads' => $version['downloads'] ?? 0,
                    'changelog' => $version['changelog'] ?? '',
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Error fetching Modrinth versions', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function fetchDetails(string $modpackId): ?array
    {
        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . "/project/{$modpackId}");

            if (!$response->successful()) {
                return null;
            }

            $pack = $response->json();

            return [
                'id' => $pack['id'],
                'name' => $pack['title'],
                'summary' => $pack['description'] ?? '',
                'body' => $pack['body'] ?? '',
                'icon' => $pack['icon_url'] ?? null,
                'author' => $pack['team'] ?? 'Unknown',
                'downloads' => $pack['downloads'] ?? 0,
                'followers' => $pack['followers'] ?? 0,
                'published_at' => $pack['published'] ?? null,
                'updated_at' => $pack['updated'] ?? null,
                'url' => "https://modrinth.com/modpack/{$pack['slug']}",
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching Modrinth details', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function fetchDownloadInfo(string $modpackId, string $versionId): ?array
    {
        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . "/version/{$versionId}");

            if (!$response->successful()) {
                return null;
            }

            $version = $response->json();
            $files = $version['files'] ?? [];

            $primaryFile = collect($files)->firstWhere('primary', true) ?? $files[0] ?? null;

            if (!$primaryFile) {
                return null;
            }

            return [
                'url' => $primaryFile['url'],
                'filename' => $primaryFile['filename'],
                'size' => $primaryFile['size'] ?? 0,
                'hash' => $primaryFile['hashes']['sha1'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching Modrinth download info', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
