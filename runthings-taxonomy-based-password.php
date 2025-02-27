<?php

/**
 * Plugin Name: RunThings Taxonomy-Based Passwords
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

namespace RunThingsTaxonomyBasedPasswords;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('RUNTHINGS_TAXONOMY_BASED_PASSWORDS_URL', plugin_dir_url(__FILE__));
define('RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR', plugin_dir_path(__FILE__));

// Include the configuration file
require_once RUNTHINGS_TAXONOMY_BASED_PASSWORDS_DIR . 'config.php';

class Runthings_Taxonomy_Based_Passwords
{
    private $config;

    public function __construct()
    {
        // Instantiate the config class
        $this->config = new Config();

        // taxonomy
        add_action('init', [$this, 'register_grower_contract_taxonomy']);
        add_action('add_meta_boxes', [$this, 'add_grower_contract_meta_box']);
        add_action('save_post', [$this, 'save_grower_contract_meta_box']);

        // protection
        add_action('template_redirect', [$this, 'single_protection']);

        // shortcode
        add_shortcode('runthings_taxonomy_login_form', [$this, 'render_login_form']);

        // custom authentication
        add_filter('authenticate', [$this, 'custom_authenticate'], 30, 3);
    }

    /**
     * Registers the grower_contract taxonomy
     */
    public function register_grower_contract_taxonomy()
    {
        $labels = [
            'name'              => _x('Grower Contracts', 'taxonomy general name', 'runthings'),
            'singular_name'     => _x('Grower Contract', 'taxonomy singular name', 'runthings'),
            'search_items'      => __('Search Grower Contracts', 'runthings'),
            'all_items'         => __('All Grower Contracts', 'runthings'),
            'parent_item'       => __('Parent Grower Contract', 'runthings'),
            'parent_item_colon' => __('Parent Grower Contract:', 'runthings'),
            'edit_item'         => __('Edit Grower Contract', 'runthings'),
            'update_item'       => __('Update Grower Contract', 'runthings'),
            'add_new_item'      => __('Add New Grower Contract', 'runthings'),
            'new_item_name'     => __('New Grower Contract Name', 'runthings'),
            'menu_name'         => __('Grower Contracts', 'runthings'),
        ];

        $args = [
            'labels'            => $labels,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'hierarchical'      => false,
            'rewrite'           => ['slug' => 'grower-contract'],
        ];

        $taxonomy_objects = array_merge($this->config->objects, $this->config->hub_object);

        register_taxonomy('grower_contract', $taxonomy_objects, $args);
    }

    /**
     * Adds a custom meta box for grower_contract taxonomy
     */
    public function add_grower_contract_meta_box()
    {
        add_meta_box(
            'grower_contract_meta_box',
            __('Grower Contract', 'runthings'),
            [$this, 'render_grower_contract_meta_box'],
            ['farmer-profiles'],
            'side',
            'default'
        );
    }

    /**
     * Renders the grower_contract meta box
     */
    public function render_grower_contract_meta_box($post)
    {
        $terms = get_terms([
            'taxonomy' => 'grower_contract',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
        $selected_term = wp_get_post_terms($post->ID, 'grower_contract', ['fields' => 'ids']);
        $selected_term = !empty($selected_term) ? $selected_term[0] : '';

        echo '<select name="grower_contract_term" id="grower_contract_term">';
        echo '<option value="">' . __('Select Grower Contract', 'runthings') . '</option>';
        foreach ($terms as $term) {
            echo '<option value="' . esc_attr($term->term_id) . '" ' . selected($selected_term, $term->term_id, false) . '>' . esc_html($term->name) . '</option>';
        }
        echo '</select>';
    }

    /**
     * Saves the grower_contract meta box selection
     */
    public function save_grower_contract_meta_box($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['grower_contract_term'])) {
            $term_id = intval($_POST['grower_contract_term']);
            wp_set_post_terms($post_id, [$term_id], 'grower_contract');
        }
    }

    /**
     * Protect single pages
     */
    public function single_protection()
    {
        if (!is_singular() || is_admin()) {
            return;
        }

        $post_type = get_post_type();

        if (in_array($post_type, $this->config->objects)) {
            $login_url = get_permalink($this->config->login_page_id);

            if ($login_url) {
                // Add the current URL as a return URL parameter
                global $wp;
                $current_url = home_url(add_query_arg([], $wp->request));
                $login_url = add_query_arg('return', urlencode($current_url), $login_url);
            } else {
                // Fallback url
                $login_url = home_url();
            }

            wp_redirect($login_url);
            exit;
        }
    }

    /**
     * Custom authentication function
     */
    public function custom_authenticate($user, $username, $password)
    {
        if (isset($_POST['custom_form']) && $_POST['custom_form'] === 'runthings_taxonomy_login_form') {
            if (isset($_POST['post_password']) && isset($_POST['return_url'])) {
                $password = $_POST['post_password'];
                $return_url = esc_url_raw($_POST['return_url']);
                $original_post_id = url_to_postid($return_url);

                if ($original_post_id) {
                    $original_post = get_post($original_post_id);
                    $terms = wp_get_post_terms($original_post_id, 'grower_contract', ['fields' => 'names']);
                    $valid_password = !empty($terms) ? $terms[0] : '';

                    if ($original_post && $valid_password === $password) {
                        // Password is correct, set the cookie and redirect to the original post
                        global $wp_hasher;
                        if (empty($wp_hasher)) {
                            require_once ABSPATH . 'wp-includes/class-phpass.php';
                            $wp_hasher = new \PasswordHash(8, true);
                        }
                        setcookie('wp-postpass_' . COOKIEHASH, $wp_hasher->HashPassword($password), time() + 864000, COOKIEPATH);
                        wp_redirect($return_url);
                        exit;
                    } else {
                        // Password is incorrect, redirect back to the login page with an error
                        $login_url = add_query_arg('error', 'incorrect_password', get_permalink($this->config->login_page_id));
                        wp_redirect($login_url);
                        exit;
                    }
                }
            }
        }

        return $user;
    }

    /**
     * Renders the login form using the built-in WordPress password form
     */
    public function render_login_form($atts)
    {
        global $post;

        // Use a temporary post object to generate the form
        $temp_post = new \stdClass();
        $temp_post->ID = 0;
        $temp_post->post_password = '';

        // Generate the password form
        $form = get_the_password_form($temp_post);

        // Customize the form if needed
        $form = str_replace(
            'This content is password protected.',
            'This content is restricted to contracted growers. Please enter your password to view it.',
            $form
        );

        // Add a hidden field for the return URL and custom form identifier
        if (isset($_GET['return'])) {
            $return_url = esc_url($_GET['return']);
            $hidden_fields = '<input type="hidden" name="return_url" value="' . $return_url . '">';
            $hidden_fields .= '<input type="hidden" name="custom_form" value="runthings_taxonomy_login_form">';
            $form = str_replace('</form>', $hidden_fields . '</form>', $form);
        }

        return $form;
    }
}

// Initialize the plugin
new Runthings_Taxonomy_Based_Passwords();
