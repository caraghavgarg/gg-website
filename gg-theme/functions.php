<?php
/**
 * GG Theme setup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GGT_VER', '2.5.0' );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	register_nav_menus( array(
		'primary'   => 'Main website navigation',
		'utilities' => 'Bar: Utilities',
		'resources' => 'Bar: Resources',
		'bar_a'     => 'Bar: Extra A',
		'bar_b'     => 'Bar: Extra B',
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'gg-fonts', get_template_directory_uri() . '/assets/fonts.css', array(), GGT_VER );
	wp_enqueue_style( 'gg-theme', get_stylesheet_uri(), array( 'gg-fonts' ), GGT_VER );
	wp_enqueue_script( 'gg-theme', get_template_directory_uri() . '/assets/theme.js', array(), GGT_VER, true );
} );

/** Preload the two most-used font files. */
add_action( 'wp_head', function () {
	$base = get_template_directory_uri() . '/assets/fonts/';
	foreach ( array( 'noticia-text-latin-400-normal.woff2', 'source-sans-3-latin-400-normal.woff2' ) as $f ) {
		echo '<link rel="preload" href="' . esc_url( $base . $f ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
}, 1 );

/** If setup ran before this theme was active, attach the menus it created by name. */
add_action( 'after_switch_theme', 'ggt_attach_menus' );
add_action( 'init', function () {
	$loc = get_nav_menu_locations();
	if ( empty( $loc['primary'] ) || empty( $loc['utilities'] ) || empty( $loc['resources'] ) ) {
		ggt_attach_menus();
	}
} );
function ggt_attach_menus() {
	$loc = get_nav_menu_locations();
	foreach ( array( 'primary' => 'Main Menu', 'utilities' => 'Utilities Toolbar', 'resources' => 'Resources Bar', 'bar_a' => 'Extra Bar A', 'bar_b' => 'Extra Bar B' ) as $where => $name ) {
		if ( empty( $loc[ $where ] ) ) {
			$menu = wp_get_nav_menu_object( $name );
			if ( $menu ) {
				$loc[ $where ] = $menu->term_id;
			}
		}
	}
	set_theme_mod( 'nav_menu_locations', $loc );
}

/** Main menu: show each top item's Description as a second line ("About Us / Our firm"). */
add_filter( 'nav_menu_item_title', function ( $title, $item, $args, $depth ) {
	if ( isset( $args->theme_location ) && 'primary' === $args->theme_location && 0 === (int) $depth && ! empty( $item->description ) ) {
		$title .= '<small>' . esc_html( $item->description ) . '</small>';
	}
	return $title;
}, 10, 4 );

/** Settings live in the GG Site plugin; fall back to defaults if it is inactive. */
function ggt( $key ) {
	if ( function_exists( 'ggs_setting' ) ) {
		return ggs_setting( $key );
	}
	$d = array(
		'firm_name' => get_bloginfo( 'name' ),
		'tagline'   => get_bloginfo( 'description' ),
		'email'     => get_option( 'admin_email' ),
	);
	return (string) ( $d[ $key ] ?? '' );
}

function ggt_tel( $phone ) {
	return 'tel:' . preg_replace( '/[^0-9+]/', '', $phone );
}

/* ---------------- Icons (one inline sprite per page) ---------------- */

function ggt_icon_paths() {
	return array(
		'scale' => 'M11 3h2v2.1l5.8 1.2-.4 2-1.1-.2 2.7 6.4c0 1.8-1.6 3-3.5 3s-3.5-1.2-3.5-3l2.6-6.1L13 7.3V19h4v2H7v-2h4V7.3L8.4 8.4l2.6 6.1c0 1.8-1.6 3-3.5 3S4 16.3 4 14.5l2.7-6.4-1.1.2-.4-2L11 5.1V3zm-3.5 7.2L5.9 14h3.2l-1.6-3.8zm9 0L14.9 14h3.2l-1.6-3.8z',
		'chart' => 'M4 20V10h3v10H4zm6 0V4h3v16h-3zm6 0v-7h3v7h-3z',
		'doc'   => 'M6 2h8l5 5v15H6V2zm7 1.5V8h4.5L13 3.5zM8.5 11v1.6h8V11h-8zm0 3.2v1.6h8v-1.6h-8zm0 3.2V19h5.5v-1.6H8.5z',
		'case'  => 'M9 3h6l1 2h4v14H4V5h4l1-2zm1.2 2h3.6l-.4-.8h-2.8l-.4.8zM6 9v8h12V9H6zm5 2h2v2h-2v-2z',
		'bank'  => 'M12 2l10 5v2H2V7l10-5zM4 11h3v7H4v-7zm6.5 0h3v7h-3v-7zM17 11h3v7h-3v-7zM2 20h20v2H2v-2z',
		'calc'  => 'M6 2h12a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm1 3v4h10V5H7zm0 6v2h2v-2H7zm4 0v2h2v-2h-2zm4 0v6h2v-6h-2zm-8 4v2h2v-2H7zm4 0v2h2v-2h-2z',
		'cal'   => 'M7 2h2v2h6V2h2v2h3v18H4V4h3V2zM6 9v11h12V9H6zm2 2h3v3H8v-3z',
		'book'  => 'M4 3h7a3 3 0 0 1 1 .2A3 3 0 0 1 13 3h7v16h-7a2 2 0 0 0-1 .3 2 2 0 0 0-1-.3H4V3zm2 2v12h5V5.3A1 1 0 0 0 11 5H6zm7 .3V17h5V5h-4a1 1 0 0 0-1 .3z',
		'lock'  => 'M12 2a5 5 0 0 0-5 5v3H5v12h14V10h-2V7a5 5 0 0 0-5-5zm-3 8V7a3 3 0 1 1 6 0v3H9z',
		'pin'   => 'M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z',
		'mail'  => 'M3 5h18v14H3V5zm2 2v.3l7 4.6 7-4.6V7H5zm14 2.7-7 4.6-7-4.6V17h14V9.7z',
		'phone' => 'M7 2h4l1.5 5-2.3 1.5a11 11 0 0 0 5.3 5.3L17 11.5l5 1.5v4a3 3 0 0 1-3 3A16 16 0 0 1 4 5a3 3 0 0 1 3-3z',
		'clock' => 'M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zm0 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm-1 3h2v5.4l3.5 2.1-1 1.7L11 13.6V7z',
		'users' => 'M9 4a4 4 0 1 1 0 8 4 4 0 0 1 0-8zm-7 16c0-3.3 3.1-6 7-6s7 2.7 7 6v1H2v-1zM17 4.1A4 4 0 0 1 17 12a6 6 0 0 0 0-7.9zM18.5 14c2.1.8 3.5 2.5 3.5 4.5V21h-4v-1c0-2.4-1.3-4.5-3.2-5.8 1.2-.2 2.5-.2 3.7-.2z',
		'award' => 'M12 2a7 7 0 0 1 4.5 12.4L18 22l-6-3-6 3 1.5-7.6A7 7 0 0 1 12 2zm0 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z',
		'grow'  => 'M3 19h18v2H3v-2zm1-4 5-5 4 4 6-6V11h2V5h-6v2h2.6L13 11.6l-4-4-6.4 6.4L4 15z',
		'chat'  => 'M4 4h16v12H8l-4 4V4zm4 5v2h8V9H8z',
	);
}

add_action( 'wp_body_open', function () {
	echo '<svg class="svg-sprite" aria-hidden="true" focusable="false">';
	foreach ( ggt_icon_paths() as $id => $d ) {
		echo '<symbol id="gi-' . esc_attr( $id ) . '" viewBox="0 0 24 24"><path d="' . esc_attr( $d ) . '"/></symbol>';
	}
	echo '</svg>';
} );

function ggt_icon( $id ) {
	if ( ! isset( ggt_icon_paths()[ $id ] ) ) {
		$id = 'doc';
	}
	return '<svg aria-hidden="true" focusable="false"><use href="#gi-' . esc_attr( $id ) . '"/></svg>';
}

/** Icon for each service (by its address). */
function ggt_service_icon( $slug ) {
	if ( function_exists( 'ggs_services_data' ) ) {
		$d = ggs_services_data();
		if ( ! empty( $d['items'][ $slug ]['icon'] ) ) {
			return $d['items'][ $slug ]['icon'];
		}
	}
	$map = array(
		'direct-taxation'              => 'doc',
		'gst'                          => 'chart',
		'audit-assurance'              => 'scale',
		'litigation-representation'    => 'case',
		'corporate-law-roc-compliance' => 'bank',
		'accounting-bookkeeping'       => 'calc',
		'financial-advisory'           => 'grow',
		'business-consultancy'         => 'users',
	);
	return $map[ $slug ] ?? 'doc';
}

/** Services as round orange icons with title and summary (home page and Services page). */
function ggt_services_grid() {
	if ( ! function_exists( 'ggs_services' ) ) {
		return '';
	}
	$out = '<div class="svcs">';
	foreach ( ggs_services() as $s ) {
		$out .= '<a class="svc" href="' . esc_url( get_permalink( $s ) ) . '"><span class="circle">' . ggt_icon( ggt_service_icon( $s->post_name ) ) . '</span><div><h3>' . esc_html( get_the_title( $s ) ) . '</h3><p>' . esc_html( get_the_excerpt( $s ) ) . '</p></div></a>';
	}
	return $out . '</div>';
}

/* ---------------- Menu bars under the main menu (choose per page) ---------------- */

/** The bars that can appear under the main menu: location => [default label, shown by default]. */
function ggt_bar_defs() {
	return array(
		'utilities' => array( 'Utilities', true ),
		'resources' => array( 'Resources', true ),
		'bar_a'     => array( 'Quick links', false ),
		'bar_b'     => array( 'More', false ),
	);
}

function ggt_bar_label( $loc ) {
	$v = trim( (string) get_theme_mod( 'gg_bar_label_' . $loc, '' ) );
	return '' !== $v ? $v : ggt_bar_defs()[ $loc ][0];
}

/** Which bars to show on the current page: the page's own choice, else the site default. */
function ggt_bars_now() {
	$id = is_singular() ? get_queried_object_id() : ( is_home() ? (int) get_option( 'page_for_posts' ) : 0 );
	if ( $id && 'custom' === get_post_meta( $id, '_ggt_bars_mode', true ) ) {
		$chosen = array_filter( explode( ',', (string) get_post_meta( $id, '_ggt_bars', true ) ) );
		return array_values( array_intersect( array_keys( ggt_bar_defs() ), $chosen ) );
	}
	$out = array();
	foreach ( ggt_bar_defs() as $loc => $def ) {
		if ( get_theme_mod( 'gg_bar_default_' . $loc, $def[1] ) ) {
			$out[] = $loc;
		}
	}
	return $out;
}

function ggt_render_bars() {
	foreach ( ggt_bars_now() as $loc ) {
		if ( ! has_nav_menu( $loc ) ) {
			continue;
		}
		echo '<div class="tools bar--' . esc_attr( $loc ) . '"><div class="wrap">';
		echo '<span class="tools-label" aria-hidden="true">' . esc_html( ggt_bar_label( $loc ) ) . '</span>';
		echo '<nav class="toolbar-nav" aria-label="' . esc_attr( ggt_bar_label( $loc ) ) . '">';
		ggt_menu( $loc );
		echo '</nav>';
		if ( 'utilities' === $loc && ggt( 'staff_url' ) ) {
			echo '<a class="staff-login" href="' . esc_url( ggt( 'staff_url' ) ) . '" rel="nofollow">' . ggt_icon( 'lock' ) . 'Office Staff Login</a>'; // phpcs:ignore
		}
		echo '</div></div>';
	}
}

/** "Menu bars on this page" box on pages, posts and services. */
add_action( 'add_meta_boxes', function () {
	foreach ( array( 'page', 'post', 'gg_service' ) as $type ) {
		add_meta_box( 'ggt_bars', 'Menu bars on this page', 'ggt_bars_box', $type, 'side', 'default' );
	}
} );

function ggt_bars_box( $post ) {
	wp_nonce_field( 'ggt_bars_save', 'ggt_bars_nonce' );
	$mode   = get_post_meta( $post->ID, '_ggt_bars_mode', true ) === 'custom' ? 'custom' : 'default';
	$chosen = array_filter( explode( ',', (string) get_post_meta( $post->ID, '_ggt_bars', true ) ) );
	echo '<p><label><input type="radio" name="ggt_bars_mode" value="default"' . checked( $mode, 'default', false ) . '> Same as the rest of the site</label><br>';
	echo '<label><input type="radio" name="ggt_bars_mode" value="custom"' . checked( $mode, 'custom', false ) . '> Choose for this page:</label></p>';
	foreach ( ggt_bar_defs() as $loc => $def ) {
		echo '<p style="margin:4px 0 4px 22px"><label><input type="checkbox" name="ggt_bars[]" value="' . esc_attr( $loc ) . '"' . checked( in_array( $loc, $chosen, true ), true, false ) . '> ' . esc_html( ggt_bar_label( $loc ) ) . '</label></p>';
	}
	echo '<p class="description">Edit what each bar contains in Appearance → Menus. Rename bars in Appearance → Customize → Menu bars.</p>';
}

add_action( 'save_post', function ( $post_id ) {
	if ( ! isset( $_POST['ggt_bars_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ggt_bars_nonce'] ) ), 'ggt_bars_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$mode = ( isset( $_POST['ggt_bars_mode'] ) && 'custom' === $_POST['ggt_bars_mode'] ) ? 'custom' : 'default';
	$bars = isset( $_POST['ggt_bars'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['ggt_bars'] ) ) : array();
	$bars = array_intersect( array_keys( ggt_bar_defs() ), $bars );
	update_post_meta( $post_id, '_ggt_bars_mode', $mode );
	update_post_meta( $post_id, '_ggt_bars', implode( ',', $bars ) );
} );

add_action( 'customize_register', function ( $wp ) {
	$wp->add_section( 'gg_bars', array( 'title' => 'Menu bars (GG Theme)', 'priority' => 31, 'description' => 'Bars shown under the main menu on every page that has not chosen its own. Each page can override this in its "Menu bars on this page" box.' ) );
	foreach ( ggt_bar_defs() as $loc => $def ) {
		$wp->add_setting( 'gg_bar_default_' . $loc, array( 'default' => $def[1], 'sanitize_callback' => 'wp_validate_boolean' ) );
		$wp->add_control( 'gg_bar_default_' . $loc, array( 'label' => 'Show the "' . $def[0] . '" bar by default', 'section' => 'gg_bars', 'type' => 'checkbox' ) );
		$wp->add_setting( 'gg_bar_label_' . $loc, array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp->add_control( 'gg_bar_label_' . $loc, array( 'label' => 'Label for this bar', 'section' => 'gg_bars', 'type' => 'text', 'input_attrs' => array( 'placeholder' => $def[0] ) ) );
	}
} );

/* ---------------- Customizer: home page text (no code needed) ---------------- */

function ggt_home_defaults() {
	return array(
		'head_line1'  => 'In practice in Kurukshetra since 1984',
		'head_line2'  => 'ICAI Firm Reg. No. 018813N',
		'tiles'       => "About Us | Our firm since 1984 | /about/ | about | award\nServices | What we do | /services/ | services | case\nOur Team | Partners & staff | /team/ | team | users\nResources | Acts, Rules & publications | /acts-rules/ | resources | book\nCalculators | Tax & GST tools | /calculators/ | calculators | calc\nContact | Get in touch | /contact/ | contact | phone",
		'welcome'     => 'Tax, audit and compliance, looked after properly.',
		'creds'       => "Since 1984 | Practice founded by CA Ashwani Kumar Garg; reconstituted as Garg Garg & Associates in 2002. | clock\nICAI registered | Firm registration no. 018813N. Partners holding FCA, LLB, LLM and DISA. | award\nRBI & CAG | Empanelled for bank and government audits. | bank\nGroup of three | Garg Garg & Associates, Garg Raghav & Associates and GG Associates Pvt. Ltd. | users",
		'clients'     => "Individuals & families\nTraders & MSMEs\nCompanies & LLPs\nTrusts, societies & NGOs\nBanks & government bodies\nNon-resident Indians",
		'cta_title'   => 'Speak to a partner about your requirement',
		'cta_text'    => 'Sector 7, Urban Estate, Kurukshetra · mail@ggassociates.in',
	);
}

function ggt_mod( $key ) {
	$v = get_theme_mod( 'gg_' . $key, '' );
	return '' !== trim( (string) $v ) ? (string) $v : ( ggt_home_defaults()[ $key ] ?? '' );
}

/** "a | b | c" lines → arrays. */
function ggt_lines( $key ) {
	$out = array();
	foreach ( preg_split( '/\r?\n/', ggt_mod( $key ) ) as $line ) {
		if ( '' === trim( $line ) ) {
			continue;
		}
		$out[] = array_map( 'trim', explode( '|', $line ) );
	}
	return $out;
}

add_action( 'customize_register', function ( $wp ) {
	$wp->add_section( 'gg_home', array( 'title' => 'Home page (GG Theme)', 'priority' => 30 ) );
	$fields = array(
		'head_line1' => array( 'Header: first line beside the logo', 'text' ),
		'head_line2' => array( 'Header: second line', 'text' ),
		'tiles'      => array( 'Picture tiles (one per line: title | subtitle | link | picture | icon). Pictures: about, services, team, resources, calculators, contact', 'textarea' ),
		'welcome'    => array( 'Welcome heading (the text under it is the Home page content)', 'text' ),
		'creds'      => array( 'Experience & Credentials boxes (one per line: title | text | icon)', 'textarea' ),
		'clients'    => array( 'Clients we serve (one per line)', 'textarea' ),
		'cta_title'  => array( 'Appointment band: heading', 'text' ),
		'cta_text'   => array( 'Appointment band: line under the heading', 'text' ),
	);
	foreach ( $fields as $key => $def ) {
		$wp->add_setting( 'gg_' . $key, array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
		$wp->add_control( 'gg_' . $key, array(
			'label'       => $def[0],
			'section'     => 'gg_home',
			'type'        => $def[1],
			'input_attrs' => array( 'placeholder' => ggt_home_defaults()[ $key ] ),
		) );
	}
} );

/* ---------------- Helpers used by templates ---------------- */

function ggt_page_hero( $title, $lead = '', $crumbs = array() ) {
	echo '<section class="page-hero"><div class="wrap">';
	echo '<nav class="crumbs" aria-label="Breadcrumb"><a href="' . esc_url( home_url( '/' ) ) . '">Home</a>';
	foreach ( $crumbs as $label => $url ) {
		echo ' / <a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
	echo '</nav><h1>' . esc_html( $title ) . '</h1>';
	if ( $lead ) {
		echo '<p class="gg-lead">' . esc_html( $lead ) . '</p>';
	}
	echo '</div></section>';
}

/** Service → matching Acts & Rules page. */
function ggt_service_hub( $slug ) {
	if ( function_exists( 'ggs_services_data' ) ) {
		$d = ggs_services_data();
		if ( ! empty( $d['items'][ $slug ]['hub'] ) ) {
			return $d['items'][ $slug ]['hub'];
		}
	}
	$map = array(
		'direct-taxation'              => array( 'income-tax', 'Income Tax: Acts, Rules & Forms' ),
		'gst'                          => array( 'gst', 'GST: Acts, Rules & Forms' ),
		'litigation-representation'    => array( 'income-tax', 'Income Tax: Acts, Rules & Forms' ),
		'corporate-law-roc-compliance' => array( 'mca-roc', 'MCA & ROC: Acts, Rules & Forms' ),
		'audit-assurance'              => array( 'mca-roc', 'MCA & ROC: Acts, Rules & Forms' ),
		'business-consultancy'         => array( 'labour-law', 'Labour Law: Codes, Rules & Forms' ),
		'financial-advisory'           => array( 'others', 'FEMA, IBC & other laws' ),
		'accounting-bookkeeping'       => array( 'gst', 'GST: Acts, Rules & Forms' ),
	);
	return $map[ $slug ] ?? null;
}

function ggt_menu( $location ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu( array(
			'theme_location' => $location,
			'container'      => false,
			'menu_class'     => 'menu',
			'depth'          => 2,
			'fallback_cb'    => false,
		) );
	} elseif ( current_user_can( 'manage_options' ) ) {
		echo '<ul class="menu"><li><a href="' . esc_url( admin_url( 'tools.php?page=gg-site-setup' ) ) . '">Run GG Site Setup to build this menu</a></li></ul>';
	}
}

/** Appointment band used at the end of pages. */
function ggt_cta() {
	echo '<div class="wrap"><section class="cta"><h2>' . esc_html( ggt_mod( 'cta_title' ) ) . '</h2><p>' . esc_html( ggt_mod( 'cta_text' ) ) . '</p><a class="gg-btn gg-btn--orange" href="' . esc_url( home_url( '/contact/#enquiry' ) ) . '">Request an appointment</a></section></div>';
}

/** Organisation details for search engines (front page only). */
add_action( 'wp_head', function () {
	if ( ! is_front_page() ) {
		return;
	}
	$data = array(
		'@context'  => 'https://schema.org',
		'@type'     => 'AccountingService',
		'name'      => ggt( 'firm_name' ),
		'url'       => home_url( '/' ),
		'telephone' => ggt( 'phone' ),
		'email'     => ggt( 'email' ),
		'address'   => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => 'Kothi No. 35, First Floor, Sector 7, Urban Estate',
			'addressLocality' => 'Kurukshetra',
			'addressRegion'   => 'Haryana',
			'postalCode'      => '136118',
			'addressCountry'  => 'IN',
		),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
} );

/** No emoji script: it turns arrows such as ↗ into images and adds a request on every page. */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
add_filter( 'emoji_svg_url', '__return_false' );

/** Archive titles without the "Category:" prefix. */
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

add_filter( 'excerpt_length', function () { return 30; } );
add_filter( 'excerpt_more', function () { return '…'; } );
