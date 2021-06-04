<?php

namespace Geniem\WPSecurityPlugin;

class WPSecurityPlugin {
    private $server_url = 'https://dashboard.production.geniem.io/api.php?skip_cache=1';

    private $versions = [
        'core' => null,
        'plugins' => []
    ];

    private $site_url;

    function __construct() {
        $this->hook();
    }

    private function hook() {
        if ( ! wp_next_scheduled( 'geniem_wp_security_plugin_scan' ) ) {
            # Sometimes two request trigger at the same time and this code is run twice
            # generating two hooks. The latter request should clear the first schedule.
            wp_unschedule_hook('geniem_wp_security_plugin_scan');
            wp_schedule_event( time(), 'twicedaily', 'geniem_wp_security_plugin_scan' );
        }

        add_action( 'geniem_wp_security_plugin_scan', [ $this, 'execute' ] );
    }

    public function execute() {
        $this->clear_duplicate_cron();

        if ( getenv( 'WP_ENV' ) == 'production' ) {
            $this->store_site_url();
            $this->store_core_version();
            $this->store_plugin_versions();
            $this->send_data();
        }
    }

    private function clear_duplicate_cron() {
        if ( substr_count( json_encode( _get_cron_array() ), 'geniem_wp_security_plugin_scan' ) > 1 ) {
            wp_unschedule_hook('geniem_wp_security_plugin_scan');
        }
    }

    private function store_site_url() {
        $this->site_url = get_site_url();
    }

    private function store_core_version() {
        global $wp_version;

        $this->versions['core'] = $wp_version;
    }

    private function store_plugin_versions() {
        $plugins = get_plugins();

        if ( ! empty( $plugins ) ) {
            foreach ( $plugins as $path => $plugin ) {
                $this->versions['plugins'][ $this->get_plugin_name( $path ) ] = $plugin['Version'];
            }
        }
    }

    private function get_plugin_name( $path ) {
        return pathinfo( $path )['dirname'];
    }

    private function send_data() {
        if ( ! empty( $this->versions['core'] ) &&
             ! empty( $this->versions['plugins'] ) &&
             ! empty( $this->site_url )
        ) {
            $data = [
                'action' => 'report_versions',
                'site_url' => $this->site_url,
                'versions_json' => json_encode( $this->versions ),
                'api_key' => getenv( 'GENIEM_WP_SECURITY_PLUGIN_API_KEY' )
            ];

            $ch = curl_init( $this->server_url );

            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

            $result = curl_exec( $ch );
        }
    }
}
