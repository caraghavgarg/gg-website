<?php
/**
 * Content types: services, team members, due dates, enquiries.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'ggs_register_content_types' );

function ggs_register_content_types() {
	register_post_type( 'gg_service', array(
		'labels'       => array(
			'name'          => 'Services',
			'singular_name' => 'Service',
			'add_new_item'  => 'Add service',
			'edit_item'     => 'Edit service',
		),
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'services', 'with_front' => false ),
		'menu_icon'    => 'dashicons-portfolio',
		'menu_position'=> 21,
		'supports'     => array( 'title', 'editor', 'excerpt', 'page-attributes', 'revisions' ),
		'show_in_rest' => true,
	) );

	register_post_type( 'gg_team', array(
		'labels'             => array(
			'name'          => 'Team',
			'singular_name' => 'Team member',
			'add_new_item'  => 'Add team member',
			'edit_item'     => 'Edit team member',
		),
		'public'             => false,
		'show_ui'            => true,
		'menu_icon'          => 'dashicons-groups',
		'menu_position'      => 22,
		'supports'           => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'revisions' ),
		'show_in_rest'       => true,
	) );

	register_post_type( 'gg_duedate', array(
		'labels'        => array(
			'name'          => 'Due dates',
			'singular_name' => 'Due date',
			'add_new_item'  => 'Add due date',
			'edit_item'     => 'Edit due date',
		),
		'public'        => false,
		'show_ui'       => true,
		'menu_icon'     => 'dashicons-calendar-alt',
		'menu_position' => 23,
		'supports'      => array( 'title', 'page-attributes' ),
	) );

	register_post_type( 'gg_enquiry', array(
		'labels'          => array(
			'name'          => 'Enquiries',
			'singular_name' => 'Enquiry',
		),
		'public'          => false,
		'show_ui'         => true,
		'menu_icon'       => 'dashicons-email-alt',
		'menu_position'   => 24,
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'    => true,
	) );
}

/* ------------------------------------------------------------------
 * Meta fields: one small box per type, saved with nonce + capability.
 * ------------------------------------------------------------------ */

function ggs_meta_fields() {
	return array(
		'gg_service' => array(
			'icon' => array( 'Icon (one short symbol, e.g. ₹ or §)', 'text' ),
			'tags' => array( 'Key areas (comma separated, shown as chips)', 'text' ),
		),
		'gg_team'    => array(
			'designation'    => array( 'Designation', 'text' ),
			'qualifications' => array( 'Qualifications (comma separated)', 'text' ),
			'email'          => array( 'Email', 'email' ),
			'phone'          => array( 'Phone', 'text' ),
			'featured'       => array( 'Show on home page (1 = yes)', 'text' ),
		),
		'gg_duedate' => array(
			'due'         => array( 'Due (e.g. 20th, 31 Oct)', 'text' ),
			'sort_day'    => array( 'Day of month for sorting (1–31)', 'number' ),
			'frequency'   => array( 'Frequency (Monthly, Quarterly, Annual)', 'text' ),
			'law'         => array( 'Law (GST, Income Tax, TDS/TCS, MCA, Labour, Other)', 'text' ),
			'note'        => array( 'Note (applies to / period)', 'text' ),
			'source_url'  => array( 'Official source URL', 'url' ),
			'verified_on' => array( 'Verified on (YYYY-MM-DD)', 'date' ),
		),
		'gg_enquiry' => array(
			'name'      => array( 'Name', 'text' ),
			'email'     => array( 'Email', 'email' ),
			'phone'     => array( 'Phone', 'text' ),
			'service'   => array( 'Service', 'text' ),
			'pref_date' => array( 'Preferred appointment date', 'text' ),
			'message'   => array( 'Message', 'textarea' ),
			'consent'   => array( 'Consent given', 'text' ),
		),
	);
}

function ggs_meta( $post_id, $key ) {
	return (string) get_post_meta( $post_id, '_gg_' . $key, true );
}

add_action( 'add_meta_boxes', function () {
	foreach ( ggs_meta_fields() as $type => $fields ) {
		add_meta_box( 'ggs_meta_' . $type, 'Details', 'ggs_render_meta_box', $type, 'normal', 'high' );
	}
} );

function ggs_render_meta_box( $post ) {
	$fields = ggs_meta_fields()[ $post->post_type ] ?? array();
	wp_nonce_field( 'ggs_meta_save', 'ggs_meta_nonce' );
	echo '<table class="form-table" role="presentation">';
	foreach ( $fields as $key => $def ) {
		list( $label, $type ) = $def;
		$id  = 'ggs_' . $key;
		$val = ggs_meta( $post->ID, $key );
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'textarea' === $type ) {
			echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" rows="5" class="large-text">' . esc_textarea( $val ) . '</textarea>';
		} else {
			echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" value="' . esc_attr( $val ) . '" class="regular-text">';
		}
		echo '</td></tr>';
	}
	echo '</table>';
	if ( 'gg_duedate' === $post->post_type ) {
		echo '<p class="description">Check every due date against the official source before publishing, and update "Verified on" each time you check. Extensions notified by CBIC, CBDT or MCA should be entered by editing the "Due" text and the note.</p>';
	}
}

add_action( 'save_post', function ( $post_id, $post ) {
	if ( ! isset( $_POST['ggs_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ggs_meta_nonce'] ) ), 'ggs_meta_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$fields = ggs_meta_fields()[ $post->post_type ] ?? array();
	foreach ( $fields as $key => $def ) {
		$name = 'ggs_' . $key;
		if ( ! isset( $_POST[ $name ] ) ) {
			continue;
		}
		$raw = wp_unslash( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		switch ( $def[1] ) {
			case 'email':
				$val = sanitize_email( $raw );
				break;
			case 'url':
				$val = esc_url_raw( $raw );
				break;
			case 'number':
				$val = (string) absint( $raw );
				break;
			case 'textarea':
				$val = sanitize_textarea_field( $raw );
				break;
			default:
				$val = sanitize_text_field( $raw );
		}
		update_post_meta( $post_id, '_gg_' . $key, $val );
	}
}, 10, 2 );

/* Admin list columns for due dates and enquiries. */
add_filter( 'manage_gg_duedate_posts_columns', function ( $cols ) {
	return array(
		'cb'          => $cols['cb'],
		'title'       => 'Compliance',
		'gg_due'      => 'Due',
		'gg_law'      => 'Law',
		'gg_verified' => 'Verified on',
	);
} );
add_filter( 'manage_gg_enquiry_posts_columns', function ( $cols ) {
	return array(
		'cb'         => $cols['cb'],
		'title'      => 'Enquiry',
		'gg_email'   => 'Email',
		'gg_phone'   => 'Phone',
		'gg_service' => 'Service',
		'date'       => 'Received',
	);
} );
add_action( 'manage_posts_custom_column', function ( $col, $post_id ) {
	$map = array(
		'gg_due'      => 'due',
		'gg_law'      => 'law',
		'gg_verified' => 'verified_on',
		'gg_email'    => 'email',
		'gg_phone'    => 'phone',
		'gg_service'  => 'service',
	);
	if ( isset( $map[ $col ] ) ) {
		$v = ggs_meta( $post_id, $map[ $col ] );
		echo $v ? esc_html( $v ) : '<span style="color:#b32d2e">—</span>';
	}
}, 10, 2 );
