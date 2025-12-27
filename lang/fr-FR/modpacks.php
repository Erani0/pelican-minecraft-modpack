<?php

return [
    'installer' => [
        'unknown_modpack' => 'Modpack inconnu',
        'unknown' => 'Inconnu',
        'backup' => [
            'pre_installation' => 'Pré-Installation-Modpack',
            'before_update' => 'Avant mise à jour : :modpack :version',
        ],
        'debug' => [
            'backup_name_generated' => 'Nom de sauvegarde généré',
            'backup_limit_check' => 'Vérification de la limite de sauvegarde',
            'waiting_backup' => 'En attente de la fin de la sauvegarde',
        ],
        'warning' => [
            'backup_name_failed' => 'Échec de la génération du nom de sauvegarde',
            'delete_backup_failed' => 'Échec de la suppression de l\'ancienne sauvegarde',
            'check_delete_backups_failed' => 'Échec de la vérification/suppression des anciennes sauvegardes',
            'backup_completed_failed' => 'Sauvegarde terminée mais échouée',
            'backup_timeout' => 'Délai de sauvegarde dépassé - continuation quand même',
            'backup_null' => 'Le service de sauvegarde a retourné null',
        ],
        'info' => [
            'creating_backup' => 'Création d\'une sauvegarde avant l\'installation du modpack',
            'backup_limit_reached' => 'Limite de sauvegarde atteinte, suppression des plus anciennes',
            'deleted_old_backup' => 'Ancienne sauvegarde supprimée',
            'backup_initiated' => 'Sauvegarde initiée',
            'backup_completed' => 'Sauvegarde terminée avec succès',
        ],
        'error' => [
            'backup_failed' => 'Échec de la création de la sauvegarde',
        ],
    ],
    'providers' => [
        'atlauncher' => [
            'warning' => [
                'log1' => 'Échec de la requête API ATLauncher',
            ],
            'error' => [
                'log1' => 'Erreur lors de la récupération des modpacks ATLauncher',
                'log2' => 'Erreur lors de la récupération des versions ATLauncher',
                'log3' => 'Erreur lors de la récupération des détails ATLauncher',
            ],
        ],
        'curseforge' => [
            'warning' => [
                'log1' => 'Clé API CurseForge non configurée - les requêtes échoueront',
                'log2' => 'Échec de la requête API CurseForge',
            ],
            'error' => [
                'log1' => 'La clé API CurseForge est requise mais non configurée',
                'log2' => 'Erreur lors de la récupération des modpacks CurseForge',
                'log3' => 'Erreur lors de la récupération des versions CurseForge',
                'log4' => 'Erreur lors de la récupération des détails CurseForge',
                'log5' => 'Erreur lors de la récupération des infos de téléchargement CurseForge',
            ],
        ],
        'feedthebeast' => [
            'warning' => [
                'log1' => 'Échec de la requête API FTB',
            ],
            'error' => [
                'log1' => 'Erreur lors de la récupération des modpacks FTB',
                'log2' => 'Erreur lors de la récupération des versions FTB',
                'log3' => 'Erreur lors de la récupération des détails FTB',
            ],
        ],
        'modrinth' => [
            'warning' => [
                'log1' => 'Échec de la requête API Modrinth',
            ],
            'error' => [
                'log1' => 'Erreur lors de la récupération des modpacks Modrinth',
                'log2' => 'Erreur lors de la récupération des versions Modrinth',
                'log3' => 'Erreur lors de la récupération des détails Modrinth',
                'log4' => 'Erreur lors de la récupération des infos de téléchargement Modrinth',
            ],
        ],
        'technic' => [
            'warning' => [
                'log1' => 'Échec de la requête API Technic',
            ],
            'error' => [
                'log1' => 'Erreur lors de la récupération des modpacks Technic',
                'log2' => 'Erreur lors de la récupération du build Technic',
                'log3' => 'Erreur lors de la récupération des détails Technic',
            ],
        ],
        'voidswrath' => [
            'warning' => [
                'log1' => 'Échec de la requête JSON VoidsWrath',
                'log2' => 'Modpack VoidsWrath introuvable',
            ],
            'error' => [
                'log1' => 'La réponse VoidsWrath n\'est pas un tableau',
                'log2' => 'Erreur lors de la récupération des modpacks VoidsWrath',
                'log3' => 'Erreur lors de la récupération des détails VoidsWrath',
                'log4' => 'Erreur lors de la récupération des infos de téléchargement VoidsWrath',
            ],
        ],
    ],
    'ui' => [
        'browser' => [
            'title' => 'Navigateur de Modpacks',
            'description' => 'Parcourez et installez des modpacks de différentes plateformes. Utilisez le filtre au-dessus du tableau pour sélectionner un fournisseur.',
            'provider' => 'Fournisseur',
            'updated' => 'Mis à jour',
            'no_versions' => 'Aucune version disponible pour ce modpack.',
            'version' => 'Version',
            'delete_existing' => 'Supprimer les fichiers serveur existants',
            'delete_warning' => 'Attention : Cela supprimera tous les fichiers actuels de votre serveur !',
            'installation_started' => 'Installation commencée',
            'installation_started_message' => 'Le modpack est en cours de téléchargement sur votre serveur.',
            'installation_failed' => 'Installation échouée',
            'installation_failed_message' => 'Impossible de démarrer l\'installation du modpack. Vérifiez les logs.',
            'clear_cache' => 'Vider le cache',
            'cache_cleared' => 'Cache vidé',
            'cache_cleared_message' => 'Le cache des modpacks a été vidé avec succès.',
            'modpacks' => 'Modpacks',
            'installed_modpack_label' => 'Modpack installé : %s %s',
            'unknown' => 'Inconnu',
            'select_version' => 'Sélectionner une version',
            'current_version_marker' => '(Actuel)',
            'debug' => [
                'update_check' => 'Vérification de mise à jour',
            ],
            'error' => [
                'check_updates' => 'Échec de la vérification des mises à jour',
                'not_available' => 'Le navigateur de modpacks n\'est pas disponible pour ce serveur.',
            ],
        ],
        'plugin' => [
            'curseforge_api_key' => 'Clé API CurseForge',
            'curseforge_api_key_help' => 'Requise pour parcourir les modpacks CurseForge. Obtenez votre clé sur console.curseforge.com',
            'cache_duration' => 'Durée du cache (secondes)',
            'request_timeout' => 'Délai d\'attente API (secondes)',
            'modpacks_per_page' => 'Modpacks par page',
            'modpacks_per_page_help' => 'Nombre de modpacks à afficher par page (5-100)',
            'settings_updated' => 'Paramètres mis à jour',
            'settings_updated_message' => 'Les paramètres du plugin ont été enregistrés avec succès.',
        ],
        'providers' => [
            'modrinth' => 'Modrinth',
            'curseforge' => 'CurseForge',
            'atlauncher' => 'ATLauncher',
            'feedthebeast' => 'Feed The Beast',
            'technic' => 'Technic',
            'voidswrath' => 'Voids Wrath',
            'unknown' => 'Inconnu',
        ],
        'common' => [
            'author' => 'Auteur',
            'downloads' => 'Téléchargements',
            'icon' => 'Icône',
            'name' => 'Nom',
            'summary' => 'Résumé',
            'website_url' => 'URL du site web',
            'latest' => 'Dernier',
            'install' => 'Installer',
            'refresh_cache' => 'Actualiser le cache',
        ],
    ],
    'tracking' => [
        'installed_modpack' => 'Installé : :name :version',
        'update_available' => 'Mise à jour disponible',
        'update_available_short' => 'Mise à jour dispo',
        'up_to_date' => 'À jour',
        'update_modpack' => 'Mettre à jour le modpack',
        'current_version' => 'Version actuelle',
        'creating_backup' => 'Création d\'une sauvegarde avant l\'installation...',
        'backup_created' => 'Sauvegarde créée avec succès',
        'backup_error' => 'Échec de la création de la sauvegarde',
        'backup_failed' => 'Sauvegarde échouée',
        'warning' => [
            'invalid_data' => 'Données de suivi du modpack invalides',
            'read_failed' => 'Échec de la lecture du fichier de suivi du modpack',
            'clear_failed' => 'Échec de l\'effacement des données de suivi du modpack',
            'no_put_method' => 'Le dépôt ne supporte pas la méthode putContent',
        ],
        'error' => [
            'save_failed' => 'Échec de l\'enregistrement des données de suivi du modpack',
        ],
        'info' => [
            'cleared' => 'Données de suivi du modpack effacées',
            'saved' => 'Données de suivi du modpack enregistrées',
        ],
    ],
];
