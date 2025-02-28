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

        if ($this->is_protected_object($post_type) || $this->is_child_of_hub_object($post_type)) {
            $this->check_for_authentication();
        }
    }

    private function is_protected_object($post_type)
    {
        return in_array($post_type, $this->config->objects);
    }

    private function is_child_of_hub_object($post_type)
    {
        $post = get_post();
        return $post_type === $this->config->hub_object && $post->post_parent == $this->config->hub_object_id;
    }

    private function check_for_authentication()
    {
        if ($this->cookies->is_logged_in()) {
            $cookie_value = $this->cookies->get_cookie_value();
            $term_id = $cookie_value['term_id'];
            $password = $cookie_value['password'];

            $current_term_id = $this->get_current_term_id();
            if ($current_term_id !== $term_id) {
                $this->redirect_to_login();
            }

            $valid_password = $this->get_valid_password($current_term_id);

            if (hash_equals($valid_password, $password)) {
                return; // User is authenticated
            }
        }

        $this->redirect_to_login();
    }

    private function get_current_term_id()
    {
        $terms = get_the_terms(get_the_ID(), $this->config->taxonomy);
        if ($terms && !is_wp_error($terms)) {
            return $terms[0]->term_id;
        }
        return null;
    }

    private function redirect_to_login()
    {
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

    /**
     * Get the valid password for a post based on the attached taxonomy term
     */
    private function get_valid_password($term_id)
    {
        if (!$term_id) {
            return '';
        }

        $password = get_term_meta($term_id, 'runthings_taxonomy_password', true);

        $hashed_password = hash('sha256', $password);

        return $hashed_password;
    }
}
