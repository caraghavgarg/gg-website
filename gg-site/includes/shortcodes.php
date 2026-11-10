<?php
/**
 * Shortcodes used by the seeded pages. Each can also be placed in any page
 * with the Shortcode block.
 *
 *   [gg_calculator type="gst"]   one calculator (gst, gstinterest, agm, gratuity, pfesi, deposit, emi)
 *   [gg_calculators]             all calculators + official income-tax calculator link
 *   [gg_due_dates law="GST"]     due-date calendar (law optional; limit="6" for a short list)
 *   [gg_hub head="gst"]          Acts & Rules / Forms / Calculators / Others for one head
 *   [gg_hub_index]               cards linking to the five heads
 *   [gg_services]                service cards
 *   [gg_team]                    team profiles
 *   [gg_portal_links]            government and professional portal links
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ggs_calc_titles() {
	return array(
		'gst'         => 'GST Calculator',
		'gstinterest' => 'GST Interest on Late Payment',
		'agm'         => 'ROC Filing Due Dates (from AGM)',
		'gratuity'    => 'Gratuity Calculator',
		'pfesi'       => 'PF & ESI Contribution',
		'deposit'     => 'FD / PPF Maturity',
		'emi'         => 'Loan EMI Calculator',
	);
}

function ggs_calc_html( $type ) {
	if ( ! isset( ggs_calc_titles()[ $type ] ) ) {
		return '';
	}
	wp_enqueue_script( 'gg-calculators' );
	return '<div class="calc" data-calc="' . esc_attr( $type ) . '" id="calc-' . esc_attr( $type ) . '"><noscript>This calculator needs JavaScript.</noscript></div>';
}

add_shortcode( 'gg_calculator', function ( $atts ) {
	$a = shortcode_atts( array( 'type' => 'gst' ), $atts );
	return '<div class="gg-calcs gg-calcs--single">' . ggs_calc_html( sanitize_key( $a['type'] ) ) . '</div>';
} );

add_shortcode( 'gg_calculators', function () {
	$out  = '<nav class="gg-chips" aria-label="Calculators">';
	$out .= '<a href="#income-tax-calc">Income Tax</a>';
	foreach ( ggs_calc_titles() as $k => $t ) {
		$out .= '<a href="#calc-' . esc_attr( $k ) . '">' . esc_html( $t ) . '</a>';
	}
	$out .= '</nav><div class="gg-calcs">';
	$out .= '<div class="calc calc--link" id="income-tax-calc"><h3>Income Tax Calculator</h3>'
		. '<p class="calc-sub">Tax under the Income-tax Act, 2025 (Tax Year 2026-27 onwards) or the Income-tax Act, 1961 (earlier years), old and new regime compared.</p>'
		. '<p>We link to the Income Tax Department\'s own calculator so the slabs, rebate and surcharge are always the officially notified ones.</p>'
		. '<p><a class="gg-btn gg-btn--gold" href="https://www.incometax.gov.in/iec/foportal/income-tax-calculator" target="_blank" rel="noopener">Open the official calculator ↗</a></p></div>';
	foreach ( array_keys( ggs_calc_titles() ) as $k ) {
		$out .= ggs_calc_html( $k );
	}
	return $out . '</div><p class="gg-note">Calculators give indicative figures only and are not professional advice. Rates and limits are editable in each calculator; confirm them against the current notification.</p>';
} );

/* ------------------------------ Due dates ------------------------------ */

function ggs_due_dates( $law = '', $limit = -1 ) {
	$q = new WP_Query( array(
		'post_type'      => 'gg_duedate',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'no_found_rows'  => true,
	) );
	$rows = array();
	foreach ( $q->posts as $p ) {
		$l = ggs_meta( $p->ID, 'law' );
		if ( $law && strcasecmp( $law, $l ) !== 0 ) {
			continue;
		}
		$rows[] = array(
			'title'    => get_the_title( $p ),
			'due'      => ggs_meta( $p->ID, 'due' ),
			'day'      => (int) ggs_meta( $p->ID, 'sort_day' ),
			'freq'     => ggs_meta( $p->ID, 'frequency' ),
			'law'      => $l,
			'note'     => ggs_meta( $p->ID, 'note' ),
			'source'   => ggs_meta( $p->ID, 'source_url' ),
			'verified' => ggs_meta( $p->ID, 'verified_on' ),
			'order'    => (int) $p->menu_order,
		);
	}
	usort( $rows, function ( $a, $b ) {
		$fa = 'Monthly' === $a['freq'] ? 0 : 1;
		$fb = 'Monthly' === $b['freq'] ? 0 : 1;
		return array( $fa, $a['order'], $a['day'] ) <=> array( $fb, $b['order'], $b['day'] );
	} );
	return $limit > 0 ? array_slice( $rows, 0, $limit ) : $rows;
}

