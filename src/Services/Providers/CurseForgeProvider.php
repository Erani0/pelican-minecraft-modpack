<?php

namespace TimVida\MinecraftModpacks\Services\Providers;

use TimVida\MinecraftModpacks\Contracts\ModpackServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurseForgeProvider implements ModpackServiceInterface
{
    private const API_BASE = 'https://api.curseforge.com/v1';
    private const GAME_ID = 432; // Minecraft
    private const CLASS_ID = 4471; // Modpacks

    private function getHeaders(): array
    {
        $apiKey = config('modpacks.curseforge_api_key');

        if (empty($apiKey)) {
            Log::warning('CurseForge API key not configured - requests will fail');
        }

        return [
            'Accept' => 'application/json',
            'x-api-key' => $apiKey ?: '',
        ];
    }

    private function hasApiKey(): bool
    {
        $apiKey = config('modpacks.curseforge_api_key');
        return !empty($apiKey);
    }

    public function fetchModpacks(?string $query = null, int $limit = 20, int $offset = 0): array
    {
        if (!$this->hasApiKey()) {
            Log::error('CurseForge API key is required but not configured');
            return ['items' => [], 'total' => 0];
        }

        try {
            $params = [
                'gameId' => self::GAME_ID,
                'classId' => self::CLASS_ID,
                'index' => $offset,
                'pageSize' => min($limit, 50), // CurseForge max is 50
                'sortField' => 2, // Popularity
                'sortOrder' => 'desc',
            ];

            if ($query) {
                $params['searchFilter'] = $query;
            }

            $response = Http::withHeaders($this->getHeaders())
                ->timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . '/mods/search', $params);

            if (!$response->successful()) {
                Log::warning('CurseForge API request failed', ['status' => $response->status()]);
                return ['items' => [], 'total' => 0];
            }

            $data = $response->json();
            $items = collect($data['data'] ?? [])->map(function ($pack) {
                return [
                    'id' => (string) $pack['id'],
                    'name' => $pack['name'],
                    'summary' => $pack['summary'] ?? '',
                    'icon' => $pack['logo']['thumbnailUrl'] ?? null,
                    'author' => collect($pack['authors'] ?? [])->first()['name'] ?? 'Unknown',
                    'downloads' => $pack['downloadCount'] ?? 0,
                    'updated_at' => $pack['dateModified'] ?? null,
                ];
            })->toArray();

            $pagination = $data['pagination'] ?? [];
            $total = $pagination['totalCount'] ?? 0;

            return [
                'items' => $items,
                'total' => min($total, 10000), // CurseForge has a 10k limit
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching CurseForge modpacks', ['error' => $e->getMessage()]);
            return ['items' => [], 'total' => 0];
        }
    }

    public function fetchVersions(string $modpackId): array
    {
        if (!$this->hasApiKey()) {
            return [];
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . "/mods/{$modpackId}/files");

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $files = $data['data'] ?? [];

            return collect($files)->map(function ($file) {
                return [
                    'id' => (string) $file['id'],
                    'name' => $file['displayName'],
                    'version_number' => $file['fileName'],
                    'published_at' => $file['fileDate'] ?? null,
                    'downloads' => $file['downloadCount'] ?? 0,
                    'changelog' => '',
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Error fetching CurseForge versions', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function fetchDetails(string $modpackId): ?array
    {
        if (!$this->hasApiKey()) {
            return null;
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . "/mods/{$modpackId}");

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $pack = $data['data'] ?? null;

            if (!$pack) {
                return null;
            }

            return [
                'id' => (string) $pack['id'],
                'name' => $pack['name'],
                'summary' => $pack['summary'] ?? '',
                'body' => $pack['description'] ?? '',
                'icon' => $pack['logo']['thumbnailUrl'] ?? null,
                'author' => collect($pack['authors'] ?? [])->first()['name'] ?? 'Unknown',
                'downloads' => $pack['downloadCount'] ?? 0,
                'followers' => 0,
                'published_at' => $pack['dateCreated'] ?? null,
                'updated_at' => $pack['dateModified'] ?? null,
                'url' => $pack['links']['websiteUrl'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching CurseForge details', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function fetchDownloadInfo(string $modpackId, string $versionId): ?array
    {
        if (!$this->hasApiKey()) {
            return null;
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(config('modpacks.request_timeout', 10))
                ->get(self::API_BASE . "/mods/{$modpackId}/files/{$versionId}");

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $file = $data['data'] ?? null;

            if (!$file) {
                return null;
            }

            return [
                'url' => $file['downloadUrl'] ?? null,
                'filename' => $file['fileName'],
                'size' => $file['fileLength'] ?? 0,
                'hash' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching CurseForge download info', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
