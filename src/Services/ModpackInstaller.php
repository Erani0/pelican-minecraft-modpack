<?php

namespace TimVida\MinecraftModpacks\Services;

use App\Models\Egg;
use App\Models\Server;
use App\Models\User;
use App\Repositories\Daemon\DaemonServerRepository;
use App\Services\Servers\ReinstallServerService;
use App\Services\Servers\StartupModificationService;
use App\Repositories\Daemon\DaemonFileRepository;
use TimVida\MinecraftModpacks\Enums\ModpackProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ModpackInstaller
{
    private const CURSEFORGE_API_BASE = 'https://api.curseforge.com/v1';
    private const INSTALLER_EGG_AUTHOR = 'minecraft-modpacks@timvida.dev';
    private const INSTALLER_EGG_NAME = 'Minecraft Modpack Installer';
    private const RUNTIME_EGG_NAME = 'Minecraft Modpack Runtime';
    private ?string $currentDownloadUrl = null;

    public function __construct(
        private ModpackManager $modpackManager,
        private DaemonFileRepository $fileRepository
    ) {
    }

    /**
     * Get daemon HTTP client for a server.
     *
     * @param Server $server
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private function getDaemonClient(Server $server)
    {
        $node = $server->node;
        $baseUrl = sprintf('%s://%s:%s', $node->scheme, $node->fqdn, $node->daemonListen);

        return Http::baseUrl($baseUrl)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $node->daemon_secret,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(30);
    }


    /**
     * Install a modpack on the server.
     *
     * @param Server $server
     * @param ModpackProvider $provider
     * @param string $modpackId
     * @param string $versionId
     * @param bool $deleteExisting Whether to delete existing server files before installation
     * @return bool
     */
    public function install(
        Server $server,
        ModpackProvider $provider,
        string $modpackId,
        string $versionId,
        bool $deleteExisting = false
    ): bool {
        try {
            $installerEgg = $this->findInstallerEgg();
            if ($installerEgg) {
                return $this->installUsingInstallerEgg(
                    $server,
                    $provider,
                    $modpackId,
                    $versionId,
                    $deleteExisting,
                    $installerEgg
                );
            }
            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log1'), [
                'server' => $server->id,
                'provider' => $provider->value,
            ]);

            $downloadInfo = $this->modpackManager->getDownloadInfo($provider, $modpackId, $versionId);
            $this->currentDownloadUrl = $downloadInfo['url'] ?? null;

            if (!$downloadInfo) {
                Log::error(trans('minecraft-modpacks::modpacks.installer.error.log1'), [
                    'provider' => $provider->value,
                    'modpackId' => $modpackId,
                    'versionId' => $versionId,
                ]);
                return false;
            }

            if (empty($downloadInfo['url'])) {
                Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log2'), [
                    'provider' => $provider->value,
                    'modpackId' => $modpackId,
                ]);
                return false;
            }

            $this->fileRepository->setServer($server);

            if ($deleteExisting) {
                $this->deleteServerFiles($server);
            }

            // Download the modpack archive
            $this->fileRepository->pull($downloadInfo['url'], '/');

            // Extract the filename from the URL
            $filename = $this->extractFilenameFromUrl($downloadInfo['url']);

            if ($filename && $this->isArchiveFile($filename)) {
            $this->processModpackArchive($server, $filename);
            }

            Log::info(trans('minecraft-modpacks::modpacks.installer.info.log1'), [
                'server' => $server->id,
                'provider' => $provider->value,
                'modpack' => $modpackId,
                'version' => $versionId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error(trans('minecraft-modpacks::modpacks.installer.error.log2'), [
                'error' => $e->getMessage(),
                'server' => $server->id,
                'provider' => $provider->value,
            ]);

            return false;
        } finally {
            $this->currentDownloadUrl = null;
        }
    }

    /**
     * Install a modpack by switching to the installer egg and triggering a reinstall.
     *
     * @param Server $server
     * @param ModpackProvider $provider
     * @param string $modpackId
     * @param string $versionId
     * @param bool $deleteExisting
     * @param Egg $installerEgg
     * @return bool
     */
    private function installUsingInstallerEgg(
        Server $server,
        ModpackProvider $provider,
        string $modpackId,
        string $versionId,
        bool $deleteExisting,
        Egg $installerEgg
    ): bool {
        try {
            $daemonServerRepository = app(DaemonServerRepository::class)->setServer($server);
            $daemonServerRepository->power('kill');
            $this->waitForServerOffline($daemonServerRepository);

            if ($deleteExisting) {
                $this->deleteServerFiles($server);
            } else {
                $this->clearModsDirectory($server);
            }

            $currentEgg = $server->egg;
            $runtimeEgg = $this->findRuntimeEgg();
            $targetEgg = $runtimeEgg ?? $currentEgg;
            $startupModificationService = app(StartupModificationService::class);
            $startupModificationService->setUserLevel(User::USER_LEVEL_ADMIN);

            $startupModificationService->handle($server, [
                'egg_id' => $installerEgg->id,
                'environment' => [
                    'MODPACK_PROVIDER' => $provider->value,
                    'MODPACK_ID' => $modpackId,
                    'MODPACK_VERSION_ID' => $versionId,
                ],
            ]);

            $reinstallServerService = app(ReinstallServerService::class);
            $reinstallServerService->handle($server);

            sleep(10); // Allow the daemon to start the installation process.

            $startupModificationService->handle($server, [
                'egg_id' => $targetEgg->id,
            ]);

            Log::info(trans('minecraft-modpacks::modpacks.installer.info.log2'), [
                'server' => $server->id,
                'provider' => $provider->value,
                'modpack' => $modpackId,
                'version' => $versionId,
                'installer_egg' => $installerEgg->id,
                'runtime_egg' => $targetEgg->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error(trans('minecraft-modpacks::modpacks.installer.error.log3'), [
                'error' => $e->getMessage(),
                'server' => $server->id,
                'provider' => $provider->value,
            ]);

            return false;
        }
    }

    /**
     * Find the installer egg used for modpack installations.
     *
     * @return Egg|null
     */
    private function findInstallerEgg(): ?Egg
    {
        return Egg::query()
            ->where('author', self::INSTALLER_EGG_AUTHOR)
            ->where('name', self::INSTALLER_EGG_NAME)
            ->first();
    }

    private function findRuntimeEgg(): ?Egg
    {
        return Egg::query()
            ->where('author', self::INSTALLER_EGG_AUTHOR)
            ->where('name', self::RUNTIME_EGG_NAME)
            ->first();
    }

    /**
     * Wait for a server to reach an offline state.
     *
     * @param DaemonServerRepository $daemonServerRepository
     * @param int $timeoutSeconds
     * @return void
     */
    private function waitForServerOffline(DaemonServerRepository $daemonServerRepository, int $timeoutSeconds = 60): void
    {
        $deadline = time() + $timeoutSeconds;

        while (time() < $deadline) {
            $details = $daemonServerRepository->getDetails();
            if (($details['state'] ?? null) === 'offline') {
                return;
            }

            sleep(1);
        }

        Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log3'));
    }

    /**
     * Delete all files from the server directory.
     *
     * @param Server $server
     * @return void
     */
    private function deleteServerFiles(Server $server): void
    {
        try {
            $files = $this->fileRepository->setServer($server)->getDirectory('/');

            if (isset($files['error'])) {
                Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log4'), ['error' => $files['error']]);
                return;
            }

            $fileNames = collect($files)
                ->pluck('name')
                ->filter(fn($name) => !in_array($name, ['.', '..']))
                ->toArray();

            if (!empty($fileNames)) {
                $this->fileRepository->deleteFiles('/', $fileNames);
                Log::info(trans('minecraft-modpacks::modpacks.installer.info.log3'), ['count' => count($fileNames)]);
            }
        } catch (\Exception $e) {
            Log::error(trans('minecraft-modpacks::modpacks.installer.error.log4'), ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check if a modpack can be installed (has direct download).
     *
     * @param ModpackProvider $provider
     * @param string $modpackId
     * @param string $versionId
     * @return bool
     */
    public function canInstall(ModpackProvider $provider, string $modpackId, string $versionId): bool
    {
        $downloadInfo = $this->modpackManager->getDownloadInfo($provider, $modpackId, $versionId);

        return $downloadInfo && !empty($downloadInfo['url']);
    }

    /**
     * Extract filename from URL.
     *
     * @param string $url
     * @return string|null
     */
    private function extractFilenameFromUrl(string $url): ?string
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';

        // Get the last segment of the path
        $filename = basename($path);

        // Remove query parameters if present
        $filename = explode('?', $filename)[0];

        // URL decode the filename
        $filename = urldecode($filename);

        return !empty($filename) ? $filename : null;
    }

    /**
     * Check if file is an archive (zip, tar, tar.gz, etc.).
     *
     * @param string $filename
     * @return bool
     */
    private function isArchiveFile(string $filename): bool
    {
        $archiveExtensions = ['zip', 'mrpack', 'tar', 'gz', 'rar', '7z', 'bz2', 'xz'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, $archiveExtensions);
    }

    /**
     * Process and install a modpack archive.
     *
     * @param Server $server
     * @param string $filename
     * @return void
     */
    private function processModpackArchive(Server $server, string $filename): void
    {
        try {
            Log::info(trans('minecraft-modpacks::modpacks.installer.info.log4'), [
                'filename' => $filename,
                'server' => $server->id,
            ]);

            // Step 1: Decompress the archive
            $archiveFilename = $this->prepareArchiveForDecompression($server, $filename);
            $this->fileRepository->decompressFile('/', $archiveFilename);
            sleep(3); // Wait for decompression to complete

            // Debug: List root directory to see what was extracted
            try {
                $rootFiles = $this->fileRepository->setServer($server)->getDirectory('/');
                $fileNames = collect($rootFiles)->pluck('name')->filter(fn($n) => !in_array($n, ['.', '..']))->toArray();
                Log::debug(trans('minecraft-modpacks::modpacks.installer.debug.log1'), ['files' => $fileNames]);
            } catch (\Exception $e) {
                Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log5'), ['error' => $e->getMessage()]);
            }

            $packDir = pathinfo($filename, PATHINFO_FILENAME);

            // Step 2: Check if this is a Modrinth pack (.mrpack)
            if (str_ends_with(strtolower($filename), '.mrpack')) {
                $this->processModrinthPack($server, $filename);
            } else {
                // For other formats (CurseForge, ATLauncher, etc.)
                $manifestOverridesPath = $this->processCurseForgeManifest($server, $filename);

                // Check if there's an overrides directory and copy it to root
                $overridesPath = $manifestOverridesPath ?: '/overrides';
                $items = $this->fileRepository->setServer($server)->getDirectory($overridesPath);

                if (!isset($items['error']) && !empty($items)) {
                    Log::info(trans('minecraft-modpacks::modpacks.installer.info.log5'), [
                        'path' => $overridesPath,
                    ]);
                    $this->copyOverridesToRoot($server, $overridesPath);
                    sleep(2); // Wait for file operations
                } else if (!$manifestOverridesPath) {
                    $overridesPath = "/{$packDir}/overrides";
                    $items = $this->fileRepository->setServer($server)->getDirectory($overridesPath);

                    if (!isset($items['error']) && !empty($items)) {
                        Log::info(trans('minecraft-modpacks::modpacks.installer.info.log6'), [
                            'path' => $overridesPath,
                        ]);
                        $this->copyOverridesToRoot($server, $overridesPath);
                        sleep(2); // Wait for file operations
                    }
                }

                // Clean up the extracted pack directory
                try {
                    Log::info(trans('minecraft-modpacks::modpacks.installer.info.log7'), ['directory' => $packDir]);
                    $this->fileRepository->deleteFiles('/', [$packDir]);
                    Log::debug(trans('minecraft-modpacks::modpacks.installer.debug.log2'), ['directory' => $packDir]);
                } catch (\Exception $e) {
                    Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log6'), [
                        'directory' => $packDir,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Step 3: Clean up the archive file
            $archiveFiles = array_unique([$filename, $archiveFilename]);
            $this->fileRepository->deleteFiles('/', $archiveFiles);

            Log::info(trans('minecraft-modpacks::modpacks.installer.info.log8'), [
                'filename' => $filename,
                'server' => $server->id,
            ]);
        } catch (\Exception $e) {
            Log::error(trans('minecraft-modpacks::modpacks.installer.error.log5'), [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'server' => $server->id,
            ]);
        }
    }

    /**
     * Ensure .mrpack archives can be decompressed by the daemon.
     *
     * @param Server $server
     * @param string $filename
     * @return string Filename to decompress
     */
    private function prepareArchiveForDecompression(Server $server, string $filename): string
    {
        if (!str_ends_with(strtolower($filename), '.mrpack')) {
            return $filename;
        }

        $zipFilename = preg_replace('/\.mrpack$/i', '.zip', $filename);
        if (!$zipFilename || $zipFilename === $filename) {
            return $filename;
        }

        $moved = $this->moveFileOrDirectory($server, '/' . $filename, '/' . $zipFilename);
        if ($moved) {
            Log::info(trans('minecraft-modpacks::modpacks.installer.info.log9'), [
                'from' => $filename,
                'to' => $zipFilename,
            ]);
            return $zipFilename;
        }

        Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log7'), [
            'filename' => $filename,
        ]);

        return $filename;
    }

    /**
     * Process a CurseForge manifest (manifest.json) by downloading mods.
     *
     * @param Server $server
     * @param string $filename
     * @return string|null Overrides path if present, otherwise null
     */
    private function processCurseForgeManifest(Server $server, string $filename): ?string
    {
        $packDir = pathinfo($filename, PATHINFO_FILENAME);
        $manifestPath = '/manifest.json';
        $manifestContent = $this->readFileFromDaemon($server, $manifestPath);
        $baseDir = '';

        if (!$manifestContent) {
            $manifestPath = "/{$packDir}/manifest.json";
            $manifestContent = $this->readFileFromDaemon($server, $manifestPath);
            $baseDir = "/{$packDir}";
        }

        if (!$manifestContent) {
            return null;
        }

        $manifest = json_decode($manifestContent, true);
        if (!is_array($manifest) || empty($manifest['files'])) {
            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log8'), [
                'path' => $manifestPath,
            ]);
            return null;
        }

        $this->clearModsDirectory($server);

        $apiKey = config('modpacks.curseforge_api_key');
        if (empty($apiKey)) {
            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log9'));
            return $this->buildOverridesPath($manifest, $baseDir);
        }

        $downloaded = 0;
        foreach ($manifest['files'] as $file) {
            $projectId = $file['projectID'] ?? null;
            $fileId = $file['fileID'] ?? null;
            $required = $file['required'] ?? true;

            if (!$required || !$projectId || !$fileId) {
                continue;
            }

            $downloadUrl = $this->getCurseForgeDownloadUrl($projectId, $fileId);
            if (!$downloadUrl) {
                Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log10'), [
                    'project_id' => $projectId,
                    'file_id' => $fileId,
                ]);
                continue;
            }

            try {
                $this->fileRepository->pull($downloadUrl, '/mods');
                $downloaded++;
            } catch (\Exception $e) {
                Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log11'), [
                    'project_id' => $projectId,
                    'file_id' => $fileId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info(trans('minecraft-modpacks::modpacks.installer.info.log10'), [
            'downloaded' => $downloaded,
            'manifest' => $manifestPath,
        ]);

        return $this->buildOverridesPath($manifest, $baseDir);
    }

    /**
     * Build overrides path from manifest data.
     *
     * @param array $manifest
     * @param string $baseDir
     * @return string|null
     */
    private function buildOverridesPath(array $manifest, string $baseDir): ?string
    {
        $overridesDir = $manifest['overrides'] ?? 'overrides';
        $overridesPath = rtrim($baseDir, '/') . '/' . ltrim($overridesDir, '/');

        return '/' . ltrim($overridesPath, '/');
    }

    /**
     * Get a CurseForge file download URL.
     *
     * @param int|string $projectId
     * @param int|string $fileId
     * @return string|null
     */
    private function getCurseForgeDownloadUrl(int|string $projectId, int|string $fileId): ?string
    {
        $apiKey = config('modpacks.curseforge_api_key');
        if (empty($apiKey)) {
            return null;
        }

        try {
            $headers = [
                'Accept' => 'application/json',
                'x-api-key' => $apiKey,
            ];

            $response = Http::withHeaders($headers)
                ->timeout(config('modpacks.request_timeout', 10))
                ->get(self::CURSEFORGE_API_BASE . "/mods/{$projectId}/files/{$fileId}/download-url");

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? null;
            }

            $fallback = Http::withHeaders($headers)
                ->timeout(config('modpacks.request_timeout', 10))
                ->get(self::CURSEFORGE_API_BASE . "/mods/{$projectId}/files/{$fileId}");

            if (!$fallback->successful()) {
                return null;
            }

            $data = $fallback->json();
            return $data['data']['downloadUrl'] ?? null;
        } catch (\Exception $e) {
            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log12'), [
                'project_id' => $projectId,
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Process a Modrinth pack (.mrpack) by downloading mods and copying overrides.
     *
     * @param Server $server
     * @param string $filename
     * @return void
     */
    private function processModrinthPack(Server $server, string $filename): void
    {
        try {
            Log::info(trans('minecraft-modpacks::modpacks.installer.info.log11'), [
                'filename' => $filename,
                'server' => $server->id,
            ]);

            // Check if modrinth.index.json is in root (extracted directly)
            $indexPathRoot = "/modrinth.index.json";
            $indexContent = $this->readFileFromDaemon($server, $indexPathRoot);
            $baseDir = "/";
            $packDir = null;

            if (!$indexContent) {
                // If not in root, check in extracted subdirectory
                $packDir = pathinfo($filename, PATHINFO_FILENAME);
                $indexPath = "/{$packDir}/modrinth.index.json";
                $indexContent = $this->readFileFromDaemon($server, $indexPath);
                $baseDir = "/{$packDir}";
                Log::debug(trans('minecraft-modpacks::modpacks.installer.debug.log3'), ['path' => $indexPath]);
            } else {
                Log::info(trans('minecraft-modpacks::modpacks.installer.info.log12'));
            }

            if (!$indexContent) {
                $indexContent = $this->readModrinthIndexFromArchive();
                if ($indexContent) {
                    Log::info(trans('minecraft-modpacks::modpacks.installer.info.log13'));
                }
            }

            if (!$indexContent) {
                Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log13'));
            } else {
                $index = json_decode($indexContent, true);

                if ($index && isset($index['files'])) {
                    $this->installModrinthServerLoader($server, $index);
                    $this->clearModsDirectory($server);

                    Log::info(trans('minecraft-modpacks::modpacks.installer.info.log14'), [
                        'file_count' => count($index['files']),
                        'base_directory' => $baseDir,
                    ]);

                    // Download all mod files
                    foreach ($index['files'] as $file) {
                        if (!isset($file['downloads'][0]) || !isset($file['path'])) {
                            continue;
                        }

                        $downloadUrl = $file['downloads'][0];
                        $targetPath = '/' . $file['path'];

                        try {
                            $this->fileRepository->pull($downloadUrl, dirname($targetPath));
                            Log::debug(trans('minecraft-modpacks::modpacks.installer.debug.log4'), ['path' => $file['path']]);
                        } catch (\Exception $e) {
                            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log14'), [
                                'path' => $file['path'],
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }

            // Copy overrides to server root
            // Check both possible locations
            $overridesPath = "/overrides";
            $items = $this->fileRepository->setServer($server)->getDirectory($overridesPath);

            if (isset($items['error']) || empty($items)) {
                // Try subdirectory
                $packDir = pathinfo($filename, PATHINFO_FILENAME);
                $overridesPath = "/{$packDir}/overrides";
            }

            $this->copyOverridesToRoot($server, $overridesPath);
            sleep(2); // Wait for file operations to complete

            // Clean up: Delete modrinth.index.json if it's in root
            try {
                if ($baseDir === '/') {
                    $this->fileRepository->deleteFiles('/', ['modrinth.index.json']);
                    Log::debug(trans('minecraft-modpacks::modpacks.installer.debug.log5'));
                }
            } catch (\Exception $e) {
                Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log15'), ['error' => $e->getMessage()]);
            }

            // Clean up the extracted pack directory if it exists
            if (isset($packDir) && $packDir) {
                try {
                    Log::info(trans('minecraft-modpacks::modpacks.installer.info.log15'), ['directory' => $packDir]);
                    $this->fileRepository->deleteFiles('/', [$packDir]);
                    Log::debug(trans('minecraft-modpacks::modpacks.installer.debug.log6'), ['directory' => $packDir]);
                } catch (\Exception $e) {
                    Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log16'), [
                        'directory' => $packDir,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info(trans('minecraft-modpacks::modpacks.installer.info.log16'), [
                'server' => $server->id,
            ]);
        } catch (\Exception $e) {
            Log::error(trans('minecraft-modpacks::modpacks.installer.error.log6'), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'server' => $server->id,
            ]);
        }
    }

    /**
     * Read a file from the daemon server.
     *
     * @param Server $server
     * @param string $path
     * @return string|null
     */
    private function readFileFromDaemon(Server $server, string $path): ?string
    {
        try {
            $repository = $this->fileRepository->setServer($server);
            $content = $this->readFileFromRepository($repository, $path);

            if ($content !== null) {
                return $content;
            }

            $response = $this->getDaemonClient($server)
                ->get("/api/servers/{$server->uuid}/files/contents", [
                    'file' => $path,
                ]);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log17'), [
                'path' => $path,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error(trans('minecraft-modpacks::modpacks.installer.error.log7'), [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Read modrinth.index.json by downloading the archive and extracting locally.
     *
     * @return string|null
     */
    private function readModrinthIndexFromArchive(): ?string
    {
        if (empty($this->currentDownloadUrl)) {
            return null;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'mrpack_');
        if (!$tmpFile) {
            return null;
        }

        try {
            $response = Http::timeout(config('modpacks.request_timeout', 10))
                ->withOptions(['sink' => $tmpFile, 'stream' => true])
                ->get($this->currentDownloadUrl);

            if (!$response->successful()) {
                Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log18'), [
                    'status' => $response->status(),
                ]);
                return null;
            }

            $zip = new \ZipArchive();
            if ($zip->open($tmpFile) !== true) {
                Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log19'));
                return null;
            }

            $indexContent = $zip->getFromName('modrinth.index.json');
            $zip->close();

            return is_string($indexContent) ? $indexContent : null;
        } catch (\Exception $e) {
            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log20'), [
                'error' => $e->getMessage(),
            ]);
            return null;
        } finally {
            @unlink($tmpFile);
        }
    }

    /**
     * Install a server loader for a Modrinth pack based on its dependencies.
     *
     * @param Server $server
     * @param array $index
     * @return void
     */
    private function installModrinthServerLoader(Server $server, array $index): void
    {
        $loader = $this->resolveModrinthLoader($index);
        if (!$loader) {
            return;
        }

        if (in_array($loader['type'], ['forge', 'neoforge'], true)) {
            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log21'), $loader);
            return;
        }

        try {
            $tempDir = '/.modpack-loader';
            $repository = $this->fileRepository->setServer($server);

            try {
                $repository->createDirectory($tempDir);
            } catch (\Throwable $e) {
                Log::debug(trans('minecraft-modpacks::modpacks.installer.debug.log7'), ['error' => $e->getMessage()]);
            }

            $repository->pull($loader['url'], $tempDir);
            $serverJar = $this->findJarInDirectory($server, $tempDir);

            if (!$serverJar) {
                Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log22'), $loader);
                return;
            }

            $repository->deleteFiles('/', ['server.jar']);
            $this->moveFileOrDirectory($server, $tempDir . '/' . $serverJar, '/server.jar');
            $repository->deleteFiles('/', [ltrim($tempDir, '/')]);
            $this->writeRunScripts($server, 'server.jar');

            Log::info(trans('minecraft-modpacks::modpacks.installer.info.log17'), [
                'type' => $loader['type'],
                'server_jar' => $serverJar,
            ]);
        } catch (\Exception $e) {
            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log23'), [
                'error' => $e->getMessage(),
                'type' => $loader['type'],
            ]);
        }
    }

    /**
     * Resolve Modrinth loader info from index dependencies.
     *
     * @param array $index
     * @return array|null
     */
    private function resolveModrinthLoader(array $index): ?array
    {
        $deps = $index['dependencies'] ?? [];
        $minecraft = $deps['minecraft'] ?? null;

        if (!$minecraft) {
            return null;
        }

        if (!empty($deps['fabric-loader'])) {
            $loader = $deps['fabric-loader'];
            return [
                'type' => 'fabric',
                'minecraft' => $minecraft,
                'loader' => $loader,
                'prefix' => "fabric-server-{$minecraft}-{$loader}",
                'url' => "https://meta.fabricmc.net/v2/versions/loader/{$minecraft}/{$loader}/server/jar",
            ];
        }

        if (!empty($deps['quilt-loader'])) {
            $loader = $deps['quilt-loader'];
            return [
                'type' => 'quilt',
                'minecraft' => $minecraft,
                'loader' => $loader,
                'prefix' => "quilt-server-{$minecraft}-{$loader}",
                'url' => "https://meta.quiltmc.org/v3/versions/loader/{$minecraft}/{$loader}/server/jar",
            ];
        }

        if (!empty($deps['neoforge'])) {
            return [
                'type' => 'neoforge',
                'minecraft' => $minecraft,
                'loader' => $deps['neoforge'],
            ];
        }

        if (!empty($deps['forge'])) {
            return [
                'type' => 'forge',
                'minecraft' => $minecraft,
                'loader' => $deps['forge'],
            ];
        }

        return null;
    }

    /**
     * Find a server jar in root that matches a prefix.
     *
     * @param Server $server
     * @param string $prefix
     * @return string|null
     */
    private function findServerJarByPrefix(Server $server, string $prefix): ?string
    {
        try {
            $items = $this->fileRepository->setServer($server)->getDirectory('/');
            foreach ($items as $item) {
                if (empty($item['name']) || empty($item['file'])) {
                    continue;
                }

                $name = $item['name'];
                if (str_starts_with($name, $prefix) && str_ends_with($name, '.jar')) {
                    return $name;
                }
            }
        } catch (\Exception $e) {
            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log24'), [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * List jar files in server root.
     *
     * @param Server $server
     * @return string[]
     */
    private function listRootJars(Server $server): array
    {
        try {
            $items = $this->fileRepository->setServer($server)->getDirectory('/');
            return collect($items)
                ->filter(fn($item) => !empty($item['file']) && !empty($item['name']) && str_ends_with($item['name'], '.jar'))
                ->pluck('name')
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log25'), ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Find a newly downloaded server jar by diffing root contents.
     *
     * @param Server $server
     * @param array $existingJars
     * @param string $prefix
     * @return string|null
     */
    private function findNewServerJar(Server $server, array $existingJars, string $prefix): ?string
    {
        $currentJars = $this->listRootJars($server);
        $newJars = array_values(array_diff($currentJars, $existingJars));

        foreach ($newJars as $jar) {
            if (str_starts_with($jar, $prefix)) {
                return $jar;
            }
        }

        if (count($newJars) === 1) {
            return $newJars[0];
        }

        foreach ($newJars as $jar) {
            if (!str_contains($jar, 'forge') && !str_contains($jar, 'neoforge')) {
                return $jar;
            }
        }

        Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log26'), [
            'new_jars' => $newJars,
        ]);

        return null;
    }

    /**
     * Find a jar file inside a directory.
     *
     * @param Server $server
     * @param string $directory
     * @return string|null
     */
    private function findJarInDirectory(Server $server, string $directory): ?string
    {
        try {
            $items = $this->fileRepository->setServer($server)->getDirectory($directory);
            foreach ($items as $item) {
                if (empty($item['name']) || empty($item['file'])) {
                    continue;
                }

                if (str_ends_with($item['name'], '.jar')) {
                    return $item['name'];
                }
            }
        } catch (\Exception $e) {
            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log27'), [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Clear the server mods directory before installing a new modpack.
     *
     * @param Server $server
     * @return void
     */
    private function clearModsDirectory(Server $server): void
    {
        try {
            $items = $this->fileRepository->setServer($server)->getDirectory('/mods');
            $fileNames = collect($items)
                ->pluck('name')
                ->filter(fn($name) => !in_array($name, ['.', '..']))
                ->toArray();

            if (!empty($fileNames)) {
                $this->fileRepository->deleteFiles('/mods', $fileNames);
                Log::info(trans('minecraft-modpacks::modpacks.installer.info.log18'), ['count' => count($fileNames)]);
            }
        } catch (\Exception $e) {
            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log28'), ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update run scripts to use a specific jar.
     *
     * @param Server $server
     * @param string $jarName
     * @return void
     */
    private function writeRunScripts(Server $server, string $jarName): void
    {
        $sh = <<<SH
#!/usr/bin/env sh

java -jar {$jarName} --onlyCheckJava || exit 1
java @user_jvm_args.txt -jar {$jarName} "$@"
SH;

        $bat = <<<BAT
@echo off

java -jar {$jarName} --onlyCheckJava
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo If you're struggling to fix the error above, ask for help on the forums or Discord mentioned in the readme.
    goto :exit
)

java @user_jvm_args.txt -jar {$jarName} %*

:exit
pause
BAT;

        $this->writeFileToRepository($server, '/run.sh', $sh);
        $this->writeFileToRepository($server, '/run.bat', $bat);
    }

    /**
     * Write file content using the daemon repository with flexible signatures.
     *
     * @param Server $server
     * @param string $path
     * @param string $content
     * @return void
     */
    private function writeFileToRepository(Server $server, string $path, string $content): void
    {
        $repository = $this->fileRepository->setServer($server);
        $cleanPath = ltrim($path, '/');

        if (!method_exists($repository, 'putContent')) {
            return;
        }

        $attempts = [
            [$path, $content],
            [$cleanPath, $content],
            ['/', $cleanPath, $content],
        ];

        foreach ($attempts as $args) {
            try {
                $repository->putContent(...$args);
                return;
            } catch (\Throwable $e) {
                Log::debug(trans('minecraft-modpacks::modpacks.installer.debug.log8'), [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Attempt to read a file using the daemon repository methods.
     *
     * @param DaemonFileRepository $repository
     * @param string $path
     * @return string|null
     */
    private function readFileFromRepository(DaemonFileRepository $repository, string $path): ?string
    {
        $methods = [
            'getContent',
            'getFileContents',
            'getFileContent',
            'getContents',
            'getFile',
        ];

        foreach ($methods as $method) {
            if (!method_exists($repository, $method)) {
                continue;
            }

            try {
                $result = $this->invokeRepositoryRead($repository, $method, $path);
                if (is_string($result)) {
                    return $result;
                }

                if (is_array($result)) {
                    $content = $result['content'] ?? $result['data'] ?? null;
                    if (is_string($content)) {
                        return $content;
                    }
                }
            } catch (\Throwable $e) {
                Log::debug(trans('minecraft-modpacks::modpacks.installer.debug.log9'), [
                    'method' => $method,
                    'path' => $path,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);
            }
        }

        Log::debug(trans('minecraft-modpacks::modpacks.installer.debug.log10'), [
            'path' => $path,
            'methods' => get_class_methods($repository),
        ]);

        return null;
    }

    /**
     * Invoke a repository read method with flexible signatures.
     *
     * @param DaemonFileRepository $repository
     * @param string $method
     * @param string $path
     * @return mixed
     */
    private function invokeRepositoryRead(DaemonFileRepository $repository, string $method, string $path): mixed
    {
        $cleanPath = ltrim($path, '/');

        if ($method === 'getContent') {
            $attempts = [
                [$cleanPath, null],
                [$path, null],
                [$cleanPath],
                [$path],
            ];

            $lastError = null;
            foreach ($attempts as $args) {
                try {
                    $result = $repository->{$method}(...$args);
                    if ($result !== null) {
                        return $result;
                    }
                } catch (\Throwable $e) {
                    $lastError = $e;
                }
            }

            if ($lastError) {
                throw $lastError;
            }

            return null;
        }

        try {
            $reflection = new \ReflectionMethod($repository, $method);
            $paramCount = $reflection->getNumberOfParameters();
        } catch (\Throwable) {
            $paramCount = 1;
        }

        if ($paramCount >= 2) {
            if ($method === 'getContent') {
                $result = $repository->{$method}($path, null);
                if ($result !== null) {
                    return $result;
                }

                return $repository->{$method}($cleanPath, null);
            }

            return $repository->{$method}('/', $cleanPath);
        }

        $result = $repository->{$method}($path);
        if ($result !== null) {
            return $result;
        }

        return $repository->{$method}($cleanPath);
    }

    /**
     * Copy/Move files or directories using the daemon API.
     *
     * @param Server $server
     * @param string $from Source path
     * @param string $to Destination path
     * @return bool
     */
    private function moveFileOrDirectory(Server $server, string $from, string $to): bool
    {
        try {
            $repository = $this->fileRepository->setServer($server);
            if ($this->renameWithRepository($repository, $from, $to)) {
                return true;
            }

            $response = $this->getDaemonClient($server)
                ->put("/api/servers/{$server->uuid}/files/rename", [
                    'root' => '/',
                    'files' => [
                        [
                            'from' => $from,
                            'to' => $to,
                        ],
                    ],
                ]);

            if ($response->successful()) {
                Log::debug(trans('minecraft-modpacks::modpacks.installer.debug.log11'), [
                    'from' => $from,
                    'to' => $to,
                ]);
                return true;
            }

            Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log29'), [
                'from' => $from,
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error(trans('minecraft-modpacks::modpacks.installer.error.log8'), [
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Attempt to rename a file or directory using repository methods.
     *
     * @param DaemonFileRepository $repository
     * @param string $from
     * @param string $to
     * @return bool
     */
    private function renameWithRepository(DaemonFileRepository $repository, string $from, string $to): bool
    {
        $attempts = [
            function () use ($repository, $from, $to) {
                if (method_exists($repository, 'renameFiles')) {
                    $repository->renameFiles('/', [['from' => ltrim($from, '/'), 'to' => ltrim($to, '/')]]);
                    return true;
                }
                return false;
            },
            function () use ($repository, $from, $to) {
                if (method_exists($repository, 'renameFiles')) {
                    $repository->renameFiles([['from' => ltrim($from, '/'), 'to' => ltrim($to, '/')]]);
                    return true;
                }
                return false;
            },
            function () use ($repository, $from, $to) {
                if (method_exists($repository, 'renameFile')) {
                    $repository->renameFile($from, $to);
                    return true;
                }
                return false;
            },
            function () use ($repository, $from, $to) {
                if (method_exists($repository, 'moveFile')) {
                    $repository->moveFile($from, $to);
                    return true;
                }
                return false;
            },
            function () use ($repository, $from, $to) {
                if (method_exists($repository, 'moveFiles')) {
                    $repository->moveFiles([['from' => ltrim($from, '/'), 'to' => ltrim($to, '/')]]);
                    return true;
                }
                return false;
            },
        ];

        foreach ($attempts as $attempt) {
            try {
                if ($attempt()) {
                    Log::debug(trans('minecraft-modpacks::modpacks.installer.debug.log12'), [
                        'from' => $from,
                        'to' => $to,
                    ]);
                    return true;
                }
            } catch (\Throwable $e) {
                Log::debug(trans('minecraft-modpacks::modpacks.installer.debug.log13'), [
                    'from' => $from,
                    'to' => $to,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return false;
    }

    /**
     * Recursively copy overrides from a directory to server root.
     *
     * @param Server $server
     * @param string $overridesPath Path to the overrides directory
     * @return void
     */
    private function copyOverridesToRoot(Server $server, string $overridesPath): void
    {
        try {
            Log::info(trans('minecraft-modpacks::modpacks.installer.info.log19'), ['path' => $overridesPath]);

            // List all files/folders in the overrides directory
            $items = $this->fileRepository->setServer($server)->getDirectory($overridesPath);

            if (isset($items['error']) || empty($items)) {
                Log::warning(trans('minecraft-modpacks::modpacks.installer.warning.log30'), [
                    'path' => $overridesPath,
                ]);
                return;
            }

            foreach ($items as $item) {
                if (!isset($item['name']) || in_array($item['name'], ['.', '..'])) {
                    continue;
                }

                $sourcePath = rtrim($overridesPath, '/') . '/' . $item['name'];
                $targetPath = '/' . $item['name'];

                // Move each item from overrides to root
                $this->moveFileOrDirectory($server, $sourcePath, $targetPath);
            }

            Log::info(trans('minecraft-modpacks::modpacks.installer.info.log20'));
        } catch (\Exception $e) {
            Log::error(trans('minecraft-modpacks::modpacks.installer.error.log9'), [
                'path' => $overridesPath,
                'error' => $e->getMessage(),
            ]);
        }
    }

}