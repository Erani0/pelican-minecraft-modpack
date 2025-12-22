<?php

namespace TimVida\MinecraftModpacks\Contracts;

interface ModpackServiceInterface
{
    /**
     * Fetch modpacks from the provider with optional search and pagination.
     *
     * @param string|null $query Search query
     * @param int $limit Number of results per page
     * @param int $offset Pagination offset
     * @return array{items: array, total: int}
     */
    public function fetchModpacks(?string $query = null, int $limit = 20, int $offset = 0): array;

    /**
     * Retrieve available versions for a specific modpack.
     *
     * @param string $modpackId The modpack identifier
     * @return array List of versions
     */
    public function fetchVersions(string $modpackId): array;

    /**
     * Get detailed information about a specific modpack.
     *
     * @param string $modpackId The modpack identifier
     * @return array|null Modpack details or null if not found
     */
    public function fetchDetails(string $modpackId): ?array;

    /**
     * Get download information for a specific version.
     *
     * @param string $modpackId The modpack identifier
     * @param string $versionId The version identifier
     * @return array|null Download information or null if not found
     */
    public function fetchDownloadInfo(string $modpackId, string $versionId): ?array;
}
