<?php
/**
 * Light hardening that is safe on shared hosting.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Security headers on every front-end response.
add_action( 'send_headers', function () {
	if ( is_admin() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
} );

// XML-RPC is not used by this site.
add_filter( 'xmlrpc_enabled', '__return_false' );

// Do not list user accounts to visitors through the REST API or ?author= scans.
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
} );
add_action( 'template_redirect', function () {
	if ( isset( $_GET['author'] ) && ! is_user_logged_in() ) { // phpcs:ignore WordPress.Security.NonceVerification
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
} );

// Hide the WordPress version number.
remove_action( 'wp_head', 'wp_generator' );
