<?php

namespace TimVida\MinecraftModpacks;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Panel;

class MinecraftModpacksPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'minecraft-modpacks';
    }

    public function register(Panel $panel): void
    {
        $panelName = str($panel->getId())->title()->toString();

        $panel->discoverPages(
            plugin_path($this->getId(), "src/Filament/$panelName/Pages"),
            "TimVida\\MinecraftModpacks\\Filament\\$panelName\\Pages"
        );
    }

    public function boot(Panel $panel): void
    {
        try {
            if (class_exists(\Database\Seeders\MinecraftModpacksSeeder::class)) {
                (new \Database\Seeders\MinecraftModpacksSeeder())->run();
            }
        } catch (\Throwable $e) {
            // Avoid breaking panel boot if seeding fails.
        }
    }

    public function getSettingsForm(): array
    {
        return [
            TextInput::make('curseforge_api_key')
                ->label('CurseForge API Key')
                ->password()
                ->revealable()
                ->helperText('Required for CurseForge modpack browsing. Get your key at console.curseforge.com')
                ->default(fn () => config('modpacks.curseforge_api_key', '')),

            TextInput::make('cache_duration')
                ->label('Cache Duration (seconds)')
                ->numeric()
                ->minValue(0)
                ->default(fn () => config('modpacks.cache_duration', 1800)),

            TextInput::make('request_timeout')
                ->label('API Request Timeout (seconds)')
                ->numeric()
                ->minValue(1)
                ->maxValue(30)
                ->default(fn () => config('modpacks.request_timeout', 10)),
        ];
    }

    public function saveSettings(array $data): void
    {
        $envData = [];

        if (isset($data['curseforge_api_key'])) {
            $envData['CURSEFORGE_API_KEY'] = $data['curseforge_api_key'];
        }

        if (isset($data['cache_duration'])) {
            $envData['MODPACKS_CACHE_DURATION'] = $data['cache_duration'];
        }

        if (isset($data['request_timeout'])) {
            $envData['MODPACKS_REQUEST_TIMEOUT'] = $data['request_timeout'];
        }

        $this->writeToEnvironment($envData);

        Notification::make()
            ->title('Settings Updated')
            ->body('Plugin settings have been saved successfully.')
            ->success()
            ->send();
    }
}
