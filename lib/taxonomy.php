<?php

namespace RunthingsTaxonomyBasedPasswords;

class Taxonomy
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;

        $init_priority = 5; // To ensure taxonomy exists before form processing in \Authentication

        // Register taxonomy
        add_action('init', [$this, 'register_access_group_taxonomy'], $init_priority);
        add_action('add_meta_boxes', [$this, 'add_access_group_meta_boxes']);
        add_action('save_post', [$this, 'save_access_group_meta_box']);

        // Add custom password field to the taxonomy terms
        if (current_user_can(Config::$set_passwords_capability)) {
            add_action("{$this->config->taxonomy}_add_form_fields", [$this, 'add_password_field']);
            add_action("{$this->config->taxonomy}_edit_form_fields", [$this, 'edit_password_field']);
            add_action("created_{$this->config->taxonomy}", [$this, 'save_password_field']);
            add_action("edited_{$this->config->taxonomy}", [$this, 'save_password_field']);
        }

        // Customize admin columns titles
        foreach ($this->config->objects as $post_type) {
            add_filter("manage_edit-{$post_type}_columns", [$this, 'edit_taxonomy_column_title']);
        }
        add_filter("manage_edit-{$this->config->hub_object}_columns", [$this, 'edit_taxonomy_column_title']);
    }

    /**
     * Registers the access_group taxonomy
     */
    public function register_access_group_taxonomy(): void
    {
        $labels = [
            'name'              => esc_html($this->config->taxonomy_plural),
            'singular_name'     => esc_html($this->config->taxonomy_singular),
            'search_items'      => sprintf(
                /* translators: %s is the plural name of the taxonomy */
                __('Search %s', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_plural
            ),
            'all_items'         => sprintf(
                /* translators: %s is the plural name of the taxonomy */
                __('All %s', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_plural
            ),
            'parent_item'       => sprintf(
                /* translators: %s is the singular name of the taxonomy */
                __('Parent %s', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_singular
            ),
            'parent_item_colon' => sprintf(
                /* translators: %s is the singular name of the taxonomy */
                __('Parent %s:', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_singular
            ),
            'edit_item'         => sprintf(
                /* translators: %s is the singular name of the taxonomy */
                __('Edit %s', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_singular
            ),
            'update_item'       => sprintf(
                /* translators: %s is the singular name of the taxonomy */
                __('Update %s', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_singular
            ),
            'add_new_item'      => sprintf(
                /* translators: %s is the singular name of the taxonomy */
                __('Add New %s', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_singular
            ),
            'new_item_name'     => sprintf(
                /* translators: %s is the singular name of the taxonomy */
                __('New %s Name', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_singular
            ),
            'menu_name'         => esc_html($this->config->taxonomy_plural),
        ];

        $args = [
            'labels'            => $labels,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'hierarchical'      => false,
            'rewrite'           => ['slug' => $this->config->taxonomy_slug],
        ];

        $taxonomy_objects = array_merge($this->config->objects, [$this->config->hub_object]);

        register_taxonomy($this->config->taxonomy, $taxonomy_objects, $args);
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
     * Adds a password field to the add term form
     */
    public function add_password_field(): void
    {
?>
        <div class="form-field term-password-wrap">
            <label for="term-password"><?php esc_html_e('Password', 'runthings-taxonomy-based-passwords'); ?></label>
            <input type="text" name="term_password" id="term-password" value="" />
            <p class="description"><?php esc_html_e('Enter a password to protect items tagged with this term.', 'runthings-taxonomy-based-passwords'); ?></p>
            <?php wp_nonce_field('add_password_term', 'runthings_tbp_add_password_term_nonce'); ?>
        </div>
    <?php
    }

    /**
     * Adds a password field to the edit term form
     */
    public function edit_password_field(\WP_Term $term): void
    {
    ?>
        <tr class="form-field term-password-wrap">
            <th scope="row"><label for="term-password"><?php esc_html_e('Password', 'runthings-taxonomy-based-passwords'); ?></label></th>
            <td>
                <input type="text" name="term_password" id="term-password" value="" placeholder="<?php esc_html_e('Enter new password', 'runthings-taxonomy-based-passwords'); ?>" />
                <p class="description"><?php esc_html_e('Leave blank to keep the existing password.', 'runthings-taxonomy-based-passwords'); ?></p>
                <?php wp_nonce_field('edit_password_term', 'runthings_tbp_edit_password_term_nonce'); ?>
            </td>
        </tr>
<?php
    }

    /**
     * Saves the password field for the term
     */
    public function save_password_field(int $term_id): void
    {
        if (
            isset($_POST['runthings_tbp_add_password_term_nonce']) &&
            wp_verify_nonce(
                sanitize_key(wp_unslash($_POST['runthings_tbp_add_password_term_nonce'])),
                'add_password_term'
            ) ||
            isset($_POST['runthings_tbp_edit_password_term_nonce']) &&
            wp_verify_nonce(
                sanitize_key(wp_unslash($_POST['runthings_tbp_edit_password_term_nonce'])),
                'edit_password_term'
            )
        ) {
            if (isset($_POST['term_password'])) {
                $new_password = sanitize_text_field(wp_unslash($_POST['term_password']));

                // Get existing hashed password
                $existing_hashed_password = get_term_meta($term_id, 'runthings_taxonomy_password', true);

                // Only update if password is non-empty and different from the stored hash
                if (!empty($new_password) && !password_verify($new_password, $existing_hashed_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    update_term_meta($term_id, 'runthings_taxonomy_password', $hashed_password);
                }
            }
        }
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
