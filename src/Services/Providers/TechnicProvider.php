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
            if (empty($query)) {
                return ['items' => [], 'total' => 0];
            }

            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . '/search', ['q' => $query]);

            if (!$response->successful()) {
                Log::warning('Technic API request failed', ['status' => $response->status()]);
                return ['items' => [], 'total' => 0];
            }

            $data = $response->json();
            $packs = $data['modpacks'] ?? [];

            $items = collect($packs)->take($limit)->map(function ($slug) {
                $details = $this->fetchDetails($slug);
                if (!$details) {
                    return null;
                }

                return [
                    'id' => $slug,
                    'name' => $details['name'],
                    'summary' => $details['summary'],
                    'icon' => $details['icon'],
                    'author' => $details['author'],
                    'downloads' => $details['downloads'],
                    'updated_at' => null,
                ];
            })->filter()->values()->toArray();

            return [
                'items' => $items,
                'total' => count($items),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching Technic modpacks', ['error' => $e->getMessage()]);
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

    public function fetchDetails(string $modpackId): ?array
    {
        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . '/modpack/' . urlencode($modpackId));

            if (!$response->successful()) {
                return null;
            }

            $pack = $response->json();

            if (!isset($pack['name'])) {
                return null;
            }

            return [
                'id' => $modpackId,
                'name' => $pack['name'],
                'summary' => $pack['description'] ?? '',
                'body' => $pack['description'] ?? '',
                'icon' => $pack['icon']['url'] ?? null,
                'author' => $pack['user'] ?? 'Unknown',
                'downloads' => $pack['runs'] ?? 0,
                'followers' => 0,
                'published_at' => null,
                'updated_at' => null,
                'url' => $pack['platformUrl'] ?? "https://www.technicpack.net/modpack/{$modpackId}",
                'version' => $pack['version'] ?? 'latest',
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching Technic details', ['error' => $e->getMessage()]);
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
