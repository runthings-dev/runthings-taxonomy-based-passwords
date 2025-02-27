<?php

namespace RunThingsTaxonomyBasedPasswords;

class Protection
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;

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
            $login_url = get_permalink($this->config->login_page_id);

            if ($login_url) {
                // Add the current URL as a return URL parameter
                global $wp;
                $current_url = home_url(add_query_arg([], $wp->request));
                $login_url = add_query_arg('return', urlencode($current_url), $login_url);
            } else {
                // Fallback url
                $login_url = home_url();
            }

            wp_redirect($login_url);
            exit;
        }
    }
}
