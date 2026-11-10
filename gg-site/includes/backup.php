<?php
/**
 * GG quick backup: Tools → GG Backup.
 * Lets an administrator download, on demand,
 *  - the database as a compressed SQL file, or
 *  - a full copy: database + everything in wp-content (theme, plugins, uploads) in one zip.
 * Files are built in a protected folder, sent to the browser and deleted at once.
 * Scheduled backups and one-click restore are handled by UpdraftPlus.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', function () {
	add_management_page( 'GG Backup', 'GG Backup', 'manage_options', 'gg-backup', 'ggs_backup_page' );
} );

function ggs_backup_dir() {
	$up  = wp_upload_dir();
	$dir = trailingslashit( $up['basedir'] ) . 'gg-backups';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	if ( ! file_exists( $dir . '/.htaccess' ) ) {
		file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	if ( ! file_exists( $dir . '/index.php' ) ) {
		file_put_contents( $dir . '/index.php', "<?php // Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $dir;
}

/** Folders inside wp-content that are caches or other backups, not part of the site. */
function ggs_backup_skip( $rel ) {
	$skip = array( 'uploads/gg-backups', 'updraft', 'cache', 'litespeed', 'upgrade', 'upgrade-temp-backup', 'wflogs', 'ai1wm-backups', 'backups-dup-lite', 'debug.log' );
	foreach ( $skip as $s ) {
		if ( $rel === $s || 0 === strpos( $rel, $s . '/' ) ) {
			return true;
		}
	}
	return false;
}

