<?php

return [
    'installer' => [
        'unknown_modpack' => 'Unbekanntes Modpack',
        'unknown' => 'Unbekannt',
        'backup' => [
            'pre_installation' => 'Vor-Modpack-Installation',
            'before_update' => 'Vor Update: :modpack :version',
        ],
        'debug' => [
            'backup_name_generated' => 'Backup-Name generiert',
            'backup_limit_check' => 'Backup-Limit-Check',
            'waiting_backup' => 'Warte auf Backup-Abschluss',
        ],
        'warning' => [
            'backup_name_failed' => 'Fehler beim Generieren des Backup-Namens',
            'delete_backup_failed' => 'Fehler beim Löschen des alten Backups',
            'check_delete_backups_failed' => 'Fehler beim Prüfen/Löschen alter Backups',
            'backup_completed_failed' => 'Backup abgeschlossen aber fehlgeschlagen',
            'backup_timeout' => 'Backup-Timeout - fahre trotzdem fort',
            'backup_null' => 'Backup-Service gab null zurück',
        ],
        'info' => [
            'creating_backup' => 'Erstelle Backup vor Modpack-Installation',
            'backup_limit_reached' => 'Backup-Limit erreicht, lösche älteste Backups',
            'deleted_old_backup' => 'Altes Backup gelöscht',
            'backup_initiated' => 'Backup initiiert',
            'backup_completed' => 'Backup erfolgreich abgeschlossen',
        ],
        'error' => [
            'backup_failed' => 'Fehler beim Erstellen des Backups',
        ],
    ],
    'providers' => [
        'atlauncher' => [
            'warning' => [
                'log1' => 'ATLauncher API-Anfrage fehlgeschlagen',
            ],
            'error' => [
                'log1' => 'Fehler beim Abrufen der ATLauncher Modpacks',
                'log2' => 'Fehler beim Abrufen der ATLauncher Versionen',
                'log3' => 'Fehler beim Abrufen der ATLauncher Details',
            ],
        ],
        'curseforge' => [
            'warning' => [
                'log1' => 'CurseForge API-Key nicht konfiguriert - Anfragen werden fehlschlagen',
                'log2' => 'CurseForge API-Anfrage fehlgeschlagen',
            ],
            'error' => [
                'log1' => 'CurseForge API-Key ist erforderlich, aber nicht konfiguriert',
                'log2' => 'Fehler beim Abrufen der CurseForge Modpacks',
                'log3' => 'Fehler beim Abrufen der CurseForge Versionen',
                'log4' => 'Fehler beim Abrufen der CurseForge Details',
                'log5' => 'Fehler beim Abrufen der CurseForge Download-Infos',
            ],
        ],
        'feedthebeast' => [
            'warning' => [
                'log1' => 'FTB API-Anfrage fehlgeschlagen',
            ],
            'error' => [
                'log1' => 'Fehler beim Abrufen der FTB Modpacks',
                'log2' => 'Fehler beim Abrufen der FTB Versionen',
                'log3' => 'Fehler beim Abrufen der FTB Details',
            ],
        ],
        'modrinth' => [
            'warning' => [
                'log1' => 'Modrinth API-Anfrage fehlgeschlagen',
            ],
            'error' => [
                'log1' => 'Fehler beim Abrufen der Modrinth Modpacks',
                'log2' => 'Fehler beim Abrufen der Modrinth Versionen',
                'log3' => 'Fehler beim Abrufen der Modrinth Details',
                'log4' => 'Fehler beim Abrufen der Modrinth Download-Infos',
            ],
        ],
        'technic' => [
            'warning' => [
                'log1' => 'Technic API-Anfrage fehlgeschlagen',
            ],
            'error' => [
                'log1' => 'Fehler beim Abrufen der Technic Modpacks',
                'log2' => 'Fehler beim Abrufen des Technic Builds',
                'log3' => 'Fehler beim Abrufen der Technic Details',
            ],
        ],
        'voidswrath' => [
            'warning' => [
                'log1' => 'VoidsWrath JSON-Anfrage fehlgeschlagen',
                'log2' => 'VoidsWrath Modpack nicht gefunden',
            ],
            'error' => [
                'log1' => 'VoidsWrath Antwort ist kein Array',
                'log2' => 'Fehler beim Abrufen der VoidsWrath Modpacks',
                'log3' => 'Fehler beim Abrufen der VoidsWrath Details',
                'log4' => 'Fehler beim Abrufen der VoidsWrath Download-Infos',
            ],
        ],
    ],
    'ui' => [
        'browser' => [
            'title' => 'Modpack Browser',
            'description' => 'Durchsuchen und installieren Sie Modpacks von verschiedenen Plattformen. Nutzen Sie den Filter über der Tabelle, um einen Anbieter zu wählen.',
            'provider' => 'Anbieter',
            'updated' => 'Aktualisiert',
            'no_versions' => 'Keine Versionen für dieses Modpack verfügbar.',
            'version' => 'Version',
            'delete_existing' => 'Bestehende Serverdateien löschen',
            'delete_warning' => 'Warnung: Dies wird alle aktuellen Dateien von Ihrem Server entfernen!',
            'installation_started' => 'Installation gestartet',
            'installation_started_message' => 'Das Modpack wird auf Ihren Server heruntergeladen.',
            'installation_failed' => 'Installation fehlgeschlagen',
            'installation_failed_message' => 'Modpack-Installation konnte nicht gestartet werden. Prüfen Sie die Logs.',
            'clear_cache' => 'Cache leeren',
            'cache_cleared' => 'Cache geleert',
            'cache_cleared_message' => 'Modpack-Cache wurde erfolgreich geleert.',
            'modpacks' => 'Modpacks',
            'installed_modpack_label' => 'Installiertes Modpack: %s %s',
            'unknown' => 'Unbekannt',
            'select_version' => 'Version wählen',
            'current_version_marker' => '(Aktuell)',
            'debug' => [
                'update_check' => 'Update-Prüfung',
            ],
            'error' => [
                'check_updates' => 'Fehler bei der Suche nach Updates',
                'not_available' => 'Modpack Browser ist für diesen Server nicht verfügbar.',
            ],
        ],
        'plugin' => [
            'curseforge_api_key' => 'CurseForge API Key',
            'curseforge_api_key_help' => 'Erforderlich für CurseForge Modpacks. Key erhältlich unter console.curseforge.com',
            'cache_duration' => 'Cache-Dauer (Sekunden)',
            'request_timeout' => 'API-Anfrage-Timeout (Sekunden)',
            'modpacks_per_page' => 'Modpacks pro Seite',
            'modpacks_per_page_help' => 'Anzahl der angezeigten Modpacks pro Seite (5-100)',
            'settings_updated' => 'Einstellungen aktualisiert',
            'settings_updated_message' => 'Plugin-Einstellungen wurden erfolgreich gespeichert.',
        ],
        'providers' => [
            'modrinth' => 'Modrinth',
            'curseforge' => 'CurseForge',
            'atlauncher' => 'ATLauncher',
            'feedthebeast' => 'Feed The Beast',
            'technic' => 'Technic',
            'voidswrath' => 'Voids Wrath',
            'unknown' => 'Unbekannt',
        ],
        'common' => [
            'author' => 'Autor',
            'downloads' => 'Downloads',
            'icon' => 'Icon',
            'name' => 'Name',
            'summary' => 'Zusammenfassung',
            'website_url' => 'Webseiten-URL',
            'latest' => 'Neueste',
            'install' => 'Installieren',
            'refresh_cache' => 'Cache aktualisieren',
        ],
    ],
    'tracking' => [
        'installed_modpack' => 'Installiert: :name :version',
        'update_available' => 'Update verfügbar',
        'update_available_short' => 'Update verfügbar',
        'up_to_date' => 'Aktuell',
        'update_modpack' => 'Modpack aktualisieren',
        'current_version' => 'Aktuelle Version',
        'creating_backup' => 'Erstelle Backup vor der Installation...',
        'backup_created' => 'Backup erfolgreich erstellt',
        'backup_error' => 'Fehler beim Erstellen des Backups',
        'backup_failed' => 'Backup fehlgeschlagen',
        'warning' => [
            'invalid_data' => 'Ungültige Modpack-Tracking-Daten',
            'read_failed' => 'Fehler beim Lesen der Modpack-Tracking-Datei',
            'clear_failed' => 'Fehler beim Löschen der Modpack-Tracking-Daten',
            'no_put_method' => 'Repository unterstützt putContent-Methode nicht',
        ],
        'error' => [
            'save_failed' => 'Fehler beim Speichern der Modpack-Tracking-Daten',
        ],
        'info' => [
            'cleared' => 'Modpack-Tracking-Daten gelöscht',
            'saved' => 'Modpack-Tracking-Daten gespeichert',
        ],
    ],
];
