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
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Runthings_Taxonomy_Based_Passwords
{
    /**
     * Hub object that is the parent of the protected pages
     */
    private $hub_object = ['page'];
    private $hub_object_id = 123;

    /**
     * Login page
     */
    private $login_page_id = 6515;

    /**
     * Post types to protect
     */
    private $objects = ['grower-news', 'grower-questions', 'farmer-profiles'];

    public function __construct()
    {
        // taxonomy
        add_action('init', [$this, 'register_grower_contract_taxonomy']);
        add_action('add_meta_boxes', [$this, 'add_grower_contract_meta_box']);
        add_action('save_post', [$this, 'save_grower_contract_meta_box']);

        // protection
        add_action('template_redirect', [$this, 'single_protection']);
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

        $taxonomy_objects = array_merge($this->objects, $this->hub_object);

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

        if (in_array($post_type, $this->objects)) {
            $login_url = get_permalink($this->login_page_id);

            if (!$login_url) {
                $login_url = home_url();
            } else {
                // Add the current URL as a return URL parameter
                global $wp;
                $current_url = home_url(add_query_arg([], $wp->request));
                $login_url = add_query_arg('return', urlencode($current_url), $login_url);
            }

            wp_redirect($login_url);
            exit;
        }
    }
}

// Initialize the plugin
new Runthings_Taxonomy_Based_Passwords();
