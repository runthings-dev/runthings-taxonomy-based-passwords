<?php

namespace RunthingsTaxonomyBasedPasswords;

class Authentication
{
    private Config $config;
    private Cookies $cookies;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->cookies = new Cookies();

        add_shortcode($this->config->shortcode_login_form, [$this, 'render_login_form']);
        add_shortcode($this->config->shortcode_logout, [$this, 'render_logout_link']);

        add_action('init', [$this, 'handle_form_submission']);
        add_action('init', [$this, 'handle_logout']);
    }

    /**
     * Handle form submission
     */
    public function handle_form_submission(): void
    {
        if (!isset($_POST['runthings_taxonomy_based_password_form']) || $_POST['runthings_taxonomy_based_password_form'] !== 'login_form') {
            return;
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash(($_POST['_wpnonce']))), 'runthings_taxonomy_based_passwords_login_form')) {
            return;
        }

        if (!isset($_POST['post_password']) || !isset($_POST['return_url']) || !isset($_POST['original_post_id'])) {
            return;
        }

        $input_password = sanitize_text_field(wp_unslash($_POST['post_password']));
        $return_url = esc_url_raw(wp_unslash($_POST['return_url']));
        $original_post_id = intval($_POST['original_post_id']);

        if (!$original_post_id || trim($return_url) === '') {
            return;
        }

        $term_id = $this->get_term_id($original_post_id);
        $stored_hashed_password = $this->get_valid_password($term_id);

        if ($this->verify_password($input_password, $stored_hashed_password)) {
            // Password is correct
            $this->cookies->set_cookie($stored_hashed_password, $term_id);
            wp_safe_redirect($return_url);
            exit;
        }

        // Password is incorrect, add error query parameter
        $destination_url = home_url();
        if ($this->config->login_page_id !== 0) {
            $destination_url = get_permalink($this->config->login_page_id);
            $destination_url = add_query_arg([
                'error' => 'incorrect_password',
                'return_url' => urlencode($return_url),
                'original_post_id' => $original_post_id
            ], $destination_url);

            $destination_url = wp_nonce_url($destination_url, 'runthings_taxonomy_based_passwords_login_form');
        }

        wp_safe_redirect($destination_url);
        exit;
    }

    /**
     * Handle logout
     */
    public function handle_logout(): void
    {
        if (isset($_GET['runthings_taxonomy_logout'])) {
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'runthings_taxonomy_based_passwords_logout')) {
                wp_safe_redirect(home_url('?logout_error=invalid_nonce'));
                exit;
            }

            $this->cookies->clear_cookie();
            wp_safe_redirect(home_url());
            exit;
        }
    }

    /**
     * Renders the login form using custom markup similar to the built-in WordPress password form
     */
    public function render_login_form(array $atts): string
    {
        wp_enqueue_style('runthings-taxonomy-based-passwords-styles', RUNTHINGS_TAXONOMY_BASED_PASSWORDS_URL . 'assets/css/styles.css', [], RUNTHINGS_TAXONOMY_BASED_PASSWORDS_VERSION);

        $field_id = 'pwbox-' . wp_rand();
        $invalid_password = esc_html__('The password you entered is incorrect.', 'runthings-taxonomy-based-passwords');
        $invalid_password_html = '';
        $aria = '';
        $class = '';

        // If any values posted, check the nonce before parsing
        if (isset($_REQUEST['error']) || isset($_GET['return_url']) || isset($_GET['original_post_id'])) {
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'runthings_taxonomy_based_passwords_login_form')) {
                $invalid_password_html = '<div class="runthings-taxonomy-based-passwords-error" role="alert"><p id="error-' . esc_attr($field_id) . '">' . esc_html__('Session expired. Please go back and try again.', 'runthings-taxonomy-based-passwords') . '</p></div>';

                return $invalid_password_html;
            }
        }

        // // Set up incorrect password error message if present
        if (isset($_REQUEST['error']) && sanitize_text_field(wp_unslash($_REQUEST['error'])) === 'incorrect_password') {
            $invalid_password_html = '<div class="runthings-taxonomy-based-passwords-error" role="alert"><p id="error-' . esc_attr($field_id) . '">' . $invalid_password . '</p></div>';
            $class = ' password-form-error';
            $aria = ' aria-describedby="error-' . esc_attr($field_id) . '"';
        }

        $output = '<form method="post" class="post-password-form runthings-taxonomy-based-passwords-login-form' . esc_attr($class) . '"> ';
        $output .= '<p>' . esc_html__('This content is restricted. Please enter your password to view it.', 'runthings-taxonomy-based-passwords') . '</p>';
        $output .= $invalid_password_html;
        $output .= '<p><label for="' . esc_attr($field_id) . '">' . esc_html__('Password:', 'runthings-taxonomy-based-passwords') . ' <input name="post_password" id="' . esc_attr($field_id) . '" type="password" spellcheck="false" required size="20"' . $aria . ' /></label>';
        $output .= '<input type="submit" name="Submit" value="' . esc_attr__('Enter', 'runthings-taxonomy-based-passwords') . '" /></p>';

        // Add hidden fields for return URL and form type
        if (isset($_GET['return_url'])) {
            $return_url = esc_url(sanitize_text_field(wp_unslash($_GET['return_url'])));
            $output .= '<input type="hidden" name="return_url" value="' . esc_attr($return_url) . '">';
        }

        if (isset($_GET['original_post_id'])) {
            $post_id = intval(wp_unslash($_GET['original_post_id']));
            $output .= '<input type="hidden" name="original_post_id" value="' . esc_attr($post_id) . '">';
        }

        $output .= '<input type="hidden" name="runthings_taxonomy_based_password_form" value="login_form">';
        $output .= wp_nonce_field('runthings_taxonomy_based_passwords_login_form', '_wpnonce', true, false);

        $output .= '</form>';

        return $output;
    }

    /**
     * Renders the logout link if the user is logged in
     */
    public function render_logout_link(array $atts): string
    {
        if ($this->cookies->is_logged_in()) {
            $logout_url = add_query_arg('runthings_taxonomy_logout', 'true', home_url());
            $logout_url = wp_nonce_url($logout_url, 'runthings_taxonomy_based_passwords_logout');
            return '<a href="' . esc_url($logout_url) . '">' . __('Log Out', 'runthings-taxonomy-based-passwords') . '</a>';
        }

        return '';
    }

    /**
     * Get the term ID for a post based on the attached taxonomy term
     */
    private function get_term_id(int $post_id): int
    {
        $terms = wp_get_post_terms($post_id, $this->config->taxonomy, ['fields' => 'ids']);

        if (is_wp_error($terms) || empty($terms)) {
            return 0;
        }

        return $terms[0];
    }

    /**
     * Get the valid password for a post based on the attached taxonomy term
     */
    private function get_valid_password(int $term_id): string
    {
        if (!$term_id) {
            return '';
        }

        $hashed_password = get_term_meta($term_id, 'runthings_taxonomy_password', true);

        return $hashed_password;
    }

    /**
     * Verifies a password against the stored hash
     */
    public function verify_password(string $input_password, string $stored_hashed_password): bool
    {
        if (!$stored_hashed_password) {
            return false; // No password stored
        }

        return password_verify($input_password, $stored_hashed_password);
    }
}
