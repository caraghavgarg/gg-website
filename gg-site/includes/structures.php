<?php
/**
 * Business structures: Company, LLP, Partnership firm, Proprietorship, Trust / Society.
 * Content lives in data/structures.json. Provides:
 *  - ggs_structures_strip(): the moving picture strip for the home page (called by the theme);
 *  - [gg_structures]: overview page with the comparison table;
 *  - [gg_structure slug="..."]: one structure's page with About, Benefits, Must understand, enquiry box.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ggs_structures() {
	static $data = null;
	if ( null === $data ) {
		$raw  = file_get_contents( GGS_DIR . 'data/structures.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$data = json_decode( (string) $raw, true );
		if ( ! is_array( $data ) ) {
			$data = array( 'order' => array(), 'items' => array() );
		}
	}
	return $data;
}

function ggs_structure_url( $slug ) {
	return home_url( '/business-structures/' . $slug . '/' );
}

function ggs_structure_img( $item ) {
	return GGS_URL . 'assets/img/structures/' . sanitize_key( $item['img'] ) . '.svg?v=' . GGS_VER;
}

/** Moving strip of pictures for the home page. The list is repeated once so the loop is seamless. */
function ggs_structures_strip() {
	$d = ggs_structures();
	if ( empty( $d['order'] ) ) {
		return '';
	}
	$cards = '';
	foreach ( $d['order'] as $slug ) {
		$it     = $d['items'][ $slug ];
		$cards .= '<a class="gs-card" href="' . esc_url( ggs_structure_url( $slug ) ) . '">'
			. '<span class="gs-pic"><img src="' . esc_url( ggs_structure_img( $it ) ) . '" alt="" width="400" height="300" loading="lazy"></span>'
			. '<span class="gs-text"><b>' . esc_html( $it['title'] ) . '</b><small>' . esc_html( $it['tag'] ) . '</small></span></a>';
	}
	$dup = str_replace( '<a class="gs-card"', '<a class="gs-card" aria-hidden="true" tabindex="-1"', $cards );
	return '<section class="sec gs-sec" aria-labelledby="gs-title">'
		. '<div class="gs-head"><h2 class="sec-title" id="gs-title">Starting a business?</h2>'
		. '<p>Compare the five common structures: what each one is, its benefits, and what to understand before you choose it.</p>'
		. '<a class="gs-all" href="' . esc_url( home_url( '/business-structures/' ) ) . '">Compare all →</a></div>'
		. '<div class="gs-marquee"><div class="gs-track">' . $cards . $dup . '</div></div></section>';
}

/** Overview page: cards plus a side-by-side table. */
add_shortcode( 'gg_structures', function () {
	$d = ggs_structures();
	$o = '<p class="gg-lead">The structure you choose decides who is liable for the business\'s debts, how it is taxed, how much compliance it carries and how easily it can grow or raise money. Choose a structure to read about it in detail.</p>';
	$o .= '<div class="gs-grid">';
	foreach ( $d['order'] as $slug ) {
		$it = $d['items'][ $slug ];
		$o .= '<a class="gs-card gs-card--grid" href="' . esc_url( ggs_structure_url( $slug ) ) . '"><span class="gs-pic"><img src="' . esc_url( ggs_structure_img( $it ) ) . '" alt="" width="400" height="300" loading="lazy"></span><span class="gs-text"><b>' . esc_html( $it['title'] ) . '</b><small>' . esc_html( $it['tag'] ) . '</small></span></a>';
	}
	$o .= '</div><section class="gg-hub-sec"><h2>At a glance</h2><div class="gs-table-wrap"><table class="gg-table gs-table"><thead><tr><th scope="col"></th>';
	foreach ( $d['order'] as $slug ) {
		$o .= '<th scope="col"><a href="' . esc_url( ggs_structure_url( $slug ) ) . '">' . esc_html( $d['items'][ $slug ]['title'] ) . '</a></th>';
	}
	$o .= '</tr></thead><tbody>';
	$first = $d['items'][ $d['order'][0] ]['facts'];
	foreach ( $first as $i => $row ) {
		$o .= '<tr><th scope="row">' . esc_html( $row[0] ) . '</th>';
		foreach ( $d['order'] as $slug ) {
			$o .= '<td>' . esc_html( $d['items'][ $slug ]['facts'][ $i ][1] ?? '' ) . '</td>';
		}
		$o .= '</tr>';
	}
	$o .= '</tbody></table></div></section>';
	return $o . '<p class="gg-note">General information on the law as it stands for Tax Year 2026-27. Rates, limits and procedures change; please confirm before acting.</p>';
} );

