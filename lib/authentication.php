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

        // Add shortcodes
        add_shortcode('runthings_taxonomy_login_form', [$this, 'render_login_form']);
        add_shortcode('runthings_taxonomy_logout', [$this, 'render_logout_link']);

        // Handle form submission
        add_action('init', [$this, 'handle_form_submission']);

        // Handle logout
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

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'runthings_taxonomy_based_passwords_login_form')) {
            return;
        }

        if (!isset($_POST['post_password']) || !isset($_POST['return_url']) || !isset($_POST['original_post_id'])) {
            return;
        }

        $input_password = sanitize_text_field($_POST['post_password']);
        $return_url = esc_url_raw($_POST['return_url']);
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
        $login_url = add_query_arg([
            'error' => 'incorrect_password',
            'return_url' => urlencode($return_url),
            'original_post_id' => $original_post_id
            // Caution: login_page_id may be 0. Currently the form only submits from the login page
            // so this should not be an issue, but if its used elsewhere, it may need to be updated
        ], get_permalink($this->config->login_page_id));
        wp_safe_redirect($login_url);
        exit;
    }

    /**
     * Handle logout
     */
    public function handle_logout(): void
    {
        if (isset($_GET['runthings_taxonomy_logout'])) {
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
        wp_enqueue_style('runthings-taxonomy-based-passwords-styles', RUNTHINGS_TAXONOMY_BASED_PASSWORDS_URL . 'assets/css/styles.css');

        $field_id = 'pwbox-' . rand();
        $invalid_password = __('The password you entered is incorrect.', 'runthings-taxonomy-based-passwords');
        $invalid_password_html = '';
        $aria = '';
        $class = '';

        // Set Up error message if present
        if (isset($_REQUEST['error']) && $_REQUEST['error'] === 'incorrect_password') {
            $invalid_password_html = '<div class="runthings-taxonomy-based-passwords-error" role="alert"><p id="error-' . $field_id . '">' . $invalid_password . '</p></div>';
            $class = ' password-form-error';
            $aria = ' aria-describedby="error-' . $field_id . '"';
        }

        $form = '<form method="post" class="post-password-form runthings-taxonomy-based-passwords-login-form' . $class . '"> ';
        $form .= '<p>' . __('This content is restricted. Please enter your password to view it.', 'runthings-taxonomy-based-passwords') . '</p>';
        $form .= $invalid_password_html;
        $form .= '<p><label for="' . $field_id . '">' . __('Password:', 'runthings-taxonomy-based-passwords') . ' <input name="post_password" id="' . $field_id . '" type="password" spellcheck="false" required  size="20" ' . $aria . ' /></label>';
        $form .= '<input type="submit" name="Submit" value="' . esc_attr__('Enter', 'runthings-taxonomy-based-passwords') . '" /></p>';

        // Add hidden fields for return URL and form type
        if (isset($_GET['return_url'])) {
            $return_url = esc_url($_GET['return_url']);
            $form .= '<input type="hidden" name="return_url" value="' . $return_url . '">';
        }

        if (isset($_GET['original_post_id'])) {
            $post_id = intval($_GET['original_post_id']);
            $form .= '<input type="hidden" name="original_post_id" value="' . $post_id . '">';
        }

        $form .= '<input type="hidden" name="runthings_taxonomy_based_password_form" value="login_form">';
        $form .= wp_nonce_field('runthings_taxonomy_based_passwords_login_form', '_wpnonce', true, false);

        $form .= '</form>';

        return $form;
    }

    /**
     * Renders the logout link if the user is logged in
     */
    public function render_logout_link(array $atts): string
    {
        if ($this->cookies->is_logged_in()) {
            $logout_url = add_query_arg('runthings_taxonomy_logout', 'true', home_url());
            return '<a href="' . esc_url($logout_url) . '">' . __('Log out', 'runthings-taxonomy-based-passwords') . '</a>';
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
