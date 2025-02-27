<?php

namespace RunThingsTaxonomyBasedPassword;

class Authentication
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;

        // Add custom authentication
        add_filter('authenticate', [$this, 'custom_authenticate'], 30, 3);

        // Add shortcode
        add_shortcode('runthings_taxonomy_login_form', [$this, 'render_login_form']);
    }

    /**
     * Custom authentication function
     */
    public function custom_authenticate($user, $username, $password)
    {
        if (isset($_POST['runthings_taxonomy_based_password_form']) && $_POST['runthings_taxonomy_based_password_form'] === 'login_form') {
            if (isset($_POST['post_password']) && isset($_POST['return_url'])) {
                $password = $_POST['post_password'];
                $return_url = esc_url_raw($_POST['return_url']);
                $original_post_id = url_to_postid($return_url);

                if ($original_post_id) {
                    $original_post = get_post($original_post_id);
                    $terms = wp_get_post_terms($original_post_id, 'grower_contract', ['fields' => 'names']);
                    $valid_password = !empty($terms) ? $terms[0] : '';

                    if ($original_post && $valid_password === $password) {
                        // Password is correct
                        $this->set_cookie($password, 123);
                        wp_redirect($return_url);
                        exit;
                    } else {
                        return new \WP_Error('incorrect_password', __('The password you entered is incorrect.'));
                    }
                }
            }
        }

        return $user;
    }

    /**
     * Renders the login form using the built-in WordPress password form
     */
    public function render_login_form($atts)
    {
        global $post;

        // Use a temporary post object to generate the form
        $temp_post = new \stdClass();
        $temp_post->ID = 0;
        $temp_post->post_password = '';

        // Generate the password form
        $form = get_the_password_form($temp_post);

        // Customize the form if needed
        $form = str_replace(
            'This content is password protected.',
            'This content is restricted to contracted growers. Please enter your password to view it.',
            $form
        );

        // Add a hidden field for the return URL and custom form identifier
        if (isset($_GET['return'])) {
            $return_url = esc_url($_GET['return']);
            $hidden_fields = '<input type="hidden" name="return_url" value="' . $return_url . '">';
            $hidden_fields .= '<input type="hidden" name="runthings_taxonomy_based_password_form" value="login_form">';
            $form = str_replace('</form>', $hidden_fields . '</form>', $form);
        }

        return $form;
    }

    /**
     * Set the cookie object
     */
    private function set_cookie($password, $term_id)
    {
        global $wp_hasher;
        if (empty($wp_hasher)) {
            require_once ABSPATH . 'wp-includes/class-phpass.php';
            $wp_hasher = new \PasswordHash(8, true);
        }
        $hashed_password = $wp_hasher->HashPassword($password);
        $cookie_name = 'runthings_taxonomy_based_password' . COOKIEHASH;
        $cookie_value = json_encode(['term_id' => $term_id, 'password' => $hashed_password]);
        setcookie($cookie_name, $cookie_value, time() + 864000, COOKIEPATH);
    }
}
