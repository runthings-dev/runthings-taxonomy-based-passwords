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

        // State variables
        $error_message = '';
        $return_url = '';
        $original_post_id = 0;
        $input_password = '';

        // Generate a unique field ID for accessibility
        $field_id = 'pwbox-' . wp_rand();

        // Process form submission (POST request)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['runthings_taxonomy_based_password_form'])) {
            // Verify nonce
            if (
                ! isset($_POST['_wpnonce']) ||
                ! wp_verify_nonce(sanitize_key(wp_unslash($_POST['_wpnonce'])), 'runthings_taxonomy_based_passwords_form')
            ) {
                $error_message = __('POST: Security verification failed. Please try again.', 'runthings-taxonomy-based-passwords');
            } else {
                // Validate required fields
                if (! isset($_POST['post_password']) || ! isset($_POST['return_url']) || ! isset($_POST['original_post_id'])) {
                    $error_message = __('Missing required fields.', 'runthings-taxonomy-based-passwords');
                } else {
                    // Sanitize and set form values
                    $input_password = sanitize_text_field(wp_unslash($_POST['post_password']));
                    $return_url = esc_url_raw(wp_unslash($_POST['return_url']));
                    $original_post_id = (int) $_POST['original_post_id'];

                    // Validate data
                    if (empty($original_post_id) || empty($return_url)) {
                        $error_message = __('Invalid form data.', 'runthings-taxonomy-based-passwords');
                    } else {
                        // Validate URL domain (security measure)
                        $return_url_host = wp_parse_url($return_url, PHP_URL_HOST);
                        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

                        if ($return_url_host !== $site_host) {
                            $error_message = __('Invalid return URL.', 'runthings-taxonomy-based-passwords');
                        } else {
                            // Process authentication
                            $term_id = $this->get_term_id($original_post_id);
                            $stored_hashed_password = $this->get_valid_password($term_id);

                            if ($this->verify_password($input_password, $stored_hashed_password)) {
                                // Success - set cookie and redirect
                                $this->cookies->set_cookie($stored_hashed_password, $term_id);
                                wp_safe_redirect($return_url);
                                exit;
                            } else {
                                // Invalid password
                                $error_message = __('The password you entered is incorrect.', 'runthings-taxonomy-based-passwords');
                            }
                        }
                    }
                }
            }
        } else {
            // Initial form load via redirect (GET request)
            if (isset($_REQUEST['return_url']) && isset($_REQUEST['original_post_id'])) {
                // Verify nonce
                if (
                    ! isset($_GET['_wpnonce']) ||
                    ! wp_verify_nonce(sanitize_key(wp_unslash($_GET['_wpnonce'])), 'runthings_taxonomy_based_passwords_login_redirect')
                ) {
                    return '<div class="runthings-taxonomy-based-passwords-error" role="alert">' .
                        esc_html__('GET: Security verification failed. Please try accessing the protected content again.', 'runthings-taxonomy-based-passwords') .
                        '</div>';
                }

                $return_url = esc_url_raw(wp_unslash($_GET['return_url']));
                $original_post_id = (int) $_GET['original_post_id'];

                // Validate URL domain (security measure)
                $return_url_host = wp_parse_url($return_url, PHP_URL_HOST);
                $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

                if ($return_url_host !== $site_host) {
                    return '<div class="runthings-taxonomy-based-passwords-error" role="alert">' .
                        esc_html__('Invalid return URL.', 'runthings-taxonomy-based-passwords') .
                        '</div>';
                }
            } else {
                // No parameters provided
                return '<div class="runthings-taxonomy-based-passwords-error" role="alert">' .
                    esc_html__('Missing required parameters. Please try accessing the protected content again.', 'runthings-taxonomy-based-passwords') .
                    '</div>';
            }
        }

        // Set up accessibility attributes if there's an error
        $aria = '';
        $form_class = 'post-password-form runthings-taxonomy-based-passwords-login-form';

        if (! empty($error_message)) {
            $form_class .= ' password-form-error';
            $aria = ' aria-describedby="error-' . esc_attr($field_id) . '"';
        }

        // Build the form
        $output = '<form method="post" class="' . esc_attr($form_class) . '">';
        $output .= '<p>' . esc_html__('This content is restricted. Please enter your password to view it.', 'runthings-taxonomy-based-passwords') . '</p>';

        // Error message if any
        if (! empty($error_message)) {
            $output .= '<div class="runthings-taxonomy-based-passwords-error" role="alert">';
            $output .= '<p id="error-' . esc_attr($field_id) . '">' . esc_html($error_message) . '</p>';
            $output .= '</div>';
        }

        // Password field
        $output .= '<p>';
        $output .= '<label for="' . esc_attr($field_id) . '">' . esc_html__('Password:', 'runthings-taxonomy-based-passwords') . ' ';
        $output .= '<input name="post_password" id="' . esc_attr($field_id) . '" type="password" spellcheck="false" required size="20"' . $aria . ' />';
        $output .= '</label> ';
        $output .= '<input type="submit" name="Submit" value="' . esc_attr__('Enter', 'runthings-taxonomy-based-passwords') . '" />';
        $output .= '</p>';

        // Hidden fields
        $output .= '<input type="hidden" name="return_url" value="' . esc_attr($return_url) . '">';
        $output .= '<input type="hidden" name="original_post_id" value="' . esc_attr($original_post_id) . '">';
        $output .= '<input type="hidden" name="runthings_taxonomy_based_password_form" value="login_form">';

        // Security nonce
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
        if (! isset($_GET['runthings_taxonomy_logout'])) {
            return;
        }

        if (
            ! isset($_GET['_wpnonce']) ||
            ! wp_verify_nonce(sanitize_key(wp_unslash($_GET['_wpnonce'])), 'runthings_taxonomy_based_passwords_logout')
        ) {
            wp_safe_redirect(home_url());
            exit;
        }

        $this->cookies->clear_cookie();
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
