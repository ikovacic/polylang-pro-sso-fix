<?php
/**
 * Plugin Name: Polylang Pro Single-sign on fix
 * Description: Set crossdomain cookies due to new browser expectations.
 * Plugin URI:  #
 * Version:     1.0.0
 * Author:      Igor Kovacic
 * Author URI:  https://www.applause.hr

 */

// INSPIRED BY
// https://core.trac.wordpress.org/ticket/55440

function applause_set_cookie( $name, $value = '', $args ) {
    if ( headers_sent() ) {
        return false;
    }

    $defaults = array(
        'expires'  => 0,
        'path'     => COOKIEPATH,
        'domain'   => COOKIE_DOMAIN,
        'secure'   => is_ssl(),
        'httponly' => false,
        'samesite' => 'None',
    );

    $args = wp_parse_args( $args, $defaults );

    if ( version_compare( PHP_VERSION, '7.3', '<' ) ) {
        $args['path'] .= '; SameSite=' . $args['samesite']; // Hack to set SameSite value in PHP < 7.3. Doesn't work with newer versions.
        return setcookie( $name, $value, $args['expires'], $args['path'], $args['domain'], $args['secure'], $args['httponly'] );
    } else {
        return setcookie( $name, $value, $args );
    }
}

// REWRITE PLUGGABLE FUNCTION

if ( ! function_exists( 'wp_set_auth_cookie' ) ) {

    function wp_set_auth_cookie( $user_id, $remember = false, $secure = '', $token = '' ) {
        if ( $remember ) {
            $expiration = time() + apply_filters( 'auth_cookie_expiration', 14 * DAY_IN_SECONDS, $user_id, $remember );
            $expire = $expiration + ( 12 * HOUR_IN_SECONDS );
        } else {
            $expiration = time() + apply_filters( 'auth_cookie_expiration', 2 * DAY_IN_SECONDS, $user_id, $remember );
            $expire     = 0;
        }

        if ( '' === $secure ) {
            $secure = is_ssl();
        }

        $secure_logged_in_cookie = $secure && 'https' === parse_url( get_option( 'home' ), PHP_URL_SCHEME );

        $secure = apply_filters( 'secure_auth_cookie', $secure, $user_id );

        $secure_logged_in_cookie = apply_filters( 'secure_logged_in_cookie', $secure_logged_in_cookie, $user_id, $secure );

        if ( $secure ) {
            $auth_cookie_name = SECURE_AUTH_COOKIE;
            $scheme           = 'secure_auth';
        } else {
            $auth_cookie_name = AUTH_COOKIE;
            $scheme           = 'auth';
        }

        if ( '' === $token ) {
            $manager = WP_Session_Tokens::get_instance( $user_id );
            $token   = $manager->create( $expiration );
        }

        $auth_cookie      = wp_generate_auth_cookie( $user_id, $expiration, $scheme, $token );
        $logged_in_cookie = wp_generate_auth_cookie( $user_id, $expiration, 'logged_in', $token );

        do_action( 'set_auth_cookie', $auth_cookie, $expire, $expiration, $user_id, $scheme, $token );

        do_action( 'set_logged_in_cookie', $logged_in_cookie, $expire, $expiration, $user_id, 'logged_in', $token );

        if ( ! apply_filters( 'send_auth_cookies', true ) ) {
            return;
        }

        // OLD WAY
        //setcookie( $auth_cookie_name, $auth_cookie, $expire, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN, $secure, true );

        applause_set_cookie( $auth_cookie_name, $auth_cookie, array(
            'expires'  => $expire,
            'path'     => PLUGINS_COOKIE_PATH,
            'secure'   => $secure,
            'httponly' => true,
        ) );

        // OLD WAY
        //setcookie( $auth_cookie_name, $auth_cookie, $expire, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, $secure, true );
        applause_set_cookie( $auth_cookie_name, $auth_cookie, array(
            'expires'  => $expire,
            'path'     => ADMIN_COOKIE_PATH,
            'secure'   => $secure,
            'httponly' => true,
        ) );

        // OLD WAY
        //setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true );
        applause_set_cookie( LOGGED_IN_COOKIE, $logged_in_cookie, array(
            'expires'  => $expire,
            'path'     => COOKIEPATH,
            'secure'   => $secure_logged_in_cookie,
            'httponly' => true,
        ) );

        if ( COOKIEPATH != SITECOOKIEPATH ) {

            // OLD WAY
            //setcookie( LOGGED_IN_COOKIE, $logged_in_cookie, $expire, SITECOOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true );

            applause_set_cookie( LOGGED_IN_COOKIE, $logged_in_cookie, array(
                'expires'  => $expire,
                'path'     => SITECOOKIEPATH,
                'secure'   => $secure_logged_in_cookie,
                'httponly' => true,
            ) );
        }

    }
}
