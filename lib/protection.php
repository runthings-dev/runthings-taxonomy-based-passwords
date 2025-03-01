<?php

namespace RunthingsTaxonomyBasedPasswords;

class Protection
{
    private Config $config;
    private Cookies $cookies;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->cookies = new Cookies();

        // Add protection
        add_action('template_redirect', [$this, 'single_protection']);
    }

    /**
     * Protect single pages
     */
    public function single_protection(): void
    {
        if (!is_singular() || is_admin()) {
            return;
        }

        $post_type = get_post_type();

        if ($this->is_protected_object($post_type) || $this->is_child_of_hub_object($post_type)) {
            $this->check_for_authentication();
        }
    }

    private function is_protected_object(string $post_type): bool
    {
        return in_array($post_type, $this->config->objects);
    }

    private function is_child_of_hub_object(string $post_type): bool
    {
        $post = get_post();

        if (!$this->config->hub_object_id) {
            return false;
        }

        return $post && $post_type === $this->config->hub_object && $post->post_parent == $this->config->hub_object_id;
    }

    private function check_for_authentication(): void
    {
        if (!empty($this->config->exempt_roles) && is_user_logged_in()) {
            $user = wp_get_current_user();
            $user_roles = $user->roles;

            foreach ($user_roles as $role) {
                if (in_array($role, $this->config->exempt_roles)) {
                    return; // User is in an exempt role
                }
            }
        }

        if ($this->cookies->is_logged_in()) {
            $cookie_value = $this->cookies->get_cookie_value();
            $term_id = $cookie_value['term_id'];
            $cookie_hashed_password = $cookie_value['password'];

            $current_term_id = $this->get_current_term_id();
            if ($current_term_id !== $term_id) {
                $this->redirect_to_login();
            }

            $stored_hashed_password = $this->get_valid_password($current_term_id);

            if ($this->verify_hashed_password($cookie_hashed_password, $stored_hashed_password)) {
                return; // User is authenticated
            }
        }

        $this->redirect_to_login();
    }

    private function get_current_term_id(): ?int
    {
        $terms = get_the_terms(get_the_ID(), $this->config->taxonomy);
        if ($terms && !is_wp_error($terms)) {
            return $terms[0]->term_id;
        }
        return null;
    }

    private function redirect_to_login(): void
    {
        if ($this->config->login_page_id === 0) {
            return; // Do not redirect if the login page is not set
        }

        $login_url = get_permalink($this->config->login_page_id);

        if (!$login_url) {
            return;
        }

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

        wp_redirect($login_url);
        exit;
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
     * Verifies a hashed password against the stored hash
     */
    public function verify_hashed_password(string $cookie_hashed_password, string $stored_hashed_password): bool
    {
        if (!$stored_hashed_password || !$cookie_hashed_password) {
            return false; // No password stored
        }

        return hash_equals($stored_hashed_password, $cookie_hashed_password);
    }
}
