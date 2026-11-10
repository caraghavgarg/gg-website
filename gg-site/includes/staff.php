<?php
/**
 * Office Staff Login: a page that sends staff to their own mail sign-in
 * (Google Workspace for @ggassociates.in, Zoho Mail for @gargraghav.in).
 * No staff accounts are kept on this website.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ggs_staff_mail_boxes() {
	return array(
		array(
			'name'   => 'Google Workspace',
			'domain' => '@ggassociates.in',
			'url'    => 'https://mail.google.com/a/ggassociates.in',
			'app'    => 'Gmail app',
			'color'  => '#4285f4',
			'icon'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285f4" d="M2 6.5 12 13l10-6.5V18a2 2 0 0 1-2 2h-2V9.8l-6 3.9-6-3.9V20H4a2 2 0 0 1-2-2z"/><path fill="#ea4335" d="M2 6a2 2 0 0 1 3.2-1.6L12 9l6.8-4.6A2 2 0 0 1 22 6v.5L12 13 2 6.5z"/></svg>',
		),
		array(
			'name'   => 'Zoho Mail',
			'domain' => '@gargraghav.in',
			'url'    => 'https://mail.zoho.com',
			'app'    => 'Zoho Mail app',
			'color'  => '#e42527',
			'icon'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2" fill="#e42527"/><path d="M4 8l8 5 8-5" fill="none" stroke="#fff" stroke-width="1.8"/></svg>',
		),
	);
}

add_shortcode( 'gg_staff_login', function () {
	$o = '<p class="gg-staff-lead">Choose the mailbox for your office email address. You will sign in on Google\'s or Zoho\'s own page; this website does not store staff passwords.</p><div class="gg-staff">';
	foreach ( ggs_staff_mail_boxes() as $b ) {
		$o .= '<a class="gg-staff-card" href="' . esc_url( $b['url'] ) . '" target="_blank" rel="noopener nofollow" style="--brand:' . esc_attr( $b['color'] ) . '">'
			. '<span class="gg-staff-ico">' . $b['icon'] . '</span>' // phpcs:ignore -- fixed SVG above.
			. '<span class="gg-staff-txt"><b>' . esc_html( $b['name'] ) . '</b><span>For addresses ending ' . esc_html( $b['domain'] ) . '</span></span>'
			. '<span class="gg-staff-go">Open mail &rarr;</span></a>';
	}
	$o .= '</div><ul class="gs-list gg-staff-notes">'
		. '<li>On your phone, use the Gmail app (Google Workspace) or the Zoho Mail app and sign in with your office address.</li>'
		. '<li>Forgot your password or locked out? Ask the office administrator to reset it. Do not share your password with anyone.</li>'
		. '<li>Using a shared office computer? Sign out when you finish.</li>'
		. '</ul>';
	return $o;
} );

/** Keep the staff page out of search engines. */
add_filter( 'wp_robots', function ( $robots ) {
	if ( is_page( 'staff-login' ) ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
} );

add_action( 'wp_head', function () {
	if ( ! is_page( 'staff-login' ) ) {
		return;
	}
	echo '<style id="gg-staff-css">'
		. '.gg-staff{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;margin:26px 0}'
		. '.gg-staff-card{display:flex;align-items:center;gap:18px;padding:24px 22px;background:var(--panel,#3b3d41);border:1px solid var(--rule,#55585e);border-left:4px solid var(--orange,#f36a17);color:var(--text,#fff);text-decoration:none;transition:border-color .2s,transform .2s}'
		. '.gg-staff-card:hover,.gg-staff-card:focus-visible{border-color:var(--orange,#f36a17);transform:translateY(-2px);color:#fff}'
		. '.gg-staff-ico{flex:0 0 52px;height:52px;display:grid;place-items:center;background:#fff;border-radius:6px}.gg-staff-ico svg{width:32px;height:32px}'
		. '.gg-staff-txt{flex:1;display:flex;flex-direction:column;gap:4px}.gg-staff-txt b{font-size:1.2rem}.gg-staff-txt span{color:var(--muted,#c9cbd0);font-size:.95rem}'
		. '.gg-staff-go{font-weight:600;color:var(--orange-2,#ff8f45);white-space:nowrap}'
		. '@media(max-width:520px){.gg-staff-card{flex-wrap:wrap}.gg-staff-go{width:100%}}'
		. '</style>';
} );

/**
 * One-time step after the update: create the page and switch on the header button.
 */
function ggs_staff_install() {
	$page = get_page_by_path( 'staff-login' );
	if ( ! $page ) {
		$id = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Office Staff Login',
			'post_name'    => 'staff-login',
			'post_content' => "<!-- wp:shortcode -->\n[gg_staff_login]\n<!-- /wp:shortcode -->",
		) );
	} else {
		$id = $page->ID;
	}
	$opts = get_option( 'ggs_settings', array() );
	if ( is_array( $opts ) && empty( $opts['staff_url'] ) && $id ) {
		$opts['staff_url'] = get_permalink( $id );
		update_option( 'ggs_settings', $opts );
	}
}

add_action( 'admin_init', function () {
	if ( (int) get_option( 'ggs_staff_ver' ) < 1 && current_user_can( 'manage_options' ) ) {
		ggs_staff_install();
		update_option( 'ggs_staff_ver', 1 );
	}
} );
