<?php

namespace RunthingsTaxonomyBasedPasswords;

/**
 * Handles authentication for taxonomy-based passwords.
 */
class Authentication
{
    /**
     * Configuration object.
     *
     * @var Config
     */
    private Config $config;

    /**
     * Cookies handler.
     *
     * @var Cookies
     */
    private Cookies $cookies;

    /**
     * Constructor.
     *
     * @param Config $config The plugin configuration.
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->cookies = new Cookies();

        add_shortcode($this->config->shortcode_login_form, [$this, 'login_form_shortcode']);
        add_shortcode($this->config->shortcode_logout, [$this, 'logout_link_shortcode']);

        // Only handle logout, not form submissions - we'll do that in the shortcode
        add_action('init', [$this, 'handle_logout']);
    }

    /**
     * Renders the login form shortcode.
     * Also handles form processing when submitted via POST.
     *
     * @param array $atts Shortcode attributes.
     * @return string The login form HTML.
     */
    public function login_form_shortcode(array $atts = []): string
    {
        wp_enqueue_style(
            'runthings-taxonomy-based-passwords-styles',
            RUNTHINGS_TAXONOMY_BASED_PASSWORDS_URL . 'assets/css/styles.css',
            [],
            RUNTHINGS_TAXONOMY_BASED_PASSWORDS_VERSION
        );

        $data = $this->collect_login_form_data();
        return $this->render_login_form($data);
    }

    /**
     * Collects and validates data from the login form.
     * Refactored to reduce complexity and remove duplicated code.
     *
     * @return array An array containing the form data and error messages.
     */
    private function collect_login_form_data(): array
    {
        $data = [
            'error_message' => '',
            'return_url' => '',
            'original_post_id' => 0,
            'field_id' => 'pwbox-' . wp_rand(),
        ];

        // Handle POST submission (login attempt)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['runthings_taxonomy_based_password_form'])) {
            return $this->process_post_submission($data);
        }
        
        // Handle GET request (initial form load)
        if (isset($_REQUEST['return_url'], $_REQUEST['original_post_id'])) {
            return $this->process_get_request($data);
        }
        
