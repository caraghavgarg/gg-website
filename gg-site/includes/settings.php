<?php
/**
 * Firm settings (Settings → GG Site): contact details used across the theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ggs_setting_defaults() {
	return array(
		'firm_name'     => 'Garg Garg & Associates',
		'tagline'       => 'Chartered Accountants · Kurukshetra',
		'icai_reg'      => '018813N',
		'phone'         => '+91 720 611 3434',
		'whatsapp'      => '917206113434',
		'email'         => 'mail@ggassociates.in',
		'email_2'       => 'raghav@gargraghav.in',
		'address'       => "Kothi No. 35, First Floor\nSector 7, Urban Estate\nKurukshetra, Haryana – 136118",
		'branch'        => '',
		'hours'         => "Monday – Friday: 9:30 AM – 6:30 PM\nSaturday: 10:00 AM – 2:00 PM\nSunday: Closed",
		'map_query'     => 'Kothi No. 35, Sector 7, Urban Estate, Kurukshetra, Haryana 136118',
		'staff_url'     => '',
		'announcement'  => '',
		'enquiry_email' => '',
		'structure_emails' => 'mail@ggassociates.in, raghav@ggassociates.in',
	);
}

function ggs_setting( $key ) {
	$opts = get_option( 'ggs_settings', array() );
	$def  = ggs_setting_defaults();
	$val  = isset( $opts[ $key ] ) && '' !== $opts[ $key ] ? $opts[ $key ] : ( $def[ $key ] ?? '' );
	return (string) $val;
}

add_action( 'admin_menu', function () {
	add_options_page( 'GG Site', 'GG Site', 'manage_options', 'gg-site', 'ggs_settings_page' );
} );

add_action( 'admin_init', function () {
	register_setting( 'ggs_settings', 'ggs_settings', array(
		'type'              => 'array',
		'sanitize_callback' => 'ggs_sanitize_settings',
	) );
} );

function ggs_sanitize_settings( $in ) {
	$out = array();
	foreach ( ggs_setting_defaults() as $key => $def ) {
		$v = isset( $in[ $key ] ) ? wp_unslash( $in[ $key ] ) : '';
		if ( in_array( $key, array( 'staff_url' ), true ) ) {
			$out[ $key ] = esc_url_raw( $v );
		} elseif ( in_array( $key, array( 'email', 'email_2', 'enquiry_email' ), true ) ) {
			$out[ $key ] = sanitize_email( $v );
		} elseif ( in_array( $key, array( 'address', 'branch', 'hours' ), true ) ) {
			$out[ $key ] = sanitize_textarea_field( $v );
		} elseif ( 'structure_emails' === $key ) {
			$list        = array_filter( array_map( 'sanitize_email', array_map( 'trim', explode( ',', (string) $v ) ) ), 'is_email' );
			$out[ $key ] = $list ? implode( ', ', $list ) : $def;
		} elseif ( 'whatsapp' === $key ) {
			$out[ $key ] = preg_replace( '/\D/', '', $v );
		} else {
			$out[ $key ] = sanitize_text_field( $v );
		}
	}
	return $out;
}

function ggs_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$labels = array(
		'firm_name'     => array( 'Firm name (header and footer)', 'text' ),
		'tagline'       => array( 'Tagline under the name', 'text' ),
		'icai_reg'      => array( 'ICAI firm registration no.', 'text' ),
		'phone'         => array( 'Phone', 'text' ),
		'whatsapp'      => array( 'WhatsApp number (digits with country code)', 'text' ),
		'email'         => array( 'Main email', 'email' ),
		'email_2'       => array( 'Second email (optional)', 'email' ),
		'address'       => array( 'Head office address', 'textarea' ),
		'branch'        => array( 'Branch office', 'textarea' ),
		'hours'         => array( 'Working hours', 'textarea' ),
		'map_query'     => array( 'Address for the Google Map', 'text' ),
		'announcement'  => array( 'Announcement bar text (leave empty to hide)', 'text' ),
		'enquiry_email' => array( 'Send website enquiries to (default: main email)', 'email' ),
		'structure_emails' => array( 'Send business-structure enquiries to (separate addresses with commas)', 'text' ),
		'staff_url'     => array( 'Office Staff Login link (leave empty to hide until the staff area is live)', 'url' ),
	);
	$opts = get_option( 'ggs_settings', array() );
	echo '<div class="wrap"><h1>GG Site settings</h1><form method="post" action="options.php">';
	settings_fields( 'ggs_settings' );
	echo '<table class="form-table" role="presentation">';
	foreach ( $labels as $key => $def ) {
		$val = $opts[ $key ] ?? ggs_setting_defaults()[ $key ];
		$id  = 'ggs_set_' . $key;
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $def[0] ) . '</label></th><td>';
		if ( 'textarea' === $def[1] ) {
			echo '<textarea id="' . esc_attr( $id ) . '" name="ggs_settings[' . esc_attr( $key ) . ']" rows="3" class="large-text">' . esc_textarea( $val ) . '</textarea>';
		} else {
			echo '<input type="' . esc_attr( $def[1] ) . '" id="' . esc_attr( $id ) . '" name="ggs_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" class="regular-text">';
		}
		echo '</td></tr>';
	}
	echo '</table>';
	submit_button();
	echo '</form>';
	if ( function_exists( 'ggs_updates_section' ) ) {
		ggs_updates_section();
	}
	echo '</div>';
}
