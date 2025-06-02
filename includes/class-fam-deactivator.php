<?php

class FAM_Deactivator {
    public static function deactivate() {
        // Clear any scheduled events
        wp_clear_scheduled_hook('fam_cleanup_expired_tokens');
        
        // Clear any transients
        delete_transient('fam_archive_cache');
    }
} 