add_shortcode( 'gg_due_dates', function ( $atts ) {
	$a    = shortcode_atts( array( 'law' => '', 'limit' => -1, 'compact' => '' ), $atts );
	$rows = ggs_due_dates( sanitize_text_field( $a['law'] ), (int) $a['limit'] );
	if ( ! $rows ) {
		return '<p class="gg-note">Due dates will appear here once they are added under Due dates in the dashboard.</p>';
	}
	if ( $a['compact'] ) {
		$out = '<ul class="gg-due-list">';
		foreach ( $rows as $r ) {
			$out .= '<li><span class="gg-due-day">' . esc_html( $r['due'] ) . '</span><span class="gg-due-what"><strong>' . esc_html( $r['title'] ) . '</strong><small>' . esc_html( trim( $r['law'] . ' · ' . $r['freq'], ' ·' ) ) . '</small></span></li>';
		}
		return $out . '</ul>';
	}
	$out = '<div class="gg-table-wrap"><table class="gg-table gg-due-table"><thead><tr><th scope="col">Due</th><th scope="col">Compliance</th><th scope="col">Law</th><th scope="col">Frequency</th><th scope="col">Source</th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		$src = $r['source'] ? '<a href="' . esc_url( $r['source'] ) . '" target="_blank" rel="noopener">Official site ↗</a>' : '—';
		if ( $r['verified'] ) {
			$src .= '<small>Checked ' . esc_html( mysql2date( 'j M Y', $r['verified'] ) ) . '</small>';
		}
		$out .= '<tr><td class="gg-due-day">' . esc_html( $r['due'] ) . '</td><td><strong>' . esc_html( $r['title'] ) . '</strong>' . ( $r['note'] ? '<small>' . esc_html( $r['note'] ) . '</small>' : '' ) . '</td><td>' . esc_html( $r['law'] ) . '</td><td>' . esc_html( $r['freq'] ) . '</td><td>' . $src . '</td></tr>'; // phpcs:ignore
	}
	return $out . '</tbody></table></div><p class="gg-note">Dates are the usual statutory due dates and may be extended by notification. Always confirm on the official portal before filing.</p>';
} );

/* --------------------------- Acts & Rules hub --------------------------- */

function ggs_hub_card( $item ) {
	list( $title, $note, $url ) = $item;
	$internal = ( 0 === strpos( $url, '/' ) || 0 === strpos( $url, '#' ) );
	$host     = $internal ? 'On this site' : preg_replace( '/^www\./', '', (string) wp_parse_url( $url, PHP_URL_HOST ) ) . ' ↗';
	$href     = $internal ? home_url( $url ) : $url;
	$attrs    = $internal ? '' : ' target="_blank" rel="noopener"';
	return '<a class="gg-card gg-card--link' . ( $internal ? ' is-internal' : '' ) . '" href="' . esc_url( $href ) . '"' . $attrs . '><strong>' . esc_html( $title ) . '</strong><span>' . esc_html( $note ) . '</span><small>' . esc_html( $host ) . '</small></a>';
}

add_shortcode( 'gg_hub', function ( $atts ) {
	$a    = shortcode_atts( array( 'head' => 'gst' ), $atts );
	$data = ggs_hub_data();
	$key  = sanitize_key( $a['head'] );
	if ( empty( $data['pages'][ $key ] ) ) {
		return '';
	}
	$d    = $data['pages'][ $key ];
	$secs = array(
		'acts'        => 'Act & Rules',
		'forms'       => 'Forms',
		'calculators' => 'Calculators',
		'others'      => 'Others',
	);
	$out = '<p class="gg-lead">' . esc_html( $d['intro'] ) . '</p><nav class="gg-chips" aria-label="Sections">';
	$i   = 0;
	foreach ( $secs as $id => $label ) {
		$i++;
		$out .= '<a href="#' . esc_attr( $id ) . '"><span>0' . $i . '</span> ' . esc_html( $label ) . '</a>';
	}
	$out .= '</nav>';
	$i = 0;
	foreach ( $secs as $id => $label ) {
		$i++;
		$out .= '<section class="gg-hub-sec" id="' . esc_attr( $id ) . '"><h2><span class="gg-num">0' . $i . '</span> ' . esc_html( $label ) . '</h2>';
		if ( 'calculators' === $id ) {
			$body = '';
			if ( ! empty( $d['calc_widgets'] ) ) {
				$body .= '<div class="gg-calcs">';
				foreach ( $d['calc_widgets'] as $w ) {
					$body .= ggs_calc_html( $w );
				}
				$body .= '</div>';
			}
			if ( ! empty( $d['calcs'] ) ) {
				$body .= '<div class="gg-cards">' . implode( '', array_map( 'ggs_hub_card', $d['calcs'] ) ) . '</div>';
			}
			$body .= '<p><a class="gg-text-link" href="' . esc_url( home_url( '/calculators/' ) ) . '">All calculators →</a></p>';
			$out  .= $body;
		} else {
			$out .= '<div class="gg-cards">' . implode( '', array_map( 'ggs_hub_card', $d[ $id ] ) ) . '</div>';
		}
		$out .= '</section>';
	}
	$others = array();
	foreach ( $data['order'] as $k ) {
		if ( $k !== $key ) {
			$others[] = '<a href="' . esc_url( home_url( '/' . $k . '/' ) ) . '">' . esc_html( $data['pages'][ $k ]['title'] ) . '</a>';
		}
	}
	$out .= '<nav class="gg-chips gg-chips--quiet" aria-label="Other heads"><span>Also in Acts &amp; Rules:</span>' . implode( '', $others ) . '</nav>';
	return $out . '<p class="gg-note">Links open the official government websites, so you always see the current, amended law.</p>';
} );

