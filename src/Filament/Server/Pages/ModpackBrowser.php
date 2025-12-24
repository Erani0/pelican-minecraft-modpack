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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use TimVida\MinecraftModpacks\Enums\ModpackProvider;
use TimVida\MinecraftModpacks\Services\ModpackInstaller;
use TimVida\MinecraftModpacks\Services\ModpackManager;

class ModpackBrowser extends Page implements HasTable
{
    use BlockAccessInConflict;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-box-multiple';

    protected static ?string $slug = 'modpacks';

    protected static ?string $navigationLabel = 'Modpacks';

    protected static ?int $navigationSort = 25;

    public function getTitle(): string
    {
        return trans('minecraft-modpacks::modpacks.ui.browser.title');
    }

    public function table(Table $table): Table
    {
        $manager = app(ModpackManager::class);

        return $table
            ->records(function (?string $search, int $page) use ($manager) {
                $filters = $this->tableFilters;
                $providerFilter = $filters['provider'] ?? ModpackProvider::MODRINTH->value;

                // Extract value from filter array if needed
                $providerValue = is_array($providerFilter)
                    ? ($providerFilter['value'] ?? ModpackProvider::MODRINTH->value)
                    : $providerFilter;

                try {
                    $provider = ModpackProvider::from($providerValue);
                } catch (\Exception $e) {
                    $provider = ModpackProvider::MODRINTH;
                }

                $perPage = 20;
                $result = $manager->searchModpacks($provider, $search, $page, $perPage);

                return new LengthAwarePaginator(
                    $result['items'],
                    $result['total'],
                    $perPage,
                    $page
                );
            })
            ->paginated([20])
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
                    ->description(fn(array $record) => $this->truncateText($record['summary'] ?? '', 150)),

                TextColumn::make('author')
                    ->toggleable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('downloads')
                    ->toggleable()
                    ->numeric()
                    ->icon('tabler-download')
                    ->sortable(false),

                TextColumn::make('updated_at')
                    ->toggleable()
                    ->label(trans('minecraft-modpacks::modpacks.ui.browser.updated'))
                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->diffForHumans() : 'Unknown')
                    ->icon('tabler-clock'),
            ])
            ->recordActions([
                Action::make('install')
                    ->icon('tabler-download')
                    ->color('success')
                    ->modalHeading(fn(array $record) => trans('minecraft-modpacks::modpacks.ui.common.install') . ' ' . $record['name'])
                    ->modalDescription(trans('minecraft-modpacks::modpacks.ui.browser.version'))
                    ->form(function (array $record) use ($manager) {
                        $filters = $this->tableFilters;
                        $providerFilter = $filters['provider'] ?? ModpackProvider::MODRINTH->value;

                        // Extract value from filter array if needed
                        $providerValue = is_array($providerFilter)
                            ? ($providerFilter['value'] ?? ModpackProvider::MODRINTH->value)
                            : $providerFilter;

                        try {
                            $provider = ModpackProvider::from($providerValue);
                        } catch (\Exception $e) {
                            $provider = ModpackProvider::MODRINTH;
                        }

                        $versions = $manager->getModpackVersions($provider, $record['id']);

                        if (empty($versions)) {
                            return [
                                TextEntry::make('no_versions')
                                    ->label('')
                                    ->state(trans('minecraft-modpacks::modpacks.ui.browser.no_versions')),
                            ];
                        }

                        $versionOptions = collect($versions)->mapWithKeys(function ($version) {
                            return [$version['id'] => $version['name']];
                        })->toArray();

                        return [
                            Radio::make('version_id')
                                ->label(trans('minecraft-modpacks::modpacks.ui.browser.version'))
                                ->options($versionOptions)
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

                        $filters = $this->tableFilters;
                        $providerFilter = $filters['provider'] ?? ModpackProvider::MODRINTH->value;

                        // Extract value from filter array if needed
                        $providerValue = is_array($providerFilter)
                            ? ($providerFilter['value'] ?? ModpackProvider::MODRINTH->value)
                            : $providerFilter;

                        try {
                            $provider = ModpackProvider::from($providerValue);
                        } catch (\Exception $e) {
                            $provider = ModpackProvider::MODRINTH;
                        }

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
                                ->send();
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
            Grid::make(1)
                ->schema([
                    Section::make(trans('minecraft-modpacks::modpacks.ui.browser.title'))
                        ->description(trans('minecraft-modpacks::modpacks.ui.browser.description'))
                        ->schema([
                            EmbeddedTable::make(),
                        ]),
                ]),
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
        return [
            Action::make('refresh_cache')
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
                }),
        ];
    }
}