        // No parameters provided
        $data['error_message'] = __('Missing required parameters. Please try accessing the protected content again.', 'runthings-taxonomy-based-passwords');
        return $data;
    }

    /**
     * Process POST submission for password verification.
     *
     * @param array $data The initial data array.
     * @return array Updated data array.
     */
    private function process_post_submission(array $data): array
    {
        // Verify security nonce
        if (!$this->verify_nonce($_POST, '_wpnonce', 'runthings_taxonomy_based_passwords_form')) {
            $data['error_message'] = __('Security verification failed. Please try again.', 'runthings-taxonomy-based-passwords');
            return $data;
        }

        // Check required fields
        if (!isset($_POST['post_password'], $_POST['return_url'], $_POST['original_post_id'])) {
            $data['error_message'] = __('Missing required fields.', 'runthings-taxonomy-based-passwords');
            return $data;
        }

        // Sanitize input
        $input_password = sanitize_text_field(wp_unslash($_POST['post_password']));
        $data['return_url'] = esc_url_raw(wp_unslash($_POST['return_url']));
        $data['original_post_id'] = (int)$_POST['original_post_id'];

        // Validate data
        if (empty($data['original_post_id']) || empty($data['return_url'])) {
            $data['error_message'] = __('Invalid form data.', 'runthings-taxonomy-based-passwords');
            return $data;
        }

        // Validate URL domain
        if ($error = $this->validate_url_domain($data['return_url'])) {
            $data['error_message'] = $error;
            return $data;
        }

        // Verify password
        $term_id = $this->get_term_id($data['original_post_id']);
        $stored_hashed_password = $this->get_valid_password($term_id);

        if ($this->verify_password($input_password, $stored_hashed_password)) {
            $this->cookies->set_cookie($stored_hashed_password, $term_id);
            wp_safe_redirect($data['return_url']);
            exit;
        } 
        
        $data['error_message'] = __('The password you entered is incorrect.', 'runthings-taxonomy-based-passwords');
        return $data;
    }

    /**
     * Process GET request for initial form load.
     *
     * @param array $data The initial data array.
     * @return array Updated data array.
     */
    private function process_get_request(array $data): array
    {
        // Verify security nonce
        if (!$this->verify_nonce($_GET, '_wpnonce', 'runthings_taxonomy_based_passwords_login_redirect')) {
            $data['error_message'] = __('Security verification failed. Please try accessing the protected content again.', 'runthings-taxonomy-based-passwords');
            return $data;
        }

        // Sanitize input
        $data['return_url'] = esc_url_raw(wp_unslash($_GET['return_url']));
        $data['original_post_id'] = (int)$_GET['original_post_id'];

        // Validate URL domain
        if ($error = $this->validate_url_domain($data['return_url'])) {
            $data['error_message'] = $error;
            return $data;
        }

        return $data;
    }

    /**
     * Verify nonce from request data.
     *
     * @param array  $source Source array ($_POST or $_GET).
     * @param string $field  Nonce field name.
     * @param string $action Nonce action.
     * @return bool Whether the nonce is valid.
     */
    private function verify_nonce(array $source, string $field, string $action): bool
    {
        return isset($source[$field]) && wp_verify_nonce(sanitize_key(wp_unslash($source[$field])), $action);
    }

    /**
     * Validate that URL domain matches the site domain.
     *
     * @param string $url URL to validate.
     * @return string|null Error message or null if valid.
     */
    private function validate_url_domain(string $url): ?string
    {
        $url_host = wp_parse_url($url, PHP_URL_HOST);
        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
        
        if ($url_host !== $site_host) {
            return __('Invalid return URL.', 'runthings-taxonomy-based-passwords');
        }
        
        return null;
    }

    private function render_login_form(array $data): string
    {
        $aria = '';
        $form_class = 'post-password-form runthings-taxonomy-based-passwords-login-form';

        if (!empty($data['error_message'])) {
            $form_class .= ' password-form-error';
            $aria = ' aria-describedby="error-' . esc_attr($data['field_id']) . '"';
        }

        $output = '<form method="post" class="' . esc_attr($form_class) . '">';
        $output .= '<div class="runthings-taxonomy-based-passwords-restricted-content">';
        $output .= '<p>' . esc_html__('This content is restricted. Please enter your password to view it.', 'runthings-taxonomy-based-passwords') . '</p>';
        $output .= '</div>';

        if (!empty($data['error_message'])) {
            $output .= '<div class="runthings-taxonomy-based-passwords-error" role="alert">';
            $output .= '<p id="error-' . esc_attr($data['field_id']) . '">' . esc_html($data['error_message']) . '</p>';
            $output .= '</div>';
        }

        $output .= '<p>';
        $output .= '<label for="' . esc_attr($data['field_id']) . '">' . esc_html__('Password:', 'runthings-taxonomy-based-passwords') . ' ';
        $output .= '<input name="post_password" id="' . esc_attr($data['field_id']) . '" type="password" spellcheck="false" required size="20"' . $aria . ' />';
        $output .= '</label> ';
        $output .= '<input type="submit" name="Submit" value="' . esc_attr__('Enter', 'runthings-taxonomy-based-passwords') . '" />';
        $output .= '</p>';

        $output .= '<input type="hidden" name="return_url" value="' . esc_attr($data['return_url']) . '">';
        $output .= '<input type="hidden" name="original_post_id" value="' . esc_attr($data['original_post_id']) . '">';
        $output .= '<input type="hidden" name="runthings_taxonomy_based_password_form" value="login_form">';
        $output .= wp_nonce_field('runthings_taxonomy_based_passwords_form', '_wpnonce', true, false);
        $output .= '</form>';

        return $output;
    }

    /**
     * Renders logout link if user is authenticated.
     *
     * @param array $atts Shortcode attributes.
     * @return string The logout link HTML.
     */
    public function logout_link_shortcode(array $atts = []): string
    {
        if ($this->cookies->is_logged_in()) {
            $logout_url = add_query_arg('runthings_taxonomy_logout', 'true', home_url());
            $logout_url = wp_nonce_url($logout_url, 'runthings_taxonomy_based_passwords_logout');

            return '<a href="' . esc_url($logout_url) . '">' .
                esc_html__('Log Out', 'runthings-taxonomy-based-passwords') .
                '</a>';
        }

        return '';
    }

    /**
     * Handles user logout.
     *
     * @return void
     */
    public function handle_logout(): void
    {
        if (!isset($_GET['runthings_taxonomy_logout'])) {
            return;
        }

        if ($this->verify_nonce($_GET, '_wpnonce', 'runthings_taxonomy_based_passwords_logout')) {
            $this->cookies->clear_cookie();
        }

        wp_safe_redirect(home_url());
        exit;
    }

    /**
     * Gets the term ID for a post based on attached taxonomy term.
     *
     * @param int $post_id The post ID.
     * @return int The term ID or 0 if not found.
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
     * Gets the valid password for a term.
     *
     * @param int $term_id The term ID.
     * @return string The hashed password or empty string if not found.
     */
    private function get_valid_password(int $term_id): string
    {
        if (! $term_id) {
            return '';
        }

        return get_term_meta($term_id, 'runthings_taxonomy_password', true) ?: '';
    }

    /**
     * Verifies a password against the stored hash.
     *
     * @param string $input_password The input password.
     * @param string $stored_hashed_password The stored hashed password.
     * @return bool Whether the password is valid.
     */
    private function verify_password(string $input_password, string $stored_hashed_password): bool
    {
        if (empty($stored_hashed_password)) {
            return false;
        }

        return password_verify($input_password, $stored_hashed_password);
    }
}
