<?php

namespace RunthingsTaxonomyBasedPasswords;

class Cache
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;

        add_action('send_headers', [$this, 'apply_cache_segment']);
        add_action('wp', [$this, 'disable_page_cache']);
    }

    /**
     * Disable cache on protected content
     */
    public function disable_page_cache(): void
    {
        if (apply_filters('runthings_tbp_disable_page_cache', true) && $this->should_activate()) {
            if (!defined('DONOTCACHEPAGE')) {
                define('DONOTCACHEPAGE', true);
            }

            if (!defined('LSCACHE_NO_CACHE')) {
                define('LSCACHE_NO_CACHE', true);
            }
        }
    }

    /**
     * Apply cache segment for protected objects and hub pages
     */
    public function apply_cache_segment(): void
    {
        if ($this->should_activate()) {
            $term_id = $this->get_current_term_id();
            $this->set_cache_segment_header($term_id);
        }
    }

    /**
     * Set cache segment header
     */
    public function set_cache_segment_header(?int $term_id = null): void
    {
        $segment_key = $term_id ? "runthings-tbp-segment-{$term_id}" : "runthings-tbp-segment-guest";

        // Set the custom segmentation header
        header("X-Runthings-TBP-Segment: {$segment_key}", false);

        // Add Vary without overwriting existing ones (server will merge)
        header("Vary: X-Runthings-TBP-Segment", false);
    }

    /**
     * Get the current term ID
     */
    private function get_current_term_id(): ?int
    {
        $terms = get_the_terms(get_the_ID(), $this->config->taxonomy);
        if ($terms && !is_wp_error($terms)) {
            return $terms[0]->term_id;
        }
        return null;
    }

    /**
     * Check if the request is for protected content
     */
    private function should_activate(): bool
    {
        $post_type = get_post_type();

        return (is_singular() && ($this->is_protected_object($post_type) || $this->is_child_of_hub_object($post_type)))
            || (is_archive() && $this->is_protected_object($post_type));
    }

    /**
     * Check if the post type is a protected object
     */
    private function is_protected_object(string $post_type): bool
    {
        return in_array($post_type, $this->config->objects);
    }

    /**
     * Check if the post is a child of the hub object
     */
    private function is_child_of_hub_object(string $post_type): bool
    {
        $post = get_post();

        if (!$this->config->hub_object_id) {
            return false;
        }

        return $post && $post_type === $this->config->hub_object && $post->post_parent == $this->config->hub_object_id;
    }
}
