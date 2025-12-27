<?php

namespace TimVida\MinecraftModpacks\Filament\Server\Pages;

use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use App\Traits\Filament\BlockAccessInConflict;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use TimVida\MinecraftModpacks\Enums\ModpackProvider;
use TimVida\MinecraftModpacks\Services\ModpackInstaller;
use TimVida\MinecraftModpacks\Services\ModpackManager;
use TimVida\MinecraftModpacks\Services\ModpacksService;
use TimVida\MinecraftModpacks\Services\ModpackTracker;

class ModpackBrowser extends Page implements HasTable
{
    use BlockAccessInConflict;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-box-multiple';

    protected static ?string $slug = 'modpacks';

    protected static ?string $navigationLabel = 'Modpacks';

    protected static ?int $navigationSort = 25;

    public ?array $installedModpack = null;

    public function mount(): void
    {
        $this->loadInstalledModpack();
    }

    protected function loadInstalledModpack(): void
    {
        try {
            $tracker = app(ModpackTracker::class);
            $server = Filament::getTenant();
            $this->installedModpack = $tracker->getInstalledModpack($server);
        } catch (\Exception $e) {
            $this->installedModpack = null;
        }
    }

    protected function getPerPage(): int
    {
        $perPage = config('minecraft-modpacks.modpacks_per_page', 20);
        return max(5, min(100, (int)$perPage));
    }

    public function getTitle(): string
    {
        return trans('minecraft-modpacks::modpacks.ui.browser.title');
    }

    public function getSubheading(): string|HtmlString|null
    {
        $baseDescription = trans('minecraft-modpacks::modpacks.ui.browser.description');

        if ($this->installedModpack) {
            $versionName = $this->installedModpack['version_name'];
            $modpackName = $this->installedModpack['modpack_name'];

            // Falls version_name den modpack_name enthält, entferne ihn
            if (str_contains($versionName, $modpackName)) {
                $versionName = trim(str_replace($modpackName, '', $versionName));
            }

            $installedLabel = sprintf(
                trans('minecraft-modpacks::modpacks.ui.browser.installed_modpack_label'),
                htmlspecialchars($modpackName),
                htmlspecialchars($versionName)
            );

            return new HtmlString($baseDescription . '<br>' . $installedLabel);
        }

        return $baseDescription;
    }

    protected function getProviderFromFilters(): ModpackProvider
    {
        $filters = $this->tableFilters;
        $providerFilter = $filters['provider'] ?? null;
        $providerValue = is_array($providerFilter)
            ? ($providerFilter['value'] ?? null)
            : $providerFilter;

        if (empty($providerValue) || $providerValue === '') {
            return ModpackProvider::MODRINTH;
        }

        try {
            return ModpackProvider::from($providerValue);
        } catch (\Exception $e) {
            return ModpackProvider::MODRINTH;
        }
    }

    protected function hasUpdateForInstalled(): bool
    {
        if (!$this->installedModpack) {
            return false;
        }
        return $this->hasUpdate($this->installedModpack['modpack_id']);
    }

