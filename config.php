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
    public string $taxonomy = '';
    public string $taxonomy_slug = '';
    public string $taxonomy_singular = '';
    public string $taxonomy_plural = '';

    public string $taxonomy_default = 'access_group';
    public string $taxonomy_slug_default = 'access-group';
    public string $taxonomy_singular_default = 'Access Group';
    public string $taxonomy_plural_default = 'Access Groups';

    /**
     * Archive redirect option (home page or hub page)
     */
    public string $archive_redirect;

    /**
     * Delete all data on uninstall
     */
    public bool $delete_data_on_uninstall;

    /**
     * Capabilities (static for the plugin 'activate' hook to be able to scaffold them)
     */
    public static string $manage_options_capability = 'runthings_tbp_manage_options';
    public static string $set_passwords_capability = 'runthings_tbp_set_passwords';

    public function __construct()
    {
        $settings = get_option('runthings_taxonomy_based_passwords_settings', []);

        $this->hub_object_id = (int) ($settings['hub_object_id'] ?? Config::PAGE_ID_NOT_SET);
        $this->login_page_id = (int) ($settings['login_page_id'] ?? Config::PAGE_ID_NOT_SET);
        $this->objects = $settings['objects'] ?? [];
        $this->exempt_roles = $settings['exempt_roles'] ?? [];
        $this->archive_redirect = $settings['archive_redirect'] ?? 'hub';
        $this->taxonomy = $settings['taxonomy'] ?? $this->taxonomy_default;
        $this->taxonomy_slug = $settings['taxonomy_slug'] ?? $this->taxonomy_slug_default;
        $this->taxonomy_singular = $settings['taxonomy_singular'] ?? $this->taxonomy_singular_default;
        $this->taxonomy_plural = $settings['taxonomy_plural'] ?? $this->taxonomy_plural_default;
        $this->delete_data_on_uninstall = (bool) ($settings['delete_data_on_uninstall'] ?? true);
    }
}
