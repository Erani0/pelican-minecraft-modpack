<?php

namespace TimVida\MinecraftModpacks\Services\Providers;

use TimVida\MinecraftModpacks\Contracts\ModpackServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FeedTheBeastProvider implements ModpackServiceInterface
{
    private const API_BASE = 'https://api.feed-the-beast.com/v1/modpacks/public/modpack';

    public function fetchModpacks(?string $query = null, int $limit = 20, int $offset = 0): array
    {
        try {
            $endpoint = empty($query)
                ? "/popular/installs/{$limit}"
                : "/search/{$limit}";

            $params = empty($query) ? [] : ['term' => $query];

            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . $endpoint, $params);

            if (!$response->successful()) {
                Log::warning('FTB API request failed', ['status' => $response->status()]);
                return ['items' => [], 'total' => 0];
            }

            $data = $response->json();
            $packIds = $data['packs'] ?? [];

            $items = [];
            foreach ($packIds as $packId) {
                if ($packId === 81) {
                    continue;
                }

                $details = $this->fetchDetails((string) $packId);
                if ($details) {
                    $items[] = [
                        'id' => $details['id'],
                        'name' => $details['name'],
                        'summary' => $details['summary'],
                        'icon' => $details['icon'],
                        'author' => 'Feed The Beast',
                        'downloads' => 0,
                        'updated_at' => null,
                    ];
                }
            }

            return [
                'items' => $items,
                'total' => count($items),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching FTB modpacks', ['error' => $e->getMessage()]);
            return ['items' => [], 'total' => 0];
        }
    }

    public function fetchVersions(string $modpackId): array
    {
        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . "/{$modpackId}");

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $versions = $data['versions'] ?? [];

            return collect($versions)->reverse()->map(function ($version) {
                return [
                    'id' => (string) $version['id'],
                    'name' => $version['name'],
                    'version_number' => $version['name'],
                    'published_at' => null,
                    'downloads' => 0,
                    'changelog' => '',
                ];
            })->values()->toArray();
        } catch (\Exception $e) {
            Log::error('Error fetching FTB versions', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function fetchDetails(string $modpackId): ?array
    {
        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . "/{$modpackId}");

            if (!$response->successful()) {
                return null;
            }

            $pack = $response->json();

            if (isset($pack['status']) && $pack['status'] === 'error') {
                return null;
            }

            $iconUrl = null;
            $artworks = $pack['art'] ?? [];
            foreach ($artworks as $art) {
                if ($art['type'] === 'square') {
                    $iconUrl = $art['url'];
                    break;
                }
            }

            return [
                'id' => (string) $pack['id'],
                'name' => $pack['name'],
                'summary' => $pack['description'] ?? '',
                'body' => $pack['description'] ?? '',
                'icon' => $iconUrl,
                'author' => 'Feed The Beast',
                'downloads' => 0,
                'followers' => 0,
                'published_at' => null,
                'updated_at' => null,
                'url' => "https://feed-the-beast.com/modpacks/{$pack['id']}",
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching FTB details', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function fetchDownloadInfo(string $modpackId, string $versionId): ?array
    {
        return [
            'url' => null,
            'filename' => "ftb-{$modpackId}-{$versionId}.zip",
            'size' => 0,
            'hash' => null,
            'requires_launcher' => true,
        ];
    }
}