/** [gg_tool name="..."]: a self-contained tool from assets/tools, shown full width and sized to its content. */
add_shortcode( 'gg_tool', function ( $atts ) {
	$a     = shortcode_atts( array( 'name' => '' ), $atts );
	$tools = array( 'gst-registration-drafter' => 'GST Registration Drafter' );
	$name  = sanitize_key( $a['name'] );
	if ( ! isset( $tools[ $name ] ) || ! file_exists( GGS_DIR . 'assets/tools/' . $name . '.html' ) ) {
		return '';
	}
	$src = GGS_URL . 'assets/tools/' . $name . '.html?v=' . GGS_VER;
	$id  = 'gg-tool-' . $name;
	return '<div class="gg-tool-frame" style="max-width:none;width:100%"><iframe id="' . esc_attr( $id ) . '" src="' . esc_url( $src ) . '" title="' . esc_attr( $tools[ $name ] ) . '" style="display:block;width:100%;min-height:900px;border:0;background:#28292c"></iframe></div>'
		. '<script>(function(){var f=document.getElementById(' . wp_json_encode( $id ) . ');function fit(){try{var d=f.contentDocument;if(d&&d.documentElement){f.style.height=Math.max(900,d.documentElement.scrollHeight+4)+"px";}}catch(e){}}f.addEventListener("load",function(){fit();setInterval(fit,700);});window.addEventListener("pageshow",function(e){if(e.persisted){f.src=f.src.split("#")[0];}});})();</script>';
} );

add_shortcode( 'gg_hub_index', function () {
	$data = ggs_hub_data();
	$out  = '<div class="gg-cards gg-cards--heads">';
	foreach ( $data['order'] as $k ) {
		$p    = $data['pages'][ $k ];
		$out .= '<a class="gg-card gg-card--head" href="' . esc_url( home_url( '/' . $k . '/' ) ) . '"><strong>' . esc_html( $p['title'] ) . '</strong><span>' . esc_html( wp_trim_words( $p['intro'], 22 ) ) . '</span><small>Act & Rules · Forms · Calculators · Others</small></a>';
	}
	return $out . '</div>';
} );

/* ------------------------------ Services ------------------------------- */

function ggs_services() {
	return get_posts( array(
		'post_type'      => 'gg_service',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
	) );
}

add_shortcode( 'gg_services', function () {
	$out = '<div class="gg-services">';
	foreach ( ggs_services() as $i => $s ) {
		$tags = array_filter( array_map( 'trim', explode( ',', ggs_meta( $s->ID, 'tags' ) ) ) );
		$out .= '<a class="gg-service" href="' . esc_url( get_permalink( $s ) ) . '"><span class="gg-service-no">' . esc_html( sprintf( '%02d', $i + 1 ) ) . '</span><h3>' . esc_html( get_the_title( $s ) ) . '</h3><p>' . esc_html( get_the_excerpt( $s ) ) . '</p>';
		if ( $tags ) {
			$out .= '<ul class="gg-tags">';
			foreach ( array_slice( $tags, 0, 4 ) as $t ) {
				$out .= '<li>' . esc_html( $t ) . '</li>';
			}
			$out .= '</ul>';
		}
		$out .= '<span class="gg-service-more">Read more →</span></a>';
	}
	return $out . '</div>';
} );

/* -------------------------------- Team --------------------------------- */

