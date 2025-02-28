<?php

namespace RunThingsTaxonomyBasedPassword;

class Config
{
    /**
     * Hub object that is the parent of the protected pages
     */
    public $hub_object = ['page'];
    public $hub_object_id = 6522;

    /**
     * Login page
     */
    public $login_page_id = 6515;

    /**
     * Post types to protect
     */
    public $objects = ['grower-news', 'grower-questions', 'farmer-profiles'];

    /**
     * Taxonomy to use for password protection
     */
    public $taxonomy = 'grower_contract';
}
