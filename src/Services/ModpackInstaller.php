<?php

namespace TimVida\MinecraftModpacks\Services;

use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use TimVida\MinecraftModpacks\Enums\ModpackProvider;
use Illuminate\Support\Facades\Log;

class ModpackInstaller
{
    public function __construct(
        private ModpackManager $modpackManager,
        private DaemonFileRepository $fileRepository
    ) {
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
            $downloadInfo = $this->modpackManager->getDownloadInfo($provider, $modpackId, $versionId);

            if (!$downloadInfo) {
                Log::error('Failed to get download info for modpack', [
                    'provider' => $provider->value,
                    'modpackId' => $modpackId,
                    'versionId' => $versionId,
                ]);
                return false;
            }

            if (empty($downloadInfo['url'])) {
                Log::warning('Modpack requires launcher installation', [
                    'provider' => $provider->value,
                    'modpackId' => $modpackId,
                ]);
                return false;
            }

            $this->fileRepository->setServer($server);

            if ($deleteExisting) {
                $this->deleteServerFiles($server);
            }

            $this->fileRepository->pull($downloadInfo['url'], '/');

            Log::info('Modpack installation initiated', [
                'server' => $server->id,
                'provider' => $provider->value,
                'modpack' => $modpackId,
                'version' => $versionId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to install modpack', [
                'error' => $e->getMessage(),
                'server' => $server->id,
                'provider' => $provider->value,
            ]);

            return false;
        }
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
                Log::warning('Could not list server files for deletion', ['error' => $files['error']]);
                return;
            }

            $fileNames = collect($files)
                ->pluck('name')
                ->filter(fn($name) => !in_array($name, ['.', '..']))
                ->toArray();

            if (!empty($fileNames)) {
                $this->fileRepository->deleteFiles('/', $fileNames);
                Log::info('Deleted server files before modpack installation', ['count' => count($fileNames)]);
            }
        } catch (\Exception $e) {
            Log::error('Error deleting server files', ['error' => $e->getMessage()]);
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
}