/** One structure's page. */
add_shortcode( 'gg_structure', function ( $atts ) {
	$a  = shortcode_atts( array( 'slug' => '' ), $atts );
	$d  = ggs_structures();
	$sl = sanitize_key( str_replace( '-', '_', $a['slug'] ) );
	$sl = str_replace( '_', '-', $sl );
	if ( empty( $d['items'][ $sl ] ) ) {
		return '';
	}
	$it   = $d['items'][ $sl ];
	$list = function ( $rows, $cls ) {
		return '<ul class="gs-list ' . esc_attr( $cls ) . '">' . implode( '', array_map( function ( $r ) {
			return '<li>' . esc_html( $r ) . '</li>';
		}, $rows ) ) . '</ul>';
	};
	$o  = '<div class="gs-intro"><span class="gs-pic gs-pic--hero"><img src="' . esc_url( ggs_structure_img( $it ) ) . '" alt="" width="400" height="300"></span>';
	$o .= '<div><p class="gg-lead">' . esc_html( $it['tag'] ) . '.</p><dl class="gs-facts">';
	foreach ( $it['facts'] as $f ) {
		$o .= '<dt>' . esc_html( $f[0] ) . '</dt><dd>' . esc_html( $f[1] ) . '</dd>';
	}
	$o .= '</dl></div></div>';
	$o .= '<nav class="gg-chips" aria-label="Sections"><a href="#about"><span>01</span> About</a><a href="#benefits"><span>02</span> Benefits</a><a href="#before"><span>03</span> Must understand before creating</a><a href="#enquiry"><span>04</span> Send your requirement</a></nav>';
	$o .= '<section class="gg-hub-sec" id="about"><h2><span class="gg-num">01</span> About the ' . esc_html( $it['full'] ) . '</h2>';
	foreach ( $it['about'] as $p ) {
		$o .= '<p>' . esc_html( $p ) . '</p>';
	}
	$o .= '</section><section class="gg-hub-sec" id="benefits"><h2><span class="gg-num">02</span> Benefits</h2>' . $list( $it['benefits'], 'gs-list--plus' ) . '</section>';
	$o .= '<section class="gg-hub-sec" id="before"><h2><span class="gg-num">03</span> Must understand before creating</h2>' . $list( $it['cons'], 'gs-list--minus' );
	$o .= '<p class="gs-needs"><b>Usually needed to register:</b> ' . esc_html( $it['needs'] ) . '</p></section>';
	$o .= '<section class="gg-hub-sec" id="enquiry-box"><h2><span class="gg-num">04</span> Send your requirement</h2><p>Tell us about the business you plan to set up. Your details go to our office by email and we will get back to you.</p>' . do_shortcode( '[gg_enquiry type="structure" structure="' . esc_attr( $sl ) . '"]' ) . '</section>';
	$others = array();
	foreach ( $d['order'] as $k ) {
		if ( $k !== $sl ) {
			$others[] = '<a href="' . esc_url( ggs_structure_url( $k ) ) . '">' . esc_html( $d['items'][ $k ]['title'] ) . '</a>';
		}
	}
	$o .= '<nav class="gg-chips gg-chips--quiet" aria-label="Other structures"><span>Compare with:</span>' . implode( '', $others ) . '<a href="' . esc_url( home_url( '/business-structures/' ) ) . '">All at a glance</a></nav>';
	return $o . '<p class="gg-note">General information on the law as it stands for Tax Year 2026-27, not professional advice. Rates, limits and procedures change; please confirm before acting.</p>';
} );
