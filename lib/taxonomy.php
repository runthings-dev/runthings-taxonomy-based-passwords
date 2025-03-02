<?php

namespace RunthingsTaxonomyBasedPasswords;

class Taxonomy
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;

        // Register taxonomy
        add_action('init', [$this, 'register_access_group_taxonomy']);
        add_action('add_meta_boxes', [$this, 'add_access_group_meta_boxes']);
        add_action('save_post', [$this, 'save_access_group_meta_box']);

        // Add custom password field to the taxonomy terms
        if (current_user_can(Config::$set_passwords_capability)) {
            add_action("{$this->config->taxonomy}_add_form_fields", [$this, 'add_password_field']);
            add_action("{$this->config->taxonomy}_edit_form_fields", [$this, 'edit_password_field']);
            add_action("created_{$this->config->taxonomy}", [$this, 'save_password_field']);
            add_action("edited_{$this->config->taxonomy}", [$this, 'save_password_field']);
        }
    }

    /**
     * Registers the access_group taxonomy
     */
    public function register_access_group_taxonomy(): void
    {
        $labels = [
            'name'              => _x($this->config->taxonomy_plural, 'taxonomy general name', 'runthings-taxonomy-based-passwords'),
            'singular_name'     => _x($this->config->taxonomy_singular, 'taxonomy singular name', 'runthings-taxonomy-based-passwords'),
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
            'menu_name'         => _x($this->config->taxonomy_plural, 'admin menu', 'runthings-taxonomy-based-passwords'),
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

        echo '<select name="access_group_term" id="access_group_term">';
        echo '<option value="">' . sprintf(
            __('Select %s', 'runthings-taxonomy-based-passwords') /* translators: %s is the singular name of the taxonomy */,
            esc_html($this->config->taxonomy_singular)
        ) . '</option>';
        foreach ($terms as $term) {
            echo '<option value="' . esc_attr($term->term_id) . '" ' . selected($selected_term, $term->term_id, false) . '>' . esc_html($term->name) . '</option>';
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

        $post = get_post($post_id);
        if (!$this->is_child_of_hub_object($post)) {
            return;
        }

        if (isset($_POST['access_group_term'])) {
            $term_id = intval($_POST['access_group_term']);
            if ($term_id) {
                wp_set_post_terms($post_id, [$term_id], $this->config->taxonomy);
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
            <label for="term-password"><?php _e('Password', 'runthings-taxonomy-based-passwords'); ?></label>
            <input type="text" name="term_password" id="term-password" value="" />
            <p class="description"><?php _e('Enter a password to protect items tagged with this term.', 'runthings-taxonomy-based-passwords'); ?></p>
            <?php wp_nonce_field('runthings_taxonomy_based_passwords_add_term', '_wpnonce'); ?>
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
            <th scope="row"><label for="term-password"><?php _e('Password', 'runthings-taxonomy-based-passwords'); ?></label></th>
            <td>
                <input type="text" name="term_password" id="term-password" value="" placeholder="<?php _e('Enter new password', 'runthings-taxonomy-based-passwords'); ?>" />
                <p class="description"><?php _e('Leave blank to keep the existing password.', 'runthings-taxonomy-based-passwords'); ?></p>
                <?php wp_nonce_field('runthings_taxonomy_based_passwords_edit_term', '_wpnonce'); ?>
            </td>
        </tr>
<?php
    }

    /**
     * Saves the password field for the term
     */
    public function save_password_field(int $term_id): void
    {
        if (isset($_POST['_wpnonce']) && (wp_verify_nonce($_POST['_wpnonce'], 'runthings_taxonomy_based_passwords_add_term') || wp_verify_nonce($_POST['_wpnonce'], 'runthings_taxonomy_based_passwords_edit_term'))) {
            if (isset($_POST['term_password'])) {
                $new_password = sanitize_text_field(trim($_POST['term_password']));

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
}
