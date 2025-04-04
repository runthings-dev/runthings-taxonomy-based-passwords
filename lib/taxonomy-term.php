<?php

namespace RunthingsTaxonomyBasedPasswords;

class TaxonomyTerm
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;

        // Actions for meta boxes
        add_action('add_meta_boxes', [$this, 'add_access_group_meta_boxes']);
        add_action('save_post', [$this, 'save_access_group_meta_box']);

        // Customize admin columns titles
        foreach ($this->config->objects as $post_type) {
            add_filter("manage_edit-{$post_type}_columns", [$this, 'edit_taxonomy_column_title']);
        }
        add_filter("manage_edit-{$this->config->hub_object}_columns", [$this, 'edit_taxonomy_column_title']);
    }

    /**
     * Adds a custom meta box for access_group taxonomy
     */
    public function add_access_group_meta_boxes(): void
    {
        foreach ($this->config->objects as $post_type) {
            $this->add_access_group_meta_box($post_type);
        }

        if (get_post_type() === $this->config->hub_object) {
            global $post;

            if (!$this->is_child_of_hub_object($post)) {
                return;
            }

            $this->add_access_group_meta_box($this->config->hub_object);
        }
    }

    /**
     * Adds the custom meta box
     */
    public function add_access_group_meta_box(string $post_type): void
    {
        add_meta_box(
            'runthings_tbp_access_group_meta_box',
            $this->config->taxonomy_singular,
            [$this, 'render_access_group_meta_box'],
            $post_type,
            'side',
            'default'
        );
    }

    /**
     * Renders the access_group meta box
     */
    public function render_access_group_meta_box(\WP_Post $post): void
    {
        $terms = get_terms([
            'taxonomy' => $this->config->taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);

        $selected_term = wp_get_post_terms($post->ID, $this->config->taxonomy, ['fields' => 'ids']);
        $selected_term = !empty($selected_term) ? $selected_term[0] : '';

        wp_nonce_field('save_access_group_term', 'runthings_tbp_access_group_term_nonce');

        echo '<select name="access_group_term" id="access_group_term">';
        printf(
            '<option value="">%s</option>',
            sprintf(
                /* translators: %s is the singular name of the taxonomy */
                esc_html__('Select %s', 'runthings-taxonomy-based-passwords'),
                esc_html($this->config->taxonomy_singular)
            )
        );

        foreach ($terms as $term) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($term->term_id),
                selected($selected_term, $term->term_id, false),
                esc_html($term->name)
            );
        }
        echo '</select>';
    }

    /**
     * Saves the access_group meta box selection
     */
    public function save_access_group_meta_box(int $post_id): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST['runthings_tbp_access_group_term_nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['runthings_tbp_access_group_term_nonce'])), 'save_access_group_term')) {
            return;
        }

        $post = get_post($post_id);
        if ($post->post_type === $this->config->hub_object && !$this->is_child_of_hub_object($post)) {
            return;
        }

        if (isset($_POST['access_group_term'])) {
            $term_id = intval(wp_unslash($_POST['access_group_term']));
            if ($term_id) {
                wp_set_post_terms($post_id, [$term_id], $this->config->taxonomy);
            } else {
                wp_set_post_terms($post_id, [], $this->config->taxonomy);
            }
        }
    }

    private function is_child_of_hub_object(\WP_Post $post): bool
    {
        if (!$this->config->hub_object_id) {
            return false;
        }

        return $post->post_type === $this->config->hub_object && $post->post_parent == $this->config->hub_object_id;
    }

    /**
     * Edit title for the taxonomy column
     */
    public function edit_taxonomy_column_title(array $columns): array
    {
        if (isset($columns["taxonomy-{$this->config->taxonomy}"])) {
            $columns["taxonomy-{$this->config->taxonomy}"] = esc_html($this->config->taxonomy_singular);
        }
        return $columns;
    }
}
