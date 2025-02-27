<?php

namespace RunThingsTaxonomyBasedPassword;

class Taxonomy
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;

        // Register taxonomy
        add_action('init', [$this, 'register_grower_contract_taxonomy']);
        add_action('add_meta_boxes', [$this, 'add_grower_contract_meta_box']);
        add_action('save_post', [$this, 'save_grower_contract_meta_box']);
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
}
