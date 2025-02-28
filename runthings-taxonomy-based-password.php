<?php

/**
 * Plugin Name: Taxonomy-Based Passwords
 * Plugin URI: https://runthings.dev
 * Description: A plugin to implement password protection based on taxonomy terms.
 * Version: 0.1.0
 * Author: runthingsdev
 * Author URI: https://runthings.dev/
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: runthings-dodd-sculptor-profiles-sitemap
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

namespace RunThingsTaxonomyBasedPassword;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('RUNTHINGS_TAXONOMY_BASED_PASSWORDS_URL', plugin_dir_url(__FILE__));
define('RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR', plugin_dir_path(__FILE__));


require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'config.php';

require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'utils/cookies.php';

require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'lib/taxonomy.php';
require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'lib/protection.php';
require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'lib/authentication.php';
require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'lib/admin-options.php';

class Runthings_Taxonomy_Based_Passwords
{
    private $config;

    public function __construct()
    {
        $this->config = new Config();

        new Taxonomy($this->config);
        new Protection($this->config);
        new Authentication($this->config);
        new AdminOptions($this->config);

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_link']);
    }

    public function add_settings_link($links)
    {
        $settings_link = '<a href="options-general.php?page=runthings-taxonomy-based-passwords">' . __('Settings') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the plugin
new Runthings_Taxonomy_Based_Passwords();
