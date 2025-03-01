<?php

namespace RunthingsTaxonomyBasedPasswords;

class Config
{
    private const PAGE_ID_NOT_SET = 0;

    /**
     * Hub object that is the parent of the protected pages
     */
    public string $hub_object = 'page';
    public int $hub_object_id;

    /**
     * Login page
     */
    public int $login_page_id;

    /**
     * Post types to protect
     */
    public array $objects = [];

    /**
     * Exempt roles
     */
    public array $exempt_roles = [];

    /**
     * Taxonomy to use for password protection
     */
    public string $taxonomy = 'grower_contract';

    /**
     * Admin menu capability
     */
    public string $admin_options_capability = 'edit_pages';

    public function __construct()
    {
        $settings = get_option('runthings_taxonomy_based_passwords_settings', []);

        $this->hub_object_id = (int) ($settings['hub_object_id'] ?? Config::PAGE_ID_NOT_SET);
        $this->login_page_id = (int) ($settings['login_page_id'] ?? Config::PAGE_ID_NOT_SET);
        $this->objects = $settings['objects'] ?? [];
        $this->exempt_roles = $settings['exempt_roles'] ?? [];
    }
}
