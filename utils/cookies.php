<?php

namespace RunthingsTaxonomyBasedPasswords;

class Cookies
{
    private string $cookie_name;

    public function __construct()
    {
        $this->cookie_name = 'runthings_taxonomy_based_passwords' . COOKIEHASH;
    }

    /**
     * Set the cookie
     */
    public function set_cookie(string $hashed_password, int $term_id): void
    {
        $cookie_value = json_encode(['term_id' => $term_id, 'password' => $hashed_password]);
        $expiration_time = 12 * 30 * 24 * 60 * 60; // 12 months
        setcookie($this->cookie_name, $cookie_value, time() + $expiration_time, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }

    /**
     * Clear the cookie
     */
    public function clear_cookie(): void
    {
        setcookie($this->cookie_name, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }


    /**
     * Check if the user is logged in
     */
    public function is_logged_in(): bool
    {
        return isset($_COOKIE[$this->cookie_name]);
    }

    /**
     * Get the cookie value
     */
    public function get_cookie_value(): ?array
    {
        return isset($_COOKIE[$this->cookie_name]) ? json_decode(sanitize_text_field(wp_unslash($_COOKIE[$this->cookie_name])), true) : null;
    }
}