function ggs_team( $featured_only = false ) {
	$args = array(
		'post_type'      => 'gg_team',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
	);
	if ( $featured_only ) {
		$args['meta_key']   = '_gg_featured'; // phpcs:ignore WordPress.DB.SlowDBQuery
		$args['meta_value'] = '1'; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	return get_posts( $args );
}

function ggs_initials( $name ) {
	$name  = preg_replace( '/^(CA|Dr\.?|Mr\.?|Ms\.?|Mrs\.?|Adv\.?)\s+/i', '', $name );
	$parts = preg_split( '/\s+/', trim( $name ) );
	$first = $parts ? mb_substr( $parts[0], 0, 1 ) : '';
	$last  = count( $parts ) > 1 ? mb_substr( end( $parts ), 0, 1 ) : '';
	return mb_strtoupper( $first . $last );
}

function ggs_team_card( $m, $full = true ) {
	$quals = array_filter( array_map( 'trim', explode( ',', ggs_meta( $m->ID, 'qualifications' ) ) ) );
	$photo = has_post_thumbnail( $m ) ? get_the_post_thumbnail( $m, 'medium', array( 'class' => 'gg-person-photo', 'alt' => get_the_title( $m ) ) ) : '<span class="gg-person-photo gg-person-initials" aria-hidden="true">' . esc_html( ggs_initials( get_the_title( $m ) ) ) . '</span>';
	$out   = '<article class="gg-person">' . $photo . '<div class="gg-person-body"><h3>' . esc_html( get_the_title( $m ) ) . '</h3><p class="gg-person-role">' . esc_html( ggs_meta( $m->ID, 'designation' ) ) . '</p>';
	if ( $quals ) {
		$out .= '<ul class="gg-tags">';
		foreach ( $quals as $q ) {
			$out .= '<li>' . esc_html( $q ) . '</li>';
		}
		$out .= '</ul>';
	}
	if ( $full ) {
		$out .= '<div class="gg-person-bio">' . wp_kses_post( wpautop( $m->post_content ) ) . '</div>';
		$email = ggs_meta( $m->ID, 'email' );
		if ( $email ) {
			$out .= '<p class="gg-person-contact"><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></p>';
		}
	} else {
		$out .= '<p class="gg-person-excerpt">' . esc_html( wp_trim_words( wp_strip_all_tags( $m->post_content ), 26 ) ) . '</p>';
	}
	return $out . '</div></article>';
}

add_shortcode( 'gg_team', function () {
	$team = ggs_team();
	if ( ! $team ) {
		return '<p class="gg-note">Team profiles are added under Team in the dashboard.</p>';
	}
	return '<div class="gg-team">' . implode( '', array_map( 'ggs_team_card', $team ) ) . '</div>';
} );

/* ----------------------------- Portal links ----------------------------- */

function ggs_portal_links() {
	return array(
		array( 'Income Tax e-Filing', 'Returns, e-Pay tax, e-Verify, refund status', 'https://www.incometax.gov.in/iec/foportal/' ),
		array( 'Income Tax Department', 'Acts, Rules, circulars, notifications', 'https://www.incometaxindia.gov.in/' ),
		array( 'TRACES', 'TDS/TCS statements, Form 16/16A', 'https://www.tdscpc.gov.in/' ),
		array( 'GST Portal', 'Registration, returns, payments', 'https://www.gst.gov.in/' ),
		array( 'CBIC – GST', 'GST law, notifications, circulars', 'https://cbic-gst.gov.in/' ),
		array( 'e-Way Bill', 'Generate and manage e-way bills', 'https://ewaybillgst.gov.in/' ),
		array( 'MCA21', 'Company and LLP filings, master data', 'https://www.mca.gov.in/' ),
		array( 'RBI / FEMA', 'Master Directions, FEMA regulations', 'https://www.rbi.org.in/' ),
		array( 'EPFO', 'PF registration, ECR, UAN', 'https://www.epfindia.gov.in/' ),
		array( 'ESIC', 'ESI registration and contributions', 'https://www.esic.gov.in/' ),
		array( 'Shram Suvidha', 'Central labour law registrations and returns', 'https://shramsuvidha.gov.in/' ),
		array( 'Udyam (MSME)', 'MSME registration', 'https://udyamregistration.gov.in/' ),
		array( 'IP India', 'Trademark, patent and design filings', 'https://ipindia.gov.in/' ),
		array( 'Haryana Excise & Taxation', 'State taxes, Haryana', 'https://www.haryanatax.gov.in/' ),
		array( 'ICAI', 'Institute of Chartered Accountants of India', 'https://www.icai.org/' ),
		array( 'IBBI', 'Insolvency and Bankruptcy Board of India', 'https://ibbi.gov.in/' ),
		array( 'SEBI', 'Securities and Exchange Board of India', 'https://www.sebi.gov.in/' ),
		array( 'India Code', 'Official text of Central and State Acts', 'https://www.indiacode.nic.in/' ),
	);
}

add_shortcode( 'gg_portal_links', function () {
	return '<div class="gg-cards">' . implode( '', array_map( 'ggs_hub_card', ggs_portal_links() ) ) . '</div>';
} );
