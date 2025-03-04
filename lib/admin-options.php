<?php

namespace RunthingsTaxonomyBasedPasswords;

class AdminOptions
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page(): void
    {
        if (current_user_can(Config::$manage_options_capability)) {
            add_submenu_page(
                'options-general.php', // Parent slug
                __('Taxonomy-Based Passwords', 'runthings-taxonomy-based-passwords'), // Page title
                __('Taxonomy Passwords', 'runthings-taxonomy-based-passwords'), // Menu title
                Config::$manage_options_capability, // Capability
                'runthings-taxonomy-based-passwords', // Menu slug
                [$this, 'render_settings_page'] // Callback function
            );
        } else {
            add_menu_page(
                __('Taxonomy-Based Passwords', 'runthings-taxonomy-based-passwords'), // Page title
                __('Taxonomy Passwords', 'runthings-taxonomy-based-passwords'), // Menu title
                Config::$manage_options_capability, // Capability
                'runthings-taxonomy-based-passwords', // Menu slug
                [$this, 'render_settings_page'], // Callback function
                'dashicons-lock' // Icon
            );
        }
    }

    public function register_settings(): void
    {
        register_setting('runthings_taxonomy_based_passwords', 'runthings_taxonomy_based_passwords_settings');

        add_settings_section(
            'runthings_taxonomy_based_passwords_section',
            __('Settings', 'runthings-taxonomy-based-passwords'),
            null,
            'runthings-taxonomy-based-passwords'
        );

        add_settings_field(
            'hub_object_id',
            __('Hub Page', 'runthings-taxonomy-based-passwords'),
            [$this, 'render_hub_object_id_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_section'
        );

        add_settings_field(
            'login_page_id',
            __('Login Page', 'runthings-taxonomy-based-passwords'),
            [$this, 'render_login_page_id_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_section'
        );

        add_settings_field(
            'objects',
            __('Post Types to Protect', 'runthings-taxonomy-based-passwords'),
            [$this, 'render_objects_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_section'
        );

        add_settings_field(
            'exempt_roles',
            __('Exempt Roles', 'runthings-taxonomy-based-passwords'),
            [$this, 'render_exempt_roles_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_section'
        );

        add_settings_field(
            'archive_redirect',
            __('Archive Redirect', 'runthings-taxonomy-based-passwords'),
            [$this, 'render_archive_redirect_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_section'
        );

        add_settings_section(
            'runthings_taxonomy_based_passwords_advanced_section',
            __('Advanced', 'runthings-taxonomy-based-passwords'),
            null,
            'runthings-taxonomy-based-passwords'
        );

        add_settings_field(
            'taxonomy',
            __('Taxonomy ID', 'runthings-taxonomy-based-passwords'),
            [$this, 'render_taxonomy_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_advanced_section'
        );

        add_settings_field(
            'taxonomy_slug',
            __('Taxonomy Slug', 'runthings-taxonomy-based-passwords'),
            [$this, 'render_taxonomy_slug_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_advanced_section'
        );

        add_settings_field(
            'taxonomy_singular',
            __('Taxonomy Singular Name', 'runthings-taxonomy-based-passwords'),
            [$this, 'render_taxonomy_singular_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_advanced_section'
        );

        add_settings_field(
            'taxonomy_plural',
            __('Taxonomy Plural Name', 'runthings-taxonomy-based-passwords'),
            [$this, 'render_taxonomy_plural_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_advanced_section'
        );

        add_settings_field(
            'delete_data_on_uninstall',
            __('Delete All Data on Uninstall', 'runthings-taxonomy-based-passwords'),
            [$this, 'render_delete_data_on_uninstall_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_advanced_section'
        );
    }

    public function render_settings_page(): void
    {
?>
        <div class="wrap">
            <h1><?php _e('Taxonomy-Based Passwords', 'runthings-taxonomy-based-passwords'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('runthings_taxonomy_based_passwords'); // Ensure this matches the option group name
                do_settings_sections('runthings-taxonomy-based-passwords');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    public function render_hub_object_id_field(): void
    {
        $options = get_option('runthings_taxonomy_based_passwords_settings');
    ?>
        <select id="hub_object_id" name="runthings_taxonomy_based_passwords_settings[hub_object_id]" class="select2">
            <option value="0" <?php selected($options['hub_object_id'], 0); ?>><?php _e('Please select', 'runthings-taxonomy-based-passwords'); ?></option>
            <?php
            $pages = get_posts(['post_type' => 'page', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
            foreach ($pages as $page) {
                echo '<option value="' . esc_attr($page->ID) . '" ' . selected($options['hub_object_id'], $page->ID, false) . '>' . esc_html($page->post_title) . '</option>';
            }
            ?>
        </select>
    <?php
    }

    public function render_login_page_id_field(): void
    {
        $options = get_option('runthings_taxonomy_based_passwords_settings');
    ?>
        <select id="login_page_id" name="runthings_taxonomy_based_passwords_settings[login_page_id]" class="select2">
            <option value="0" <?php selected($options['login_page_id'], 0); ?>><?php _e('Please select', 'runthings-taxonomy-based-passwords'); ?></option>
            <?php
            $pages = get_posts(['post_type' => 'page', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
            foreach ($pages as $page) {
                echo '<option value="' . esc_attr($page->ID) . '" ' . selected($options['login_page_id'], $page->ID, false) . '>' . esc_html($page->post_title) . '</option>';
            }
            ?>
        </select>
    <?php
    }

    public function render_objects_field(): void
    {
        $options = get_option('runthings_taxonomy_based_passwords_settings');
        $selected_objects = isset($options['objects']) ? $options['objects'] : [];
        $post_types = get_post_types(['public' => true], 'objects');

        $post_types = array_filter($post_types, function ($post_type) {
            return $post_type->name !== 'page';
        });

        usort($post_types, function ($a, $b) {
            return strcmp($a->label, $b->label);
        });
    ?>
        <fieldset>
            <?php
            foreach ($post_types as $post_type) {
            ?>
                <label>
                    <input type="checkbox" name="runthings_taxonomy_based_passwords_settings[objects][]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $selected_objects)); ?>>
                    <?php echo esc_html($post_type->label); ?>
                </label><br>
            <?php
            }
            ?>
        </fieldset>
    <?php
    }

    public function render_exempt_roles_field(): void
    {
        $options = get_option('runthings_taxonomy_based_passwords_settings');
        $selected_roles = isset($options['exempt_roles']) ? $options['exempt_roles'] : [];

        global $wp_roles;
        $roles = $wp_roles->roles;

        // Sort it alphabetically by name
        uasort($roles, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
    ?>
        <fieldset>
            <?php
            foreach ($roles as $role_key => $role) {
            ?>
                <label>
                    <input type="checkbox" name="runthings_taxonomy_based_passwords_settings[exempt_roles][]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $selected_roles)); ?>>
                    <?php echo esc_html($role['name']); ?>
                </label><br>
            <?php
            }
            ?>
        </fieldset>
    <?php
    }

    public function render_archive_redirect_field(): void
    {
        $options = get_option('runthings_taxonomy_based_passwords_settings');
        $archive_redirect = $options['archive_redirect'] ?? 'hub';
    ?>
        <fieldset>
            <label>
                <input type="radio" name="runthings_taxonomy_based_passwords_settings[archive_redirect]" value="hub" <?php checked($archive_redirect, 'hub'); ?>>
                <?php _e('Redirect to Hub Page', 'runthings-taxonomy-based-passwords'); ?>
            </label><br>
            <label>
                <input type="radio" name="runthings_taxonomy_based_passwords_settings[archive_redirect]" value="home" <?php checked($archive_redirect, 'home'); ?>>
                <?php _e('Redirect to Home Page', 'runthings-taxonomy-based-passwords'); ?>
            </label>
        </fieldset>
    <?php
    }

    public function render_taxonomy_field(): void
    {
        $options = get_option('runthings_taxonomy_based_passwords_settings');
        $taxonomy = $options['taxonomy'] ?? $this->config->taxonomy_default;
    ?>
        <input type="text" id="taxonomy" name="runthings_taxonomy_based_passwords_settings[taxonomy]" value="<?php echo esc_attr($taxonomy); ?>" />
        <p class="description">
            <?php
            /* translators: %s is the default taxonomy ID */
            printf(__('Leave blank for default (%s)', 'runthings-taxonomy-based-passwords'), "<code>" . esc_html($this->config->taxonomy_default) . "</code>");
            ?>
        </p>
    <?php
    }

    public function render_taxonomy_slug_field(): void
    {
        $options = get_option('runthings_taxonomy_based_passwords_settings');
        $taxonomy_slug = $options['taxonomy_slug'] ?? $this->config->taxonomy_slug_default;
    ?>
        <input type="text" id="taxonomy_slug" name="runthings_taxonomy_based_passwords_settings[taxonomy_slug]" value="<?php echo esc_attr($taxonomy_slug); ?>" />
        <p class="description">
            <?php
            /* translators: %s is the default taxonomy slug */
            printf(__('Leave blank for default (%s)', 'runthings-taxonomy-based-passwords'), "<code>" . esc_html($this->config->taxonomy_slug_default) . "</code>");
            ?>
        </p>
    <?php
    }

    public function render_taxonomy_singular_field(): void
    {
        $options = get_option('runthings_taxonomy_based_passwords_settings');
        $taxonomy_singular = $options['taxonomy_singular'] ?? $this->config->taxonomy_singular_default;
    ?>
        <input type="text" id="taxonomy_singular" name="runthings_taxonomy_based_passwords_settings[taxonomy_singular]" value="<?php echo esc_attr($taxonomy_singular); ?>" />
        <p class="description">
            <?php
            /* translators: %s is the default taxonomy singular name */
            printf(__('Leave blank for default (%s)', 'runthings-taxonomy-based-passwords'), "<code>" . esc_html($this->config->taxonomy_singular_default) . "</code>");
            ?>
        </p>
    <?php
    }

    public function render_taxonomy_plural_field(): void
    {
        $options = get_option('runthings_taxonomy_based_passwords_settings');
        $taxonomy_plural = $options['taxonomy_plural'] ?? $this->config->taxonomy_plural_default;
    ?>
        <input type="text" id="taxonomy_plural" name="runthings_taxonomy_based_passwords_settings[taxonomy_plural]" value="<?php echo esc_attr($taxonomy_plural); ?>" />
        <p class="description">
            <?php
            /* translators: %s is the default taxonomy plural name */
            printf(__('Leave blank for default (%s)', 'runthings-taxonomy-based-passwords'), "<code>" . esc_html($this->config->taxonomy_plural_default) . "</code>");
            ?>
        </p>
    <?php
    }

    public function render_delete_data_on_uninstall_field(): void
    {
        $options = get_option('runthings_taxonomy_based_passwords_settings');
        $delete_data_on_uninstall = isset($options['delete_data_on_uninstall']) ? (bool) $options['delete_data_on_uninstall'] : true;
    ?>
        <fieldset>
            <label>
                <input type="checkbox" name="runthings_taxonomy_based_passwords_settings[delete_data_on_uninstall]" value="1" <?php checked($delete_data_on_uninstall, true); ?>>
                <?php _e('Delete all data on uninstall', 'runthings-taxonomy-based-passwords'); ?>
            </label>
        </fieldset>
<?php
    }
}