    protected function hasUpdate(string $modpackId): bool
    {
        if (!$this->installedModpack || $this->installedModpack['modpack_id'] !== $modpackId) {
            return false;
        }

        try {
            $manager = app(ModpackManager::class);
            $provider = ModpackProvider::from($this->installedModpack['provider']);
            $versions = $manager->getModpackVersions($provider, $modpackId);

            if (empty($versions)) {
                return false;
            }

            // Erste Version ist die neueste
            $latestVersionId = $versions[0]['id'] ?? null;

            if (!$latestVersionId) {
                return false;
            }

            // Debug logging
            \Illuminate\Support\Facades\Log::debug(trans('minecraft-modpacks::modpacks.ui.browser.debug.update_check'), [
                'installed_version_id' => $this->installedModpack['version_id'],
                'latest_version_id' => $latestVersionId,
                'has_update' => $this->installedModpack['version_id'] !== $latestVersionId
            ]);

            // Vergleiche die version_id direkt
            return $this->installedModpack['version_id'] !== $latestVersionId;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(trans('minecraft-modpacks::modpacks.ui.browser.error.check_updates'), [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected function isInstalled(string $modpackId): bool
    {
        return $this->installedModpack && $this->installedModpack['modpack_id'] === $modpackId;
    }

    public function table(Table $table): Table
    {
        $manager = app(ModpackManager::class);
        $perPage = $this->getPerPage();

        return $table
            ->records(function (?string $search, int $page) use ($manager, $perPage) {
                $provider = $this->getProviderFromFilters();
                $result = $manager->searchModpacks($provider, $search, $page, $perPage);

                return new LengthAwarePaginator(
                    $result['items'],
                    $result['total'],
                    $perPage,
                    $page
                );
            })
            ->paginated([$perPage])
            ->filters([
                SelectFilter::make('provider')
                    ->label(trans('minecraft-modpacks::modpacks.ui.browser.provider'))
                    ->options(
                        collect(ModpackProvider::cases())
                            ->mapWithKeys(fn($p) => [$p->value => $p->getDisplayName()])
                            ->toArray()
                    )
                    ->default(ModpackProvider::MODRINTH->value),
            ])
            ->columns([
                ImageColumn::make('icon')
                    ->label('')
                    ->defaultImageUrl(url('/assets/images/egg-default.png'))
                    ->size(60),

                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold')
                    ->formatStateUsing(function ($state, array $record) {
                        if (!$this->isInstalled($record['id'])) {
                            return $state;
                        }

                        $hasUpdate = $this->hasUpdate($record['id']);
                        $emoji = $hasUpdate ? '🟠' : '🟢';

                        return sprintf('%s %s %s', $emoji, $state, $emoji);
                    })
                    ->description(fn(array $record) => $this->truncateText($record['summary'] ?? '', 150)),

                TextColumn::make('author')
                    ->label(trans('minecraft-modpacks::modpacks.ui.common.author'))
                    ->toggleable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('downloads')
                    ->label(trans('minecraft-modpacks::modpacks.ui.common.downloads'))
                    ->toggleable()
                    ->numeric()
                    ->icon('tabler-download')
                    ->sortable(false),

                TextColumn::make('updated_at')
                    ->toggleable()
                    ->label(trans('minecraft-modpacks::modpacks.ui.browser.updated'))
                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->diffForHumans() : trans('minecraft-modpacks::modpacks.ui.browser.unknown'))
                    ->icon('tabler-clock'),
            ])
            ->recordActions([
                Action::make('install')
                    ->label(function (array $record) {
                        if ($this->isInstalled($record['id']) && $this->hasUpdate($record['id'])) {
                            return trans('minecraft-modpacks::modpacks.tracking.update_available');
                        }
                        return trans('minecraft-modpacks::modpacks.ui.common.install');
                    })
                    ->icon(function (array $record) {
                        if ($this->isInstalled($record['id']) && $this->hasUpdate($record['id'])) {
                            return 'tabler-arrow-up-circle';
                        }
                        return 'tabler-download';
                    })
                    ->color(function (array $record) {
                        if ($this->isInstalled($record['id']) && $this->hasUpdate($record['id'])) {
                            return 'warning';
                        }
                        return 'success';
                    })
                    ->modalHeading(fn(array $record) => trans('minecraft-modpacks::modpacks.ui.common.install') . ' ' . $record['name'])
                    ->modalDescription(trans('minecraft-modpacks::modpacks.ui.browser.select_version'))
                    ->form(function (array $record) use ($manager) {
                        $provider = $this->getProviderFromFilters();
                        $versions = $manager->getModpackVersions($provider, $record['id']);

                        if (empty($versions)) {
                            return [
                                TextEntry::make('no_versions')
                                    ->label('')
                                    ->state(trans('minecraft-modpacks::modpacks.ui.browser.no_versions')),
                            ];
                        }

                        $versionOptions = collect($versions)->mapWithKeys(function ($version) {
                            $label = $version['name'];
                            if ($this->installedModpack && $version['id'] === $this->installedModpack['version_id']) {
                                $emoji = $this->hasUpdateForInstalled() ? '🟠' : '🟢';
                                $label = sprintf('%s %s %s', $emoji, $label, $emoji);
                            }
                            return [$version['id'] => $label];
                        })->toArray();

                        // Default to current version if installed, otherwise latest
                        $defaultVersion = $versions[0]['id'] ?? null;
                        if ($this->installedModpack && $this->isInstalled($record['id'])) {
                            $defaultVersion = $this->installedModpack['version_id'];
                        }

                        return [
                            Radio::make('version_id')
                                ->label(trans('minecraft-modpacks::modpacks.ui.browser.select_version'))
                                ->options($versionOptions)
                                ->default($defaultVersion)
                                ->required()
                                ->descriptions(
                                    collect($versions)->mapWithKeys(function ($version) {
                                        $info = [];
                                        if (!empty($version['version_number'])) {
                                            $info[] = $version['version_number'];
                                        }
                                        if (!empty($version['published_at'])) {
                                            $info[] = Carbon::parse($version['published_at'])->format('M d, Y');
                                        }

                                        if ($this->installedModpack && 
                                            $version['id'] === $this->installedModpack['version_id']) {
                                            $info[] = trans('minecraft-modpacks::modpacks.ui.browser.current_version_marker');
                                        }

                                        return [$version['id'] => implode(' • ', $info)];
                                    })->toArray()
                                ),

                            Toggle::make('delete_existing')
                                ->label(trans('minecraft-modpacks::modpacks.ui.browser.delete_existing'))
                                ->helperText(trans('minecraft-modpacks::modpacks.ui.browser.delete_warning'))
                                ->default(false),
                        ];
                    })
                    ->action(function (array $data, array $record) {
                        /** @var Server $server */
                        $server = Filament::getTenant();
                        $provider = $this->getProviderFromFilters();
                        $installer = app(ModpackInstaller::class);

                        $success = $installer->install(
                            $server,
                            $provider,
                            $record['id'],
                            $data['version_id'],
                            $data['delete_existing'] ?? false
                        );

                        if ($success) {
                            Notification::make()
                                ->title(trans('minecraft-modpacks::modpacks.ui.browser.installation_started'))
                                ->body(trans('minecraft-modpacks::modpacks.ui.browser.installation_started_message'))
                                ->success()
                                ->duration(15000)
                                ->send();

                            $this->loadInstalledModpack();
                            $this->redirect("/server/{$server->uuid}", navigate: false);
                        } else {
                            Notification::make()
                                ->title(trans('minecraft-modpacks::modpacks.ui.browser.installation_failed'))
                                ->body(trans('minecraft-modpacks::modpacks.ui.browser.installation_failed_message'))
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    private function truncateText(string $text, int $length = 100): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . '...';
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Wenn Modpack installiert ist, zeige Button
        if ($this->installedModpack) {
            $hasUpdate = $this->hasUpdateForInstalled();
            $manager = app(ModpackManager::class);
            $provider = ModpackProvider::from($this->installedModpack['provider']);
            $modpackId = $this->installedModpack['modpack_id'];

            if ($hasUpdate) {
                // ORANGE Button mit Funktion: Update Available
                $actions[] = Action::make('update_modpack')
                    ->label(trans('minecraft-modpacks::modpacks.tracking.update_available'))
                    ->icon('tabler-arrow-up-circle')
                    ->color('warning')
                    ->modalHeading(trans('minecraft-modpacks::modpacks.tracking.update_modpack'))
                    ->modalDescription(trans('minecraft-modpacks::modpacks.ui.browser.select_version'))
                    ->form(function () use ($manager, $provider, $modpackId) {
                        $versions = $manager->getModpackVersions($provider, $modpackId);

                        if (empty($versions)) {
                            return [
                                TextEntry::make('no_versions')
                                    ->label('')
                                    ->state(trans('minecraft-modpacks::modpacks.ui.browser.no_versions')),
                            ];
                        }

                        $versionOptions = collect($versions)->mapWithKeys(function ($version) {
                            $label = $version['name'];
                            if ($this->installedModpack && $version['id'] === $this->installedModpack['version_id']) {
                                $label = sprintf('🟠 %s 🟠', $label);
                            }
                            return [$version['id'] => $label];
                        })->toArray();

                        // Default auf CURRENT Version
                        $defaultVersion = $this->installedModpack['version_id'] ?? ($versions[0]['id'] ?? null);

                        return [
                            Radio::make('version_id')
                                ->label(trans('minecraft-modpacks::modpacks.ui.browser.select_version'))
                                ->options($versionOptions)
                                ->default($defaultVersion)
                                ->required()
                                ->descriptions(
                                    collect($versions)->mapWithKeys(function ($version) {
                                        $info = [];
                                        if (!empty($version['version_number'])) {
                                            $info[] = $version['version_number'];
                                        }
                                        if (!empty($version['published_at'])) {
                                            $info[] = Carbon::parse($version['published_at'])->format('M d, Y');
                                        }

                                        if ($this->installedModpack && 
                                            $version['id'] === $this->installedModpack['version_id']) {
                                            $info[] = trans('minecraft-modpacks::modpacks.ui.browser.current_version_marker');
                                        }

                                        return [$version['id'] => implode(' • ', $info)];
                                    })->toArray()
                                ),

                            Toggle::make('delete_existing')
                                ->label(trans('minecraft-modpacks::modpacks.ui.browser.delete_existing'))
                                ->helperText(trans('minecraft-modpacks::modpacks.ui.browser.delete_warning'))
                                ->default(false),
                        ];
                    })
                    ->action(function (array $data) use ($provider, $modpackId) {
                        $server = Filament::getTenant();
                        $installer = app(ModpackInstaller::class);

                        $success = $installer->install(
                            $server,
                            $provider,
                            $modpackId,
                            $data['version_id'],
                            $data['delete_existing'] ?? false
                        );

                        if ($success) {
                            Notification::make()
                                ->title(trans('minecraft-modpacks::modpacks.ui.browser.installation_started'))
                                ->body(trans('minecraft-modpacks::modpacks.ui.browser.installation_started_message'))
                                ->success()
                                ->duration(15000)
                                ->send();

                            $this->loadInstalledModpack();
                            $this->redirect("/server/{$server->uuid}", navigate: false);
                        } else {
                            Notification::make()
                                ->title(trans('minecraft-modpacks::modpacks.ui.browser.installation_failed'))
                                ->body(trans('minecraft-modpacks::modpacks.ui.browser.installation_failed_message'))
                                ->danger()
                                ->send();
                        }
                    });
            } else {
                // GRÜNER Button OHNE Funktion: Up to Date
                $actions[] = Action::make('up_to_date')
                    ->label(trans('minecraft-modpacks::modpacks.tracking.up_to_date'))
                    ->icon('tabler-check-circle')
                    ->color('success')
                    ->disabled(); // Keine Aktion möglich
            }
        }

        // Clear Cache Button
        $actions[] = Action::make('refresh_cache')
            ->label(trans('minecraft-modpacks::modpacks.ui.browser.clear_cache'))
            ->icon('tabler-refresh')
            ->color('gray')
            ->action(function () {
                $manager = app(ModpackManager::class);
                $manager->clearCache();

                Notification::make()
                    ->title(trans('minecraft-modpacks::modpacks.ui.browser.cache_cleared'))
                    ->body(trans('minecraft-modpacks::modpacks.ui.browser.cache_cleared_message'))
                    ->success()
                    ->send();

                $this->resetTable();
            });

        return $actions;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $server = Filament::getTenant();
        if (!$server) {
            return false;
        }
        return ModpacksService::hasModpacksSupport($server);
    }

    public function authorizeAccess(): void
    {
        $server = $this->record;
        if (!ModpacksService::hasModpacksSupport($server)) {
            abort(403, trans('minecraft-modpacks::modpacks.ui.browser.error.not_available'));
        }
    }
}