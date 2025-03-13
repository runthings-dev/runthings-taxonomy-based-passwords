<?php

/**
 * Plugin Name: Taxonomy-Based Passwords
 * Plugin URI: https://runthings.dev
 * Description: A plugin to implement password protection based on taxonomy terms.
 * Version: 0.5.0
 * Author: runthingsdev
 * Author URI: https://runthings.dev/
 * Requires PHP: 7.4
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: runthings-taxonomy-based-passwords
 */
/*
Copyright 2025 Matthew Harris

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace RunthingsTaxonomyBasedPasswords;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('RUNTHINGS_TAXONOMY_BASED_PASSWORDS_VERSION', '0.5.0');
define('RUNTHINGS_TAXONOMY_BASED_PASSWORDS_URL', plugin_dir_url(__FILE__));
define('RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR', plugin_dir_path(__FILE__));

require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'config.php';

require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'utils/cookies.php';

require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'lib/admin-options.php';
require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'lib/archive.php';
require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'lib/authentication.php';
require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'lib/cache.php';
require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'lib/hub.php';
require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'lib/protection.php';
require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'lib/taxonomy.php';

class Runthings_Taxonomy_Based_Passwords
{
    private static ?Runthings_Taxonomy_Based_Passwords $instance = null;
    private Config $config;

    private function __construct()
    {
        $this->config = new Config();

        new AdminOptions($this->config);
        new Archive($this->config);
        new Authentication($this->config);
        new Cache($this->config);
        new Hub($this->config);
        new Protection($this->config);
        new Taxonomy($this->config);

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_link']);
        add_action('admin_notices', [$this, 'check_login_page_set']);
    }

    public static function get_instance(): Runthings_Taxonomy_Based_Passwords
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function add_settings_link(array $links): array
    {
        $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=runthings-taxonomy-based-passwords')) . '">' . esc_html__('Settings', 'runthings-taxonomy-based-passwords') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function check_login_page_set(): void
    {
        if ($this->config->login_page_id === 0) {
            printf(
                '<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p></div>',
                esc_html__('Taxonomy-Based Passwords:', 'runthings-taxonomy-based-passwords'),
                esc_html__('The login page is not set. Content will not be protected until the login page is set up.', 'runthings-taxonomy-based-passwords')
            );
        }
    }

    public static function activate(): void
    {
        $default_settings = [
            'hub_object_id' => 0,
            'login_page_id' => 0,
            'objects' => [],
            'exempt_roles' => ['administrator', 'editor', 'shop_manager'],
            'archive_redirect' => 'hub',
            'taxonomy' => '',
            'taxonomy_slug' => '',
            'taxonomy_singular' => '',
            'taxonomy_plural' => '',
            'delete_data_on_uninstall' => true,
        ];

        if (!get_option('runthings_taxonomy_based_passwords_settings')) {
            update_option('runthings_taxonomy_based_passwords_settings', $default_settings);
        }

        self::add_custom_capabilities();
    }

    public static function uninstall(): void
    {
        $settings = get_option('runthings_taxonomy_based_passwords_settings');
        if (!empty($settings) && (bool) $settings['delete_data_on_uninstall']) {
            delete_option('runthings_taxonomy_based_passwords_settings');
        }
    }

    private static function add_custom_capabilities()
    {
        $roles = ['administrator', 'editor', 'shop_manager'];

        foreach ($roles as $role_name) {
            $role = get_role($role_name);

            if (!$role) {
                continue;
            }

            if (!$role->has_cap(Config::$manage_options_capability)) {
                $role->add_cap(Config::$manage_options_capability);
            }

            if (!$role->has_cap(Config::$set_passwords_capability)) {
                $role->add_cap(Config::$set_passwords_capability);
            }
        }
    }
}

// Initialize the plugin
add_action('plugins_loaded', function () {
    Runthings_Taxonomy_Based_Passwords::get_instance();
});

// Register activation hook
register_activation_hook(__FILE__, ['RunthingsTaxonomyBasedPasswords\Runthings_Taxonomy_Based_Passwords', 'activate']);

// Register uninstall hook
register_uninstall_hook(__FILE__, ['RunthingsTaxonomyBasedPasswords\Runthings_Taxonomy_Based_Passwords', 'uninstall']);
