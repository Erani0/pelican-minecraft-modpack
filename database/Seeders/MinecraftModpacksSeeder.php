<?php

namespace Database\Seeders;

use App\Models\Egg;
use App\Models\EggVariable;
use Illuminate\Database\Seeder;

class MinecraftModpacksSeeder extends Seeder
{
    private const AUTHOR = 'minecraft-modpacks@timvida.dev';
    private const INSTALLER_NAME = 'Minecraft Modpack Installer';
    private const RUNTIME_NAME = 'Minecraft Modpack Runtime';

    public function run(): void
    {
        $installerEgg = $this->seedInstallerEgg();
        $this->seedRuntimeEgg();
        $this->syncVariables($installerEgg->id);
    }

    private function seedInstallerEgg(): Egg
    {
        $egg = Egg::query()->firstOrNew([
            'author' => self::AUTHOR,
            'name' => self::INSTALLER_NAME,
        ]);

        $egg->description = 'Installer egg that provisions a server-ready modpack.';
        $egg->docker_images = [
            'Java 21' => 'ghcr.io/pelican-eggs/yolks:java_21',
        ];
        $egg->startup_commands = [
            'Default' => 'java -Xms128M -XX:MaxRAMPercentage=95.0 -jar server.jar',
        ];
        $egg->config_files = json_encode([
            'server.properties' => [
                'parser' => 'properties',
                'find' => [
                    'server-ip' => '',
                    'server-port' => '{{server.allocations.default.port}}',
                    'query.port' => '{{server.allocations.default.port}}',
                ],
            ],
        ]);
        $egg->config_startup = json_encode([
            'done' => ')! For help, type ',
        ]);
        $egg->config_logs = json_encode((object) []);
        $egg->config_stop = 'stop';
        $egg->script_is_privileged = false;
        $egg->script_entry = 'ash';
        $egg->script_container = 'git.ric-rac.org/ric-rac/minecraft_modpack_server_installer:latest';
        $egg->script_install = $this->getInstallScript();
        $egg->tags = ['minecraft', 'modpack', 'installer'];

        $egg->save();

        return $egg;
    }

    private function seedRuntimeEgg(): void
    {
        $egg = Egg::query()->firstOrNew([
            'author' => self::AUTHOR,
            'name' => self::RUNTIME_NAME,
        ]);

        $egg->description = 'Runtime egg that starts the installed modpack using server.jar or unix_args.txt.';
        $egg->docker_images = [
            'Java 21' => 'ghcr.io/pelican-eggs/yolks:java_21',
        ];
        $egg->startup_commands = [
            'Default' => "sh -lc 'if [ -f unix_args.txt ]; then exec java @unix_args.txt \"\$@\"; else exec java -jar server.jar \"\$@\"; fi'",
        ];
        $egg->config_files = json_encode([
            'server.properties' => [
                'parser' => 'properties',
                'find' => [
                    'server-ip' => '',
                    'server-port' => '{{server.allocations.default.port}}',
                    'query.port' => '{{server.allocations.default.port}}',
                ],
            ],
        ]);
        $egg->config_startup = json_encode([
            'done' => ')! For help, type ',
        ]);
        $egg->config_logs = json_encode((object) []);
        $egg->config_stop = 'stop';
        $egg->script_is_privileged = false;
        $egg->script_entry = 'ash';
        $egg->script_container = 'ghcr.io/pelican-eggs/installers:alpine';
        $egg->script_install = $this->getRuntimeInstallScript();
        $egg->tags = ['minecraft', 'modpack', 'runtime'];

        $egg->save();
    }

    private function syncVariables(int $eggId): void
    {
        $variables = [
            [
                'name' => 'Modpack Provider',
                'description' => 'Provider slug used by the modpack installer.',
                'env_variable' => 'MODPACK_PROVIDER',
                'default_value' => '',
                'rules' => ['required', 'string'],
            ],
            [
                'name' => 'Modpack ID',
                'description' => 'Modpack identifier for the selected provider.',
                'env_variable' => 'MODPACK_ID',
                'default_value' => '',
                'rules' => ['required', 'string'],
            ],
            [
                'name' => 'Modpack Version ID',
                'description' => 'Version identifier for the selected modpack.',
                'env_variable' => 'MODPACK_VERSION_ID',
                'default_value' => '',
                'rules' => ['required', 'string'],
            ],
        ];

        foreach ($variables as $variable) {
            EggVariable::query()->updateOrCreate(
                [
                    'egg_id' => $eggId,
                    'env_variable' => $variable['env_variable'],
                ],
                [
                    'name' => $variable['name'],
                    'description' => $variable['description'],
                    'default_value' => $variable['default_value'],
                    'user_viewable' => false,
                    'user_editable' => false,
                    'rules' => $variable['rules'],
                ]
            );
        }
    }

    private function getInstallScript(): string
    {
        return <<<'SCRIPT'
#!/bin/ash
set -e

mkdir -p /mnt/server
cd /mnt/server

if [ -z "${MODPACK_PROVIDER}" ] || [ -z "${MODPACK_ID}" ] || [ -z "${MODPACK_VERSION_ID}" ]; then
  echo "Missing required modpack variables."
  exit 1
fi

rm -rf libraries mods coremods .fabric .quilt
rm -f server.jar

echo "Installing modpack ${MODPACK_ID} (${MODPACK_VERSION_ID}) from ${MODPACK_PROVIDER}..."

/bin/minecraft_modpack_server_installer \
  --provider "${MODPACK_PROVIDER}" \
  --modpack-id "${MODPACK_ID}" \
  --modpack-version-id "${MODPACK_VERSION_ID}" \
  --directory /mnt/server

echo "Modpack installation finished."
SCRIPT;
    }

    private function getRuntimeInstallScript(): string
    {
        return <<<'SCRIPT'
#!/bin/ash
set -e

mkdir -p /mnt/server
cd /mnt/server

echo "Runtime egg ready."
SCRIPT;
    }
}
