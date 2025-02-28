<?php

namespace RunThingsTaxonomyBasedPassword;

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
        add_submenu_page(
            'options-general.php', // Parent slug
            'Taxonomy-Based Passwords', // Page title
            'Taxonomy Passwords', // Menu title
            $this->config->admin_options_capability, // Capability
            'runthings-taxonomy-based-passwords', // Menu slug
            [$this, 'render_settings_page'] // Callback function
        );
    }

    public function register_settings(): void
    {
        register_setting('runthings_taxonomy_based_passwords', 'runthings_taxonomy_based_passwords_settings');

        add_settings_section(
            'runthings_taxonomy_based_passwords_section',
            'Settings',
            null,
            'runthings-taxonomy-based-passwords'
        );

        add_settings_field(
            'hub_object_id',
            'Hub Page',
            [$this, 'render_hub_object_id_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_section'
        );

        add_settings_field(
            'login_page_id',
            'Login Page',
            [$this, 'render_login_page_id_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_section'
        );

        add_settings_field(
            'objects',
            'Post Types to Protect',
            [$this, 'render_objects_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_section'
        );

        add_settings_field(
            'exempt_roles',
            'Exempt Roles',
            [$this, 'render_exempt_roles_field'],
            'runthings-taxonomy-based-passwords',
            'runthings_taxonomy_based_passwords_section'
        );
    }

    public function render_settings_page(): void
    {
?>
        <div class="wrap">
            <h1>RunThings Taxonomy-Based Passwords</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('runthings_taxonomy_based_passwords');
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
            <option value="0" <?php selected($options['hub_object_id'], 0); ?>><?php _e('Please select', 'runthings-dodd-sculptor-profiles-sitemap'); ?></option>
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
            <option value="0" <?php selected($options['login_page_id'], 0); ?>><?php _e('Please select', 'runthings-dodd-sculptor-profiles-sitemap'); ?></option>
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

        // Sort roles alphabetically by name
        uasort($wp_roles->roles, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
    ?>
        <fieldset>
            <?php
            foreach ($wp_roles->roles as $role_key => $role) {
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
}
