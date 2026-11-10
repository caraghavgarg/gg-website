<?php
/**
 * Updates for the GG Site plugin and the GG Theme from the firm's GitHub repository
 * (caraghavgarg/gg-website). The repository's dist/ folder holds ready-made zips and
 * versions.json; when a newer version is listed there, WordPress shows the normal
 * "update available" notice on Dashboard → Updates and installs it with one click.
 *
 * If the repository is made private, paste a GitHub access key (read-only, for that
 * repository) in Settings → GG Site → Updates. It is sent only to GitHub.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GGS_UPDATE_BASE', 'https://raw.githubusercontent.com/caraghavgarg/gg-website/main/dist/' );

/** Latest versions published in the repository (cached for an hour; refreshed by "Check again"). */
function ggs_remote_versions( $force = false ) {
	$cached = get_site_transient( 'ggs_remote_versions' );
	if ( ! $force && is_array( $cached ) ) {
		return $cached;
	}
	$res = wp_remote_get( GGS_UPDATE_BASE . 'versions.json?t=' . time(), array( 'timeout' => 10 ) );
	$out = array();
	if ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) ) {
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( is_array( $data ) ) {
			foreach ( array( 'gg-site', 'gg-theme' ) as $k ) {
				if ( ! empty( $data[ $k ] ) && preg_match( '/^\d+(\.\d+){0,3}$/', (string) $data[ $k ] ) ) {
					$out[ $k ] = (string) $data[ $k ];
				}
			}
		}
	}
	set_site_transient( 'ggs_remote_versions', $out, $out ? HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS );
	return $out;
}

/** "Check again" on Dashboard → Updates also re-reads the repository. */
add_action( 'load-update-core.php', function () {
	if ( isset( $_GET['force-check'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		delete_site_transient( 'ggs_remote_versions' );
	}
} );

add_filter( 'pre_set_site_transient_update_plugins', function ( $transient ) {
	if ( ! is_object( $transient ) ) {
		return $transient;
	}
	$remote = ggs_remote_versions();
	$file   = plugin_basename( GGS_DIR . 'gg-site.php' );
	if ( ! empty( $remote['gg-site'] ) && version_compare( $remote['gg-site'], GGS_VER, '>' ) ) {
		$transient->response[ $file ] = (object) array(
			'slug'        => 'gg-site',
			'plugin'      => $file,
			'new_version' => $remote['gg-site'],
			'url'         => 'https://github.com/caraghavgarg/gg-website',
			'package'     => GGS_UPDATE_BASE . 'gg-site.zip',
		);
	} elseif ( isset( $transient->response[ $file ] ) ) {
		unset( $transient->response[ $file ] );
	}
	return $transient;
} );

add_filter( 'pre_set_site_transient_update_themes', function ( $transient ) {
	if ( ! is_object( $transient ) ) {
		return $transient;
	}
	$theme = wp_get_theme( 'gg-theme' );
	if ( ! $theme->exists() ) {
		return $transient;
	}
	$remote = ggs_remote_versions();
	if ( ! empty( $remote['gg-theme'] ) && version_compare( $remote['gg-theme'], $theme->get( 'Version' ), '>' ) ) {
		$transient->response['gg-theme'] = array(
			'theme'       => 'gg-theme',
			'new_version' => $remote['gg-theme'],
			'url'         => 'https://github.com/caraghavgarg/gg-website',
			'package'     => GGS_UPDATE_BASE . 'gg-theme.zip',
		);
	}
	return $transient;
} );

/** Short details for the "View version details" link on the Plugins screen. */
add_filter( 'plugins_api', function ( $result, $action, $args ) {
	if ( 'plugin_information' !== $action || empty( $args->slug ) || 'gg-site' !== $args->slug ) {
		return $result;
	}
	$remote = ggs_remote_versions();
	return (object) array(
		'name'          => 'GG Site',
		'slug'          => 'gg-site',
		'version'       => $remote['gg-site'] ?? GGS_VER,
		'author'        => 'Garg Garg &amp; Associates',
		'homepage'      => 'https://github.com/caraghavgarg/gg-website',
		'download_link' => GGS_UPDATE_BASE . 'gg-site.zip',
		'sections'      => array( 'description' => 'Website tools for Garg Garg &amp; Associates. Updates come from the firm\'s GitHub repository.' ),
	);
}, 10, 3 );

/** After an update, read versions afresh next time. */
add_action( 'upgrader_process_complete', function () {
	delete_site_transient( 'ggs_remote_versions' );
} );

/** Private repository: add the access key to requests that go to GitHub, and only to GitHub. */
add_filter( 'http_request_args', function ( $args, $url ) {
	$token = trim( (string) get_option( 'ggs_github_token', '' ) );
	if ( '' === $token ) {
		return $args;
	}
	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( in_array( $host, array( 'raw.githubusercontent.com', 'api.github.com', 'github.com' ), true ) && 0 === strpos( $url, GGS_UPDATE_BASE ) ) {
		$args['headers']                  = isset( $args['headers'] ) && is_array( $args['headers'] ) ? $args['headers'] : array();
		$args['headers']['Authorization'] = 'token ' . $token;
	}
	return $args;
}, 10, 2 );

add_action( 'admin_init', function () {
	register_setting( 'ggs_updates', 'ggs_github_token', array(
		'type'              => 'string',
		'sanitize_callback' => function ( $v ) {
			$v = trim( (string) $v );
			if ( '__keep__' === $v ) {
				return get_option( 'ggs_github_token', '' );
			}
			delete_site_transient( 'ggs_remote_versions' );
			return preg_replace( '/[^A-Za-z0-9_]/', '', $v );
		},
		'default'           => '',
	) );
} );

/** Updates section shown under Settings → GG Site. */
function ggs_updates_section() {
	$has    = '' !== (string) get_option( 'ggs_github_token', '' );
	$remote = ggs_remote_versions();
	$theme  = wp_get_theme( 'gg-theme' );
	echo '<hr><h2 id="updates">Updates</h2>';
	echo '<p>Installed: GG Site ' . esc_html( GGS_VER ) . ( $theme->exists() ? ' · GG Theme ' . esc_html( $theme->get( 'Version' ) ) : '' ) . '. ';
	echo $remote ? 'Latest published: GG Site ' . esc_html( $remote['gg-site'] ?? '–' ) . ' · GG Theme ' . esc_html( $remote['gg-theme'] ?? '–' ) . '.' : 'The repository could not be read just now (if it is private, add an access key below).';
	echo ' New versions appear on <a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">Dashboard → Updates</a>.</p>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'ggs_updates' );
	echo '<table class="form-table" role="presentation"><tr><th scope="row"><label for="ggs_github_token">GitHub access key</label></th><td>';
	echo '<input type="password" id="ggs_github_token" name="ggs_github_token" value="' . ( $has ? '__keep__' : '' ) . '" class="regular-text" autocomplete="off">';
	echo '<p class="description">Needed only if the gg-website repository is made private. ' . ( $has ? 'A key is saved. Clear the box and save to remove it.' : 'No key saved.' ) . '</p>';
	echo '</td></tr></table>';
	submit_button( 'Save access key' );
	echo '</form>';
}
