# Polylang Pro Single-sign on fix

Set crossdomain cookies due to new browser expectations. Might be useful for other WordPress single-sign on problems.

Tested on WordPress 5.9.3 and Polylang 3.2.4 + PHP 8.1.

Inspired by https://core.trac.wordpress.org/ticket/55440

Rewrited pluggable function wp_set_auth_cookie.
