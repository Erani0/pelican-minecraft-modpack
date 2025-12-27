<?php

return [
    'installer' => [
        'unknown_modpack' => 'Modpack Sconosciuto',
        'unknown' => 'Sconosciuto',
        'backup' => [
            'pre_installation' => 'Pre-Installazione-Modpack',
            'before_update' => 'Prima dell\'aggiornamento: :modpack :version',
        ],
        'debug' => [
            'backup_name_generated' => 'Nome backup generato',
            'backup_limit_check' => 'Controllo limite backup',
            'waiting_backup' => 'In attesa del completamento del backup',
        ],
        'warning' => [
            'backup_name_failed' => 'Generazione nome backup fallita',
            'delete_backup_failed' => 'Eliminazione vecchio backup fallita',
            'check_delete_backups_failed' => 'Controllo/eliminazione vecchi backup fallita',
            'backup_completed_failed' => 'Backup completato ma fallito',
            'backup_timeout' => 'Timeout backup - procedo comunque',
            'backup_null' => 'Il servizio di backup ha restituito null',
        ],
        'info' => [
            'creating_backup' => 'Creazione backup prima dell\'installazione del modpack',
            'backup_limit_reached' => 'Limite backup raggiunto, elimino i più vecchi',
            'deleted_old_backup' => 'Vecchio backup eliminato',
            'backup_initiated' => 'Backup avviato',
            'backup_completed' => 'Backup completato con successo',
        ],
        'error' => [
            'backup_failed' => 'Creazione backup fallita',
        ],
    ],
    'providers' => [
        'atlauncher' => [
            'warning' => [
                'log1' => 'Richiesta API ATLauncher fallita',
            ],
            'error' => [
                'log1' => 'Errore nel recupero modpacks ATLauncher',
                'log2' => 'Errore nel recupero versioni ATLauncher',
                'log3' => 'Errore nel recupero dettagli ATLauncher',
            ],
        ],
        'curseforge' => [
            'warning' => [
                'log1' => 'Chiave API CurseForge non configurata - le richieste falliranno',
                'log2' => 'Richiesta API CurseForge fallita',
            ],
            'error' => [
                'log1' => 'Chiave API CurseForge richiesta ma non configurata',
                'log2' => 'Errore nel recupero modpacks CurseForge',
                'log3' => 'Errore nel recupero versioni CurseForge',
                'log4' => 'Errore nel recupero dettagli CurseForge',
                'log5' => 'Errore nel recupero info download CurseForge',
            ],
        ],
        'feedthebeast' => [
            'warning' => [
                'log1' => 'Richiesta API FTB fallita',
            ],
            'error' => [
                'log1' => 'Errore nel recupero modpacks FTB',
                'log2' => 'Errore nel recupero versioni FTB',
                'log3' => 'Errore nel recupero dettagli FTB',
            ],
        ],
        'modrinth' => [
            'warning' => [
                'log1' => 'Richiesta API Modrinth fallita',
            ],
            'error' => [
                'log1' => 'Errore nel recupero modpacks Modrinth',
                'log2' => 'Errore nel recupero versioni Modrinth',
                'log3' => 'Errore nel recupero dettagli Modrinth',
                'log4' => 'Errore nel recupero info download Modrinth',
            ],
        ],
        'technic' => [
            'warning' => [
                'log1' => 'Richiesta API Technic fallita',
            ],
            'error' => [
                'log1' => 'Errore nel recupero modpacks Technic',
                'log2' => 'Errore nel recupero build Technic',
                'log3' => 'Errore nel recupero dettagli Technic',
            ],
        ],
        'voidswrath' => [
            'warning' => [
                'log1' => 'Richiesta JSON VoidsWrath fallita',
                'log2' => 'Modpack VoidsWrath non trovato',
            ],
            'error' => [
                'log1' => 'La risposta VoidsWrath non è un array',
                'log2' => 'Errore nel recupero modpacks VoidsWrath',
                'log3' => 'Errore nel recupero dettagli VoidsWrath',
                'log4' => 'Errore nel recupero info download VoidsWrath',
            ],
        ],
    ],
    'ui' => [
        'browser' => [
            'title' => 'Modpack Browser',
            'description' => 'Sfoglia e installa modpack da varie piattaforme. Usa il filtro sopra la tabella per selezionare un provider.',
            'provider' => 'Provider',
            'updated' => 'Aggiornato',
            'no_versions' => 'Nessuna versione disponibile per questo modpack.',
            'version' => 'Versione',
            'delete_existing' => 'Elimina file server esistenti',
            'delete_warning' => 'Attenzione: Questo rimuoverà tutti i file attuali dal tuo server!',
            'installation_started' => 'Installazione Avviata',
            'installation_started_message' => 'Il modpack è in fase di download sul tuo server.',
            'installation_failed' => 'Installazione Fallita',
            'installation_failed_message' => 'Impossibile avviare l\'installazione del modpack. Controlla i log per i dettagli.',
            'clear_cache' => 'Svuota Cache',
            'cache_cleared' => 'Cache Svuotata',
            'cache_cleared_message' => 'La cache dei modpack è stata svuotata con successo.',
            'modpacks' => 'Modpack',
            'installed_modpack_label' => 'Modpack Installato: %s %s',
            'unknown' => 'Sconosciuto',
            'select_version' => 'Seleziona Versione',
            'current_version_marker' => '(Attuale)',
            'debug' => [
                'update_check' => 'Controllo aggiornamenti',
            ],
            'error' => [
                'check_updates' => 'Impossibile controllare gli aggiornamenti',
                'not_available' => 'Modpack Browser non è disponibile per questo server.',
            ],
        ],
        'plugin' => [
            'curseforge_api_key' => 'Chiave API CurseForge',
            'curseforge_api_key_help' => 'Richiesta per sfogliare i modpack CurseForge. Ottieni la tua chiave su console.curseforge.com',
            'cache_duration' => 'Durata Cache (secondi)',
            'request_timeout' => 'Timeout Richiesta API (secondi)',
            'modpacks_per_page' => 'Modpack per Pagina',
            'modpacks_per_page_help' => 'Numero di modpack da visualizzare per pagina (5-100)',
            'settings_updated' => 'Impostazioni Aggiornate',
            'settings_updated_message' => 'Le impostazioni del plugin sono state salvate con successo.',
        ],
        'providers' => [
            'modrinth' => 'Modrinth',
            'curseforge' => 'CurseForge',
            'atlauncher' => 'ATLauncher',
            'feedthebeast' => 'Feed The Beast',
            'technic' => 'Technic',
            'voidswrath' => 'Voids Wrath',
            'unknown' => 'Sconosciuto',
        ],
        'common' => [
            'author' => 'Autore',
            'downloads' => 'Download',
            'icon' => 'Icona',
            'name' => 'Nome',
            'summary' => 'Riepilogo',
            'website_url' => 'URL Sito Web',
            'latest' => 'Ultimo',
            'install' => 'Installa',
            'refresh_cache' => 'Aggiorna Cache',
        ],
    ],
    'tracking' => [
        'installed_modpack' => 'Installato: :name :version',
        'update_available' => 'Aggiornamento Disponibile',
        'update_available_short' => 'Aggiornamento Disp.',
        'up_to_date' => 'Aggiornato',
        'update_modpack' => 'Aggiorna Modpack',
        'current_version' => 'Versione Attuale',
        'creating_backup' => 'Creazione backup prima dell\'installazione...',
        'backup_created' => 'Backup creato con successo',
        'backup_error' => 'Creazione backup fallita',
        'backup_failed' => 'Backup fallito',
        'warning' => [
            'invalid_data' => 'Dati tracciamento modpack non validi',
            'read_failed' => 'Lettura file tracciamento modpack fallita',
            'clear_failed' => 'Pulizia dati tracciamento modpack fallita',
            'no_put_method' => 'Il repository non supporta il metodo putContent',
        ],
        'error' => [
            'save_failed' => 'Salvataggio dati tracciamento modpack fallito',
        ],
        'info' => [
            'cleared' => 'Dati tracciamento modpack puliti',
            'saved' => 'Dati tracciamento modpack salvati',
        ],
    ],
];
