<?php

namespace RunthingsTaxonomyBasedPasswords;

class Archive
{
    private Config $config;
    private Cookies $cookies;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->cookies = new Cookies();

        add_action('pre_get_posts', [$this, 'filter_posts_by_taxonomy']);
    }

    public function filter_posts_by_taxonomy($query): void
    {
        if (is_admin() || !$query->is_main_query() || !$query->is_archive()) {
            return;
        }

        // Only filter protected objects
        $post_type = $query->get('post_type');
        if (!in_array($post_type, $this->config->objects)) {
            return;
        }

        if (!$this->cookies->is_logged_in()) {
            return;
        }

        $cookie_value = $this->cookies->get_cookie_value();
        $term_id = $cookie_value['term_id'];

        $query->set('tax_query', [
            [
                'taxonomy' => $this->config->taxonomy,
                'field' => 'term_id',
                'terms' => $term_id,
            ],
        ]);
    }
}
