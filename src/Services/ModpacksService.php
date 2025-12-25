<?php

namespace TimVida\MinecraftModpacks\Services;

class ModpacksService
{
    /**
     * Überprüft, ob das Egg das Feature 'modpacks' hat
     * Akzeptiert App\Models\Server oder Pelican\Models\Server
     */
    public static function hasModpacksSupport($server): bool
    {
        // Safely get features from egg (funktioniert mit beiden Server Typen)
        $features = $server?->egg?->features ?? [];
        
        if (is_string($features)) {
            // Falls Features als JSON String gespeichert
            $features = json_decode($features, true) ?? [];
        }
        
        // ⭐ Feature 'modpacks' muss im Egg eingetragen sein!
        return in_array('modpacks', $features);
    }
}