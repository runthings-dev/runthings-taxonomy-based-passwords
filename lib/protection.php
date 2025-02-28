<?php

namespace RunThingsTaxonomyBasedPassword;

class Protection
{
    private $config;
    private $cookies;

    public function __construct($config)
    {
        $this->config = $config;
        $this->cookies = new Cookies();

        // Add protection
        add_action('template_redirect', [$this, 'single_protection']);
    }

    /**
     * Protect single pages
     */
    public function single_protection()
    {
        if (!is_singular() || is_admin()) {
            return;
        }

        $post_type = get_post_type();

        if (in_array($post_type, $this->config->objects)) {
            if ($this->cookies->is_logged_in()) {
                $cookie_value = $this->cookies->get_cookie_value();
                $term_id = $cookie_value['term_id'];
                $password = $cookie_value['password'];

                $valid_password = $this->get_valid_password($term_id);

                if (hash_equals($valid_password, $password)) {
                    return; // User is authenticated
                }
            }

            $login_url = get_permalink($this->config->login_page_id);

            if ($login_url) {
                // Add the current URL as a return URL parameter
                global $wp;
                $current_url = home_url(add_query_arg([], $wp->request));
                $login_url = add_query_arg(
                    [
                        'return_url' => urlencode($current_url),
                        'original_post_id' => get_the_ID()
                    ],
                    $login_url
                );
            } else {
                // Fallback url
                $login_url = home_url();
            }

            wp_redirect($login_url);
            exit;
        }
    }

    /**
     * Get the valid password for a post based on the attached taxonomy term
     */
    private function get_valid_password($term_id)
    {
        if (!$term_id) {
            return '';
        }

        $password = get_term_meta($term_id, 'password', true);

        return $password;
    }
}
