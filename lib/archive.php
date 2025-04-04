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
        add_action('pre_get_posts', [$this, 'filter_feed_items']);
    }

    public function filter_posts_by_taxonomy($query): void
    {
        if (is_admin() || !$query->is_main_query() || (!$query->is_archive() && !$query->is_feed())) {
            return;
        }

        // Only filter protected objects
        $post_type = $query->get('post_type');
        if (!in_array($post_type, $this->config->objects)) {
            return;
        }

        // For normal archives, only show content for logged in users
        if (!$query->is_feed()) {
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

    /**
     * Filter feed items to remove all protected content
     */
    public function filter_feed_items($query): void
    {
        if (!$query->is_feed()) {
            return;
        }

        // Only filter protected objects
        $post_type = $query->get('post_type');
        if (!in_array($post_type, $this->config->objects)) {
            return;
        }

        // For feeds, exclude all posts that have any term in the protected taxonomy
        $protected_terms = get_terms([
            'taxonomy' => $this->config->taxonomy,
            'hide_empty' => true,
            'fields' => 'ids',
        ]);

        if (!empty($protected_terms) && !is_wp_error($protected_terms)) {
            $query->set('tax_query', [
                [
                    'taxonomy' => $this->config->taxonomy,
                    'field' => 'term_id',
                    'terms' => $protected_terms,
                    'operator' => 'NOT IN',
                ],
            ]);
        }
    }
}
