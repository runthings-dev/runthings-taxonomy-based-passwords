<?php

namespace RunthingsTaxonomyBasedPasswords;

class Hub
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;

        add_shortcode($this->config->shortcode_hub_page_list, [$this, 'render_hub_page_list']);
    }

    /**
     * Shortcode to render the list of protected pages dynamically, use it on the top hub page
     */
    public function render_hub_page_list()
    {
        return $this->get_hub_page_list();
    }

    /**
     * Get the list of hub pages
     */
    public function get_hub_page_list()
    {
        $hub_id = $this->config->hub_object_id;

        $args = [
            'post_type' => $this->config->hub_object,
            'post_parent' => $hub_id,
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            $html = '<ul class="runthings-taxonomy-based-passwords-hub-page-list">';
            while ($query->have_posts()) {
                $query->the_post();
                $html .= '<li><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></li>';
            }
            $html .= '</ul>';
            wp_reset_postdata();
            return $html;
        }

        return '';
    }
}
