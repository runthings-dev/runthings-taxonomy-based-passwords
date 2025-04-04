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
            'name' => esc_html($this->config->taxonomy_plural),
            'singular_name' => esc_html($this->config->taxonomy_singular),
            'search_items' => sprintf(
                /* translators: %s is the plural name of the taxonomy */
                __('Search %s', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_plural
            ),
            'all_items' => sprintf(
                /* translators: %s is the plural name of the taxonomy */
                __('All %s', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_plural
            ),
            'parent_item' => sprintf(
                /* translators: %s is the singular name of the taxonomy */
                __('Parent %s', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_singular
            ),
            'parent_item_colon' => sprintf(
                /* translators: %s is the singular name of the taxonomy */
                __('Parent %s:', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_singular
            ),
            'edit_item' => sprintf(
                /* translators: %s is the singular name of the taxonomy */
                __('Edit %s', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_singular
            ),
            'update_item' => sprintf(
                /* translators: %s is the singular name of the taxonomy */
                __('Update %s', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_singular
            ),
            'add_new_item' => sprintf(
                /* translators: %s is the singular name of the taxonomy */
                __('Add New %s', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_singular
            ),
            'new_item_name' => sprintf(
                /* translators: %s is the singular name of the taxonomy */
                __('New %s Name', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_singular
            ),
            'back_to_items' => sprintf(
                /* translators: %s is the plural name of the taxonomy */
                __('Back to %s', 'runthings-taxonomy-based-passwords'),
                $this->config->taxonomy_plural
            ),
            'menu_name' => esc_html($this->config->taxonomy_plural),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_quick_edit' => false,
            'hierarchical' => false,
            'rewrite' => ['slug' => $this->config->taxonomy_slug],
        ];

        $taxonomy_objects = array_merge($this->config->objects, [$this->config->hub_object]);

        register_taxonomy($this->config->taxonomy, $taxonomy_objects, $args);
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
}