function ggs_backup_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$size  = 0;
	$count = 0;
	$it    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( WP_CONTENT_DIR, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $f ) {
		$rel = ltrim( str_replace( '\\', '/', substr( $f->getPathname(), strlen( WP_CONTENT_DIR ) ) ), '/' );
		if ( $f->isFile() && ! ggs_backup_skip( $rel ) ) {
			$size += $f->getSize();
			$count++;
		}
	}
	$zip_ok = class_exists( 'ZipArchive' );
	$url    = admin_url( 'admin-post.php' );
	echo '<div class="wrap"><h1>GG Backup</h1>';
	if ( isset( $_GET['ggs_backup_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-error"><p>The backup could not be created on this server. Please use UpdraftPlus instead.</p></div>';
	}
	echo '<p>Download a copy of the website to your computer at any time. Nothing is kept on the server: the file is built, downloaded and deleted at once.</p>';
	echo '<table class="widefat striped" style="max-width:760px"><tbody>';
	echo '<tr><td><strong>Database only</strong><br><span class="description">All pages, settings, enquiries, team, services and users. Usually small.</span></td><td style="text-align:right;vertical-align:middle">';
	echo '<form method="post" action="' . esc_url( $url ) . '"><input type="hidden" name="action" value="ggs_backup_db">' . wp_nonce_field( 'ggs_backup', '_wpnonce', true, false ) . '<button class="button button-primary">Download database (.sql.gz)</button></form></td></tr>';
	echo '<tr><td><strong>Full website</strong><br><span class="description">Database plus wp-content: theme, plugins and uploaded files (' . esc_html( number_format_i18n( $count ) ) . ' files, about ' . esc_html( size_format( $size ) ) . ' before compression).</span></td><td style="text-align:right;vertical-align:middle">';
	if ( $zip_ok ) {
		echo '<form method="post" action="' . esc_url( $url ) . '"><input type="hidden" name="action" value="ggs_backup_full">' . wp_nonce_field( 'ggs_backup', '_wpnonce', true, false ) . '<button class="button button-primary">Download full backup (.zip)</button></form>';
	} else {
		echo '<em>Zip support is not available on this server.</em>';
	}
	echo '</td></tr></tbody></table>';
	echo '<h2>Restoring</h2><ul style="list-style:disc;margin-left:20px;max-width:760px">';
	echo '<li><strong>Easiest:</strong> use <a href="' . esc_url( admin_url( 'options-general.php?page=updraftplus' ) ) . '">UpdraftPlus</a> → Existing backups → Restore. It keeps scheduled copies and restores in one click.</li>';
	echo '<li><strong>From a GG Backup file:</strong> import <code>database.sql</code> with phpMyAdmin in your hosting panel, and upload the <code>wp-content</code> folder with the hosting File Manager. The file <code>wp-config.php</code> (which holds the database password) is deliberately not included; keep the one already on the server.</li>';
	echo '<li>Your host (MilesWeb) also keeps daily backups that can be restored from the hosting panel.</li></ul>';
	echo '<p class="description">Keep downloaded backups somewhere safe: they contain enquiries and user accounts.</p></div>';
}

/** Write the database to a gzip file. Returns the path or false. */
function ggs_backup_write_db( $path ) {
	global $wpdb;
	$gz = gzopen( $path, 'wb6' );
	if ( ! $gz ) {
		return false;
	}
	gzwrite( $gz, "-- GG Backup of " . home_url() . ' on ' . gmdate( 'Y-m-d H:i:s' ) . " UTC\n-- WordPress " . get_bloginfo( 'version' ) . "\nSET NAMES utf8mb4;\nSET foreign_key_checks = 0;\nSET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n\n" );
	$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix ) . '%' ) );
	foreach ( (array) $tables as $t ) {
		$create = $wpdb->get_row( "SHOW CREATE TABLE `{$t}`", ARRAY_N ); // phpcs:ignore WordPress.DB
		if ( empty( $create[1] ) ) {
			continue;
		}
		gzwrite( $gz, "DROP TABLE IF EXISTS `{$t}`;\n" . $create[1] . ";\n\n" );
		$offset = 0;
		do {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$t}` LIMIT %d, 500", $offset ), ARRAY_A ); // phpcs:ignore WordPress.DB
			if ( $rows ) {
				$vals = array();
				foreach ( $rows as $r ) {
					$cells = array();
					foreach ( $r as $v ) {
						$cells[] = null === $v ? 'NULL' : "'" . $wpdb->_real_escape( $v ) . "'";
					}
					$vals[] = '(' . implode( ',', $cells ) . ')';
				}
				$cols = '`' . implode( '`,`', array_keys( $rows[0] ) ) . '`';
				gzwrite( $gz, "INSERT INTO `{$t}` ({$cols}) VALUES\n" . implode( ",\n", $vals ) . ";\n" );
			}
			$offset += 500;
		} while ( $rows && count( $rows ) === 500 );
		gzwrite( $gz, "\n" );
	}
	gzwrite( $gz, "SET foreign_key_checks = 1;\n" );
	gzclose( $gz );
	return $path;
}

function ggs_backup_send( $path, $name, $type ) {
	if ( ! $path || ! file_exists( $path ) ) {
		wp_safe_redirect( admin_url( 'tools.php?page=gg-backup&ggs_backup_error=1' ) );
		exit;
	}
	while ( ob_get_level() ) {
		ob_end_clean();
	}
	nocache_headers();
	header( 'Content-Type: ' . $type );
	header( 'Content-Disposition: attachment; filename="' . $name . '"' );
	header( 'Content-Length: ' . filesize( $path ) );
	readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	wp_delete_file( $path );
	exit;
}

function ggs_backup_guard() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ggs_backup' ) ) {
		wp_die( 'Not allowed.' );
	}
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 0 ); // phpcs:ignore
	}
	ignore_user_abort( true );
	wp_raise_memory_limit( 'admin' );
}

function ggs_backup_name( $ext ) {
	$host = preg_replace( '/[^a-z0-9.-]/', '', strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) );
	return $host . '-backup-' . wp_date( 'Y-m-d-Hi' ) . $ext;
}

add_action( 'admin_post_ggs_backup_db', function () {
	ggs_backup_guard();
	$path = ggs_backup_dir() . '/db-' . wp_generate_password( 20, false ) . '.sql.gz';
	ggs_backup_send( ggs_backup_write_db( $path ), ggs_backup_name( '-database.sql.gz' ), 'application/gzip' );
} );

add_action( 'admin_post_ggs_backup_full', function () {
	ggs_backup_guard();
	if ( ! class_exists( 'ZipArchive' ) ) {
		wp_safe_redirect( admin_url( 'tools.php?page=gg-backup&ggs_backup_error=1' ) );
		exit;
	}
	$dir  = ggs_backup_dir();
	$key  = wp_generate_password( 20, false );
	$sqlg = ggs_backup_write_db( $dir . '/db-' . $key . '.sql.gz' );
	$zip  = new ZipArchive();
	$path = $dir . '/full-' . $key . '.zip';
	if ( ! $sqlg || true !== $zip->open( $path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		wp_safe_redirect( admin_url( 'tools.php?page=gg-backup&ggs_backup_error=1' ) );
		exit;
	}
	$zip->addFile( $sqlg, 'database.sql.gz' );
	$zip->addFromString( 'README.txt', "GG Backup of " . home_url() . "\nCreated " . wp_date( 'j M Y H:i' ) . "\n\ndatabase.sql.gz  - the database (import with phpMyAdmin)\nwp-content/      - theme, plugins and uploaded files\n\nwp-config.php is not included on purpose; keep the one on the server.\n" );
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( WP_CONTENT_DIR, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $f ) {
		if ( ! $f->isFile() ) {
			continue;
		}
		$rel = ltrim( str_replace( '\\', '/', substr( $f->getPathname(), strlen( WP_CONTENT_DIR ) ) ), '/' );
		if ( ! ggs_backup_skip( $rel ) && is_readable( $f->getPathname() ) ) {
			$zip->addFile( $f->getPathname(), 'wp-content/' . $rel );
		}
	}
	if ( file_exists( ABSPATH . '.htaccess' ) ) {
		$zip->addFile( ABSPATH . '.htaccess', '.htaccess' );
	}
	$zip->close();
	wp_delete_file( $sqlg );
	ggs_backup_send( $path, ggs_backup_name( '-full.zip' ), 'application/zip' );
} );
