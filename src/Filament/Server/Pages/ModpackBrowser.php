<?php

namespace TimVida\MinecraftModpacks\Filament\Server\Pages;

use App\Traits\Filament\BlockAccessInConflict;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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

    public ?string $selectedProvider = null;

    public function mount(): void
    {
        $this->selectedProvider = ModpackProvider::MODRINTH->value;
    }

    public function getTitle(): string
    {
        return 'Modpack Browser';
    }

    public function table(Table $table): Table
    {
        $manager = app(ModpackManager::class);

        return $table
            ->records(function (?string $search, int $page) use ($manager) {
                $provider = ModpackProvider::from($this->selectedProvider ?? ModpackProvider::MODRINTH->value);
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
            ->columns([
                ImageColumn::make('icon')
                    ->label('')
                    ->defaultImageUrl(url('/assets/images/egg-default.png'))
                    ->height(60),

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
                    ->label('Updated')
                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->diffForHumans() : 'Unknown')
                    ->icon('tabler-clock'),
            ])
            ->recordActions([
                Action::make('install')
                    ->icon('tabler-download')
                    ->color('success')
                    ->modalHeading(fn(array $record) => 'Install ' . $record['name'])
                    ->modalDescription('Select a version to install on this server')
                    ->form(function (array $record) use ($manager) {
                        $provider = ModpackProvider::from($this->selectedProvider);
                        $versions = $manager->getModpackVersions($provider, $record['id']);

                        $versionOptions = collect($versions)->mapWithKeys(function ($version) {
                            return [$version['id'] => $version['name']];
                        })->toArray();

                        return [
                            Radio::make('version_id')
                                ->label('Version')
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
                                ->label('Delete existing server files')
                                ->helperText('Warning: This will remove all current files from your server!')
                                ->default(false),
                        ];
                    })
                    ->action(function (array $data, array $record) {
                        $server = Filament::getTenant();
                        $provider = ModpackProvider::from($this->selectedProvider);
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
                                ->title('Installation Started')
                                ->body('The modpack is being downloaded to your server.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Installation Failed')
                                ->body('Could not start modpack installation. Check logs for details.')
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('details')
                    ->icon('tabler-info-circle')
                    ->color('info')
                    ->url(fn(array $record) => $record['url'] ?? '#', shouldOpenInNewTab: true)
                    ->hidden(fn(array $record) => empty($record['url'])),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(1)
                ->schema([
                    Section::make('Provider Selection')
                        ->description('Choose which platform to browse modpacks from')
                        ->schema([
                            Select::make('selectedProvider')
                                ->label('Modpack Provider')
                                ->options(
                                    collect(ModpackProvider::cases())
                                        ->mapWithKeys(fn($p) => [$p->value => $p->getDisplayName()])
                                        ->toArray()
                                )
                                ->default(ModpackProvider::MODRINTH->value)
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
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
                ->label('Clear Cache')
                ->icon('tabler-refresh')
                ->color('gray')
                ->action(function () {
                    $manager = app(ModpackManager::class);
                    $manager->clearCache();

                    Notification::make()
                        ->title('Cache Cleared')
                        ->body('Modpack cache has been cleared successfully.')
                        ->success()
                        ->send();

                    $this->resetTable();
                }),
        ];
    }
}
