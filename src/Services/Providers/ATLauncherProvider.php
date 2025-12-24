<?php

namespace TimVida\MinecraftModpacks\Services\Providers;

use TimVida\MinecraftModpacks\Contracts\ModpackServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ATLauncherProvider implements ModpackServiceInterface
{
    private const API_BASE = 'https://api.atlauncher.com/v2';

    private function buildGraphQLQuery(string $query): array
    {
        return ['query' => $query];
    }

    public function fetchModpacks(?string $query = null, int $limit = 20, int $offset = 0): array
    {
        try {
            $graphQuery = empty($query)
                ? "query { packs(first: {$limit}) { id safeName name description websiteUrl } }"
                : "query { searchPacks(first: {$limit}, query: \"{$query}\") { id safeName name description websiteUrl } }";

            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->post(self::API_BASE . '/graphql', $this->buildGraphQLQuery($graphQuery));

            if (!$response->successful()) {
                Log::warning('ATLauncher API request failed', ['status' => $response->status()]);
                return ['items' => [], 'total' => 0];
            }

            $data = $response->json();
            $resultKey = empty($query) ? 'packs' : 'searchPacks';
            $packs = $data['data'][$resultKey] ?? [];

            $items = collect($packs)->map(function ($pack) {
                return [
                    'id' => (string) $pack['id'],
                    'name' => $pack['name'],
                    'summary' => $pack['description'] ?? '',
                    'icon' => 'https://cdn.atlcdn.net/images/packs/' . strtolower($pack['safeName']) . '.png',
                    'author' => 'ATLauncher',
                    'downloads' => 0,
                    'updated_at' => null,
                ];
            })->toArray();

            return [
                'items' => $items,
                'total' => count($items),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching ATLauncher modpacks', ['error' => $e->getMessage()]);
            return ['items' => [], 'total' => 0];
        }
    }

    public function fetchVersions(string $modpackId): array
    {
        try {
            $graphQuery = "query { pack(pack: { id: {$modpackId} }) { versions(first: 100) { version } } }";

            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->post(self::API_BASE . '/graphql', $this->buildGraphQLQuery($graphQuery));

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $versions = $data['data']['pack']['versions'] ?? [];

            return collect($versions)->map(function ($version) {
                return [
                    'id' => $version['version'],
                    'name' => $version['version'],
                    'version_number' => $version['version'],
                    'published_at' => null,
                    'downloads' => 0,
                    'changelog' => '',
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Error fetching ATLauncher versions', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function fetchDetails(string $modpackId): ?array
    {
        try {
            $graphQuery = "query { pack(pack: { id: {$modpackId} }) { name description safeName websiteUrl } }";

            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->post(self::API_BASE . '/graphql', $this->buildGraphQLQuery($graphQuery));

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $pack = $data['data']['pack'] ?? null;

            if (!$pack) {
                return null;
            }

            return [
                'id' => $modpackId,
                'name' => $pack['name'],
                'summary' => $pack['description'] ?? '',
                'body' => $pack['description'] ?? '',
                'icon' => 'https://cdn.atlcdn.net/images/packs/' . strtolower($pack['safeName']) . '.png',
                'author' => 'ATLauncher',
                'downloads' => 0,
                'followers' => 0,
                'published_at' => null,
                'updated_at' => null,
                'url' => $pack['websiteUrl'] ?? "https://atlauncher.com/pack/{$pack['safeName']}",
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching ATLauncher details', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function fetchDownloadInfo(string $modpackId, string $versionId): ?array
    {
        return [
            'url' => null,
            'filename' => "atlauncher-{$modpackId}-{$versionId}.zip",
            'size' => 0,
            'hash' => null,
            'requires_launcher' => true,
        ];
    }
}
