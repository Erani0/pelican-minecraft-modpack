<?php

namespace TimVida\MinecraftModpacks\Services;

use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use Illuminate\Support\Facades\Log;

class ModpackTracker
{
    private const TRACKING_FILE = '.installed_modpack.json';

    public function __construct(
        private DaemonFileRepository $fileRepository
    ) {}

    /**
     * Get the currently installed modpack information.
     *
     * @param Server $server
     * @return array|null
     */
    public function getInstalledModpack(Server $server): ?array
    {
        try {
            $this->fileRepository->setServer($server);
            $content = $this->readTrackingFile($server);

            if (!$content) {
                return null;
            }

            $data = json_decode($content, true);

            if (!is_array($data) || !$this->validateTrackingData($data)) {
                Log::warning(trans('minecraft-modpacks::modpacks.tracking.warning.invalid_data'), ['server' => $server->id]);
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            Log::warning(trans('minecraft-modpacks::modpacks.tracking.warning.read_failed'), [
                'server' => $server->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Save installed modpack information.
     *
     * @param Server $server
     * @param string $provider
     * @param string $modpackId
     * @param string $modpackName
     * @param string $versionId
     * @param string $versionName
     * @return bool
     */
    public function saveInstalledModpack(
        Server $server,
        string $provider,
        string $modpackId,
        string $modpackName,
        string $versionId,
        string $versionName
    ): bool {
        try {
            $data = [
                'provider' => $provider,
                'modpack_id' => $modpackId,
                'modpack_name' => $modpackName,
                'version_id' => $versionId,
                'version_name' => $versionName,
                'installed_at' => now()->toIso8601String(),
            ];

            return $this->writeTrackingFile($server, $data);
        } catch (\Exception $e) {
            Log::error(trans('minecraft-modpacks::modpacks.tracking.error.save_failed'), [
                'server' => $server->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if an update is available.
     *
     * @param Server $server
     * @param string $latestVersionId
     * @return bool
     */
    public function hasUpdate(Server $server, string $latestVersionId): bool
    {
        $installed = $this->getInstalledModpack($server);

        if (!$installed) {
            return false;
        }

        return $installed['version_id'] !== $latestVersionId;
    }

    /**
     * Clear tracking data.
     *
     * @param Server $server
     * @return bool
     */
    public function clearTracking(Server $server): bool
    {
        try {
            $this->fileRepository->setServer($server);
            $this->fileRepository->deleteFiles('/', [self::TRACKING_FILE]);

            Log::info(trans('minecraft-modpacks::modpacks.tracking.info.cleared'), ['server' => $server->id]);
            return true;
        } catch (\Exception $e) {
            Log::warning(trans('minecraft-modpacks::modpacks.tracking.warning.clear_failed'), [
                'server' => $server->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Read the tracking file from the server.
     *
     * @param Server $server
     * @return string|null
     */
    private function readTrackingFile(Server $server): ?string
    {
        $repository = $this->fileRepository->setServer($server);
        $path = '/' . self::TRACKING_FILE;

        $methods = ['getContent', 'getFileContents', 'getFileContent', 'getContents'];

        foreach ($methods as $method) {
            if (!method_exists($repository, $method)) {
                continue;
            }

            try {
                $result = $repository->{$method}(ltrim($path, '/'), null);

                if (is_string($result)) {
                    return $result;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Write the tracking file to the server.
     *
     * @param Server $server
     * @param array $data
     * @return bool
     */
    private function writeTrackingFile(Server $server, array $data): bool
    {
        $repository = $this->fileRepository->setServer($server);
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (!method_exists($repository, 'putContent')) {
            Log::warning(trans('minecraft-modpacks::modpacks.tracking.warning.no_put_method'));
            return false;
        }

        $attempts = [
            [self::TRACKING_FILE, $content],
            ['/', self::TRACKING_FILE, $content],
        ];

        foreach ($attempts as $args) {
            try {
                $repository->putContent(...$args);
                Log::info(trans('minecraft-modpacks::modpacks.tracking.info.saved'), ['server' => $server->id]);
                return true;
            } catch (\Throwable $e) {
                continue;
            }
        }

        return false;
    }

    /**
     * Validate tracking data structure.
     *
     * @param array $data
     * @return bool
     */
    private function validateTrackingData(array $data): bool
    {
        $required = ['provider', 'modpack_id', 'modpack_name', 'version_id', 'version_name', 'installed_at'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        return true;
    }
}