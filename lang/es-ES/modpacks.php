<?php

return [
    'installer' => [
        'unknown_modpack' => 'Modpack Desconocido',
        'unknown' => 'Desconocido',
        'backup' => [
            'pre_installation' => 'Pre-Instalación-Modpack',
            'before_update' => 'Antes de Actualización: :modpack :version',
        ],
        'debug' => [
            'backup_name_generated' => 'Nombre de respaldo generado',
            'backup_limit_check' => 'Comprobación de límite de respaldos',
            'waiting_backup' => 'Esperando a que el respaldo se complete',
        ],
        'warning' => [
            'backup_name_failed' => 'Error al generar nombre de respaldo',
            'delete_backup_failed' => 'Error al eliminar respaldo antiguo',
            'check_delete_backups_failed' => 'Error al comprobar/eliminar respaldos antiguos',
            'backup_completed_failed' => 'Respaldo completado pero fallido',
            'backup_timeout' => 'Tiempo de espera de respaldo agotado - procediendo de todas formas',
            'backup_null' => 'El servicio de respaldo devolvió null',
        ],
        'info' => [
            'creating_backup' => 'Creando respaldo antes de la instalación del modpack',
            'backup_limit_reached' => 'Límite de respaldos alcanzado, eliminando los más antiguos',
            'deleted_old_backup' => 'Respaldo antiguo eliminado',
            'backup_initiated' => 'Respaldo iniciado',
            'backup_completed' => 'Respaldo completado con éxito',
        ],
        'error' => [
            'backup_failed' => 'Error al crear respaldo',
        ],
    ],
    'providers' => [
        'atlauncher' => [
            'warning' => [
                'log1' => 'Solicitud API de ATLauncher fallida',
            ],
            'error' => [
                'log1' => 'Error obteniendo modpacks de ATLauncher',
                'log2' => 'Error obteniendo versiones de ATLauncher',
                'log3' => 'Error obteniendo detalles de ATLauncher',
            ],
        ],
        'curseforge' => [
            'warning' => [
                'log1' => 'Clave API de CurseForge no configurada - las solicitudes fallarán',
                'log2' => 'Solicitud API de CurseForge fallida',
            ],
            'error' => [
                'log1' => 'La clave API de CurseForge es requerida pero no está configurada',
                'log2' => 'Error obteniendo modpacks de CurseForge',
                'log3' => 'Error obteniendo versiones de CurseForge',
                'log4' => 'Error obteniendo detalles de CurseForge',
                'log5' => 'Error obteniendo info de descarga de CurseForge',
            ],
        ],
        'feedthebeast' => [
            'warning' => [
                'log1' => 'Solicitud API de FTB fallida',
            ],
            'error' => [
                'log1' => 'Error obteniendo modpacks de FTB',
                'log2' => 'Error obteniendo versiones de FTB',
                'log3' => 'Error obteniendo detalles de FTB',
            ],
        ],
        'modrinth' => [
            'warning' => [
                'log1' => 'Solicitud API de Modrinth fallida',
            ],
            'error' => [
                'log1' => 'Error obteniendo modpacks de Modrinth',
                'log2' => 'Error obteniendo versiones de Modrinth',
                'log3' => 'Error obteniendo detalles de Modrinth',
                'log4' => 'Error obteniendo info de descarga de Modrinth',
            ],
        ],
        'technic' => [
            'warning' => [
                'log1' => 'Solicitud API de Technic fallida',
            ],
            'error' => [
                'log1' => 'Error obteniendo modpacks de Technic',
                'log2' => 'Error obteniendo build de Technic',
                'log3' => 'Error obteniendo detalles de Technic',
            ],
        ],
        'voidswrath' => [
            'warning' => [
                'log1' => 'Solicitud JSON de VoidsWrath fallida',
                'log2' => 'Modpack de VoidsWrath no encontrado',
            ],
            'error' => [
                'log1' => 'La respuesta de VoidsWrath no es un array',
                'log2' => 'Error obteniendo modpacks de VoidsWrath',
                'log3' => 'Error obteniendo detalles de VoidsWrath',
                'log4' => 'Error obteniendo info de descarga de VoidsWrath',
            ],
        ],
    ],
    'ui' => [
        'browser' => [
            'title' => 'Navegador de Modpacks',
            'description' => 'Navega e instala modpacks de varias plataformas. Usa el filtro sobre la tabla para seleccionar un proveedor.',
            'provider' => 'Proveedor',
            'updated' => 'Actualizado',
            'no_versions' => 'No hay versiones disponibles para este modpack.',
            'version' => 'Versión',
            'delete_existing' => 'Eliminar archivos existentes del servidor',
            'delete_warning' => 'Advertencia: ¡Esto eliminará todos los archivos actuales de tu servidor!',
            'installation_started' => 'Instalación Iniciada',
            'installation_started_message' => 'El modpack se está descargando en tu servidor.',
            'installation_failed' => 'Instalación Fallida',
            'installation_failed_message' => 'No se pudo iniciar la instalación del modpack. Revisa los logs para más detalles.',
            'clear_cache' => 'Limpiar Caché',
            'cache_cleared' => 'Caché Limpia',
            'cache_cleared_message' => 'La caché de modpacks ha sido limpiada exitosamente.',
            'modpacks' => 'Modpacks',
            'installed_modpack_label' => 'Modpack Instalado: %s %s',
            'unknown' => 'Desconocido',
            'select_version' => 'Seleccionar Versión',
            'current_version_marker' => '(Actual)',
            'debug' => [
                'update_check' => 'Comprobación de actualización',
            ],
            'error' => [
                'check_updates' => 'Error al buscar actualizaciones',
                'not_available' => 'El Navegador de Modpacks no está disponible para este servidor.',
            ],
        ],
        'plugin' => [
            'curseforge_api_key' => 'Clave API de CurseForge',
            'curseforge_api_key_help' => 'Requerida para navegar modpacks de CurseForge. Obtén tu clave en console.curseforge.com',
            'cache_duration' => 'Duración de Caché (segundos)',
            'request_timeout' => 'Tiempo de espera de solicitud API (segundos)',
            'modpacks_per_page' => 'Modpacks por Página',
            'modpacks_per_page_help' => 'Número de modpacks a mostrar por página (5-100)',
            'settings_updated' => 'Ajustes Actualizados',
            'settings_updated_message' => 'Los ajustes del plugin han sido guardados exitosamente.',
        ],
        'providers' => [
            'modrinth' => 'Modrinth',
            'curseforge' => 'CurseForge',
            'atlauncher' => 'ATLauncher',
            'feedthebeast' => 'Feed The Beast',
            'technic' => 'Technic',
            'voidswrath' => 'Voids Wrath',
            'unknown' => 'Desconocido',
        ],
        'common' => [
            'author' => 'Autor',
            'downloads' => 'Descargas',
            'icon' => 'Icono',
            'name' => 'Nombre',
            'summary' => 'Resumen',
            'website_url' => 'URL del sitio web',
            'latest' => 'Último',
            'install' => 'Instalar',
            'refresh_cache' => 'Refrescar Caché',
        ],
    ],
    'tracking' => [
        'installed_modpack' => 'Instalado: :name :version',
        'update_available' => 'Actualización Disponible',
        'update_available_short' => 'Actualización Disp.',
        'up_to_date' => 'Actualizado',
        'update_modpack' => 'Actualizar Modpack',
        'current_version' => 'Versión Actual',
        'creating_backup' => 'Creando copia de seguridad antes de la instalación...',
        'backup_created' => 'Copia de seguridad creada con éxito',
        'backup_error' => 'Error al crear copia de seguridad',
        'backup_failed' => 'Copia de seguridad fallida',
        'warning' => [
            'invalid_data' => 'Datos de seguimiento de modpack inválidos',
            'read_failed' => 'Error al leer archivo de seguimiento de modpack',
            'clear_failed' => 'Error al limpiar datos de seguimiento de modpack',
            'no_put_method' => 'El repositorio no soporta el método putContent',
        ],
        'error' => [
            'save_failed' => 'Error al guardar datos de seguimiento de modpack',
        ],
        'info' => [
            'cleared' => 'Datos de seguimiento de modpack limpiados',
            'saved' => 'Datos de seguimiento de modpack guardados',
        ],
    ],
];
