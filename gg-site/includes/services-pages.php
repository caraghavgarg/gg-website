<?php
/**
 * Service pages. Content lives in data/services.json; each service post holds
 * [gg_service_page slug="..."] so wording and layout update with the plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ggs_services_data() {
	static $data = null;
	if ( null === $data ) {
		$data = json_decode( (string) file_get_contents( GGS_DIR . 'data/services.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! is_array( $data ) ) {
			$data = array( 'order' => array(), 'items' => array() );
		}
	}
	return $data;
}

function ggs_service_img_url( $slug ) {
	$d = ggs_services_data();
	if ( empty( $d['items'][ $slug ]['img'] ) ) {
		return '';
	}
	return GGS_URL . 'assets/img/services/' . sanitize_key( $d['items'][ $slug ]['img'] ) . '.svg?v=' . GGS_VER;
}

add_shortcode( 'gg_service_page', function ( $atts ) {
	$a    = shortcode_atts( array( 'slug' => '' ), $atts );
	$d    = ggs_services_data();
	$slug = sanitize_title( $a['slug'] );
	if ( empty( $d['items'][ $slug ] ) ) {
		return '';
	}
	$it = $d['items'][ $slug ];
	$o  = '<div class="sv-intro"><span class="gs-pic gs-pic--hero"><img src="' . esc_url( ggs_service_img_url( $slug ) ) . '" alt="" width="400" height="300"></span><div>';
	foreach ( $it['intro'] as $p ) {
		$o .= '<p>' . esc_html( $p ) . '</p>';
	}
	$o .= '</div></div>';
	$o .= '<nav class="gg-chips" aria-label="Sections"><a href="#what"><span>01</span> What we do</a><a href="#who"><span>02</span> Who needs it</a><a href="#how"><span>03</span> How we work</a><a href="#enquiry-box"><span>04</span> Send your requirement</a></nav>';
	$o .= '<section class="gg-hub-sec" id="what"><h2><span class="gg-num">01</span> What we do</h2><div class="sv-items">';
	foreach ( $it['items'] as $i => $row ) {
		$o .= '<div class="sv-item"><span class="sv-n">' . sprintf( '%02d', $i + 1 ) . '</span><h3>' . esc_html( $row[0] ) . '</h3><p>' . esc_html( $row[1] ) . '</p></div>';
	}
	$o .= '</div></section>';
	$o .= '<section class="gg-hub-sec" id="who"><h2><span class="gg-num">02</span> Who needs it</h2><ul class="gs-list gs-list--plus">';
	foreach ( $it['who'] as $w ) {
		$o .= '<li>' . esc_html( $w ) . '</li>';
	}
	$o .= '</ul></section>';
	$o .= '<section class="gg-hub-sec" id="how"><h2><span class="gg-num">03</span> How we work</h2><ol class="sv-steps">';
	foreach ( $it['how'] as $st ) {
		$o .= '<li><b>' . esc_html( $st[0] ) . '</b><span>' . esc_html( $st[1] ) . '</span></li>';
	}
	$o .= '</ol>';
	if ( ! empty( $it['faq'] ) ) {
		$o .= '<div class="sv-faq">';
		foreach ( $it['faq'] as $q ) {
			$o .= '<details><summary>' . esc_html( $q[0] ) . '</summary><p>' . esc_html( $q[1] ) . '</p></details>';
		}
		$o .= '</div>';
	}
	$o .= '</section>';
	$o .= '<section class="gg-hub-sec" id="enquiry-box"><h2><span class="gg-num">04</span> Send your requirement</h2><p>Tell us briefly what you need. Your request reaches our office by email and we will get back to you.</p>'
		. do_shortcode( '[gg_enquiry service="' . esc_attr( $it['title'] ) . '"]' ) . '</section>';
	return $o . '<p class="gg-note">General information, not professional advice. Limits, rates and due dates change; please confirm before acting.</p>';
} );

/**
 * Bring the service posts in line with data/services.json: rename the old services
 * to their new addresses (old links keep working), update content and order,
 * create missing ones and move retired ones to Trash.
 */
function ggs_sync_services() {
	$d     = ggs_services_data();
	$count = array( 'updated' => 0, 'created' => 0, 'retired' => 0 );
	foreach ( (array) ( $d['old_slugs'] ?? array() ) as $old => $new ) {
		$old_post = get_page_by_path( $old, OBJECT, 'gg_service' );
		$new_post = get_page_by_path( $new, OBJECT, 'gg_service' );
		if ( $old_post && ! $new_post ) {
			wp_update_post( array( 'ID' => $old_post->ID, 'post_name' => $new ) );
		}
	}
	foreach ( $d['order'] as $i => $slug ) {
		$it   = $d['items'][ $slug ];
		$post = get_page_by_path( $slug, OBJECT, 'gg_service' );
		$args = array(
			'post_type'    => 'gg_service',
			'post_status'  => 'publish',
			'post_title'   => $it['title'],
			'post_name'    => $slug,
			'post_excerpt' => $it['excerpt'],
			'post_content' => "<!-- wp:shortcode -->\n[gg_service_page slug=\"" . $slug . "\"]\n<!-- /wp:shortcode -->",
			'menu_order'   => $i + 1,
		);
		if ( $post ) {
			$args['ID'] = $post->ID;
			wp_update_post( $args );
			$id = $post->ID;
			$count['updated']++;
		} else {
			$id = wp_insert_post( $args );
			$count['created']++;
		}
		update_post_meta( $id, '_gg_tags', $it['tags'] );
	}
	foreach ( (array) ( $d['retire'] ?? array() ) as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'gg_service' );
		if ( $post && 'trash' !== $post->post_status ) {
			wp_trash_post( $post->ID );
			$count['retired']++;
		}
	}
	return $count;
}
