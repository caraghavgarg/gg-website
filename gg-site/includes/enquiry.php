<?php
/**
 * Enquiry / appointment form: [gg_enquiry]
 * Saved as a private "Enquiry" in the dashboard and emailed to the firm.
 * Protection: nonce, honeypot field, minimum fill time, 5 submissions per hour per visitor.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ggs_service_choices() {
	$list = array();
	foreach ( ggs_services() as $s ) {
		$list[] = get_the_title( $s );
	}
	$list[] = 'Other';
	return $list;
}

function ggs_position_choices() {
	return array( 'CA Articleship', 'Semi-qualified CA / CA Intermediate', 'Qualified Chartered Accountant', 'Accounts / Tax Assistant', 'Other' );
}

add_shortcode( 'gg_enquiry', function ( $atts = array() ) {
	$careers   = is_array( $atts ) && isset( $atts['type'] ) && 'careers' === $atts['type'];
	$structure = ( is_array( $atts ) && isset( $atts['type'] ) && 'structure' === $atts['type'] ) ? sanitize_key( $atts['structure'] ?? '' ) : '';
	$status = isset( $_GET['enquiry'] ) ? sanitize_key( wp_unslash( $_GET['enquiry'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$msg    = '';
	if ( 'sent' === $status ) {
		$msg = $careers
			? '<div class="gg-alert gg-alert--ok" role="status">Thank you. Your application has reached our office. Please also email your CV to ' . esc_html( ggs_setting( 'email' ) ) . ' if you have not already.</div>'
			: '<div class="gg-alert gg-alert--ok" role="status">Thank you. Your request has reached our office; we usually reply within one working day.</div>';
	} elseif ( 'error' === $status ) {
		$msg = '<div class="gg-alert gg-alert--err" role="alert">Please fill in your name, a valid email or phone number, ' . ( $structure ? 'your address, your requirement' : 'a message' ) . ', and tick the consent box.</div>';
	} elseif ( 'human' === $status ) {
		$msg = '<div class="gg-alert gg-alert--err" role="alert">The answer to the check question was not right. Please try again.</div>';
	} elseif ( 'limit' === $status ) {
		$msg = '<div class="gg-alert gg-alert--err" role="alert">Too many requests from this connection. Please call or email us instead.</div>';
	}
	$opts = '';
	foreach ( ( $careers ? ggs_position_choices() : ggs_service_choices() ) as $c ) {
		$opts .= '<option>' . esc_html( $c ) . '</option>';
	}
	$action = esc_url( admin_url( 'admin-post.php' ) );
	$token  = wp_create_nonce( 'ggs_enquiry' );
	$ts     = time();
	// Human check: a small sum; the answer is never sent to the page, only a signed hash of it.
	$qa     = wp_rand( 2, 9 );
	$qb     = wp_rand( 1, 9 );
	$hc     = wp_hash( ( $qa + $qb ) . '|' . $ts . '|ggs-human' );
	ob_start();
	?>
	<form class="gg-form" id="enquiry" method="post" action="<?php echo $action; // phpcs:ignore ?>" novalidate>
		<?php echo $msg; // phpcs:ignore ?>
		<input type="hidden" name="action" value="ggs_enquiry">
		<input type="hidden" name="_ggs_nonce" value="<?php echo esc_attr( $token ); ?>">
		<input type="hidden" name="form_type" value="<?php echo $careers ? 'careers' : ( $structure ? 'structure' : 'enquiry' ); ?>">
		<?php if ( $structure ) : ?><input type="hidden" name="structure" value="<?php echo esc_attr( $structure ); ?>"><?php endif; ?>
		<input type="hidden" name="ts" value="<?php echo esc_attr( $ts ); ?>">
		<input type="hidden" name="back" value="<?php echo esc_url( get_permalink() ); ?>">
		<div class="gg-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
		<div class="gg-form-grid">
			<label><span class="lbl">Name <span aria-hidden="true">*</span></span><input type="text" name="name" required maxlength="120" autocomplete="name"></label>
			<label><span class="lbl">Phone / WhatsApp</span><input type="tel" name="phone" maxlength="20" autocomplete="tel" inputmode="tel"></label>
			<label><span class="lbl">Email</span><input type="email" name="email" maxlength="160" autocomplete="email"></label>
			<?php if ( $structure ) : ?>
				<label class="gg-form-wide"><span class="lbl">Address <span aria-hidden="true">*</span></span><textarea name="address" rows="2" required maxlength="500" autocomplete="street-address"></textarea></label>
				<label class="gg-form-wide"><span class="lbl">Your requirement <span aria-hidden="true">*</span> <small>(nature of business, number of partners or directors, city)</small></span><textarea name="message" rows="5" required maxlength="3000"></textarea></label>
			<?php elseif ( $careers ) : ?>
				<label><span class="lbl">Position</span><select name="service"><option value="">Select a position</option><?php echo $opts; // phpcs:ignore ?></select></label>
				<label><span class="lbl">Available from <small>(optional)</small></span><input type="date" name="pref_date"></label>
				<label class="gg-form-wide"><span class="lbl">About you <span aria-hidden="true">*</span> <small>(qualification, CA registration stage, experience, city)</small></span><textarea name="message" rows="5" required maxlength="3000"></textarea></label>
			<?php else : ?>
				<label><span class="lbl">Service</span><select name="service"><option value="">Select a service</option><?php echo $opts; // phpcs:ignore ?></select></label>
				<label><span class="lbl">Preferred appointment date <small>(optional)</small></span><input type="date" name="pref_date" min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></label>
				<label class="gg-form-wide"><span class="lbl">Message <span aria-hidden="true">*</span></span><textarea name="message" rows="5" required maxlength="3000"></textarea></label>
			<?php endif; ?>
		</div>
		<input type="hidden" name="hc" value="<?php echo esc_attr( $hc ); ?>">
		<label class="gg-human"><span class="lbl">Please confirm you are human: what is <?php echo (int) $qa; ?> + <?php echo (int) $qb; ?>? <span aria-hidden="true">*</span></span><input type="text" name="human" inputmode="numeric" pattern="[0-9]*" maxlength="2" required autocomplete="off"></label>
		<label class="gg-check"><input type="checkbox" name="consent" value="1" required> <span>I agree that the firm may use these details to respond to my request. They are not shared with anyone else.</span></label>
		<button type="submit" class="gg-btn gg-btn--gold"><?php echo $careers ? 'Send application' : 'Send request'; ?></button>
		<p class="gg-note"><?php echo $careers ? 'Email your CV (PDF) to ' . esc_html( ggs_setting( 'email' ) ) . ' with the position in the subject line. Please do not send PAN or Aadhaar copies.' : 'Please do not send PAN, Aadhaar, passwords or documents through this form.'; ?></p>
	</form>
	<?php
	return ob_get_clean();
} );

add_action( 'admin_post_nopriv_ggs_enquiry', 'ggs_handle_enquiry' );
add_action( 'admin_post_ggs_enquiry', 'ggs_handle_enquiry' );

function ggs_handle_enquiry() {
	$back = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : home_url( '/contact/' );
	$back = wp_validate_redirect( $back, home_url( '/contact/' ) );
	$go   = function ( $code ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'enquiry', $code, $back ) . '#enquiry' );
		exit;
	};

	if ( ! isset( $_POST['_ggs_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ggs_nonce'] ) ), 'ggs_enquiry' ) ) {
		$go( 'error' );
	}
	// Bots fill the hidden field or submit instantly: pretend success, store nothing.
	$ts = isset( $_POST['ts'] ) ? absint( $_POST['ts'] ) : 0;
	if ( ! empty( $_POST['website'] ) || ( time() - $ts ) < 3 ) {
		$go( 'sent' );
	}

	$human = isset( $_POST['human'] ) ? preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['human'] ) ) ) : '';
	$hc    = isset( $_POST['hc'] ) ? sanitize_text_field( wp_unslash( $_POST['hc'] ) ) : '';
	if ( '' === $human || ! hash_equals( wp_hash( (int) $human . '|' . $ts . '|ggs-human' ), $hc ) ) {
		$go( 'human' );
	}

	$ip_key = 'ggs_enq_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x' );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 5 ) {
		$go( 'limit' );
	}

	$f = array(
		'name'      => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
		'email'     => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
		'phone'     => preg_replace( '/[^0-9+ \-]/', '', sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ) ),
		'service'   => sanitize_text_field( wp_unslash( $_POST['service'] ?? '' ) ),
		'pref_date' => sanitize_text_field( wp_unslash( $_POST['pref_date'] ?? '' ) ),
		'message'   => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
		'address'   => sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) ),
		'consent'   => empty( $_POST['consent'] ) ? '' : 'Yes, ' . wp_date( 'j M Y H:i' ),
	);
	if ( '' === $f['name'] || '' === $f['message'] || ( ! is_email( $f['email'] ) && strlen( preg_replace( '/\D/', '', $f['phone'] ) ) < 10 ) || '' === $f['consent'] ) {
		$go( 'error' );
	}
	if ( $f['pref_date'] && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $f['pref_date'] ) ) {
		$f['pref_date'] = '';
	}
	$is_job    = isset( $_POST['form_type'] ) && 'careers' === $_POST['form_type'];
	$is_struct = isset( $_POST['form_type'] ) && 'structure' === $_POST['form_type'];
	$st_title  = '';
	if ( $is_struct ) {
		$sd  = function_exists( 'ggs_structures' ) ? ggs_structures() : array( 'items' => array() );
		$key = sanitize_key( wp_unslash( $_POST['structure'] ?? '' ) );
		$key = isset( $sd['items'][ $key ] ) ? $key : str_replace( '_', '-', $key );
		$st_title = isset( $sd['items'][ $key ] ) ? $sd['items'][ $key ]['title'] : 'Not stated';
		if ( '' === $f['address'] ) {
			$go( 'error' );
		}
		$f['service'] = 'Business structure: ' . $st_title;
	}
	if ( ! $is_struct && $f['service'] && ! in_array( $f['service'], $is_job ? ggs_position_choices() : ggs_service_choices(), true ) ) {
		$f['service'] = 'Other';
	}
	if ( $is_job ) {
		$f['service'] = 'Careers: ' . ( $f['service'] ? $f['service'] : 'Not stated' );
	}

	$id = wp_insert_post( array(
		'post_type'   => 'gg_enquiry',
		'post_status' => 'private',
		'post_title'  => $f['name'] . ( $f['service'] ? ' – ' . $f['service'] : '' ),
	), true );
	if ( is_wp_error( $id ) ) {
		$go( 'error' );
	}
	foreach ( $f as $k => $v ) {
		update_post_meta( $id, '_gg_' . $k, $v );
	}
	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );

	$to = ggs_setting( 'enquiry_email' ) ? ggs_setting( 'enquiry_email' ) : ggs_setting( 'email' );
	if ( $is_struct ) {
		$list = array_filter( array_map( 'trim', explode( ',', (string) ggs_setting( 'structure_emails' ) ) ), 'is_email' );
		$to   = $list ? array_values( $list ) : $to;
	}
	$body = ( $is_struct ? 'New business structure enquiry (' . $st_title . ") from the website\n\n" : "New enquiry from the website\n\n" )
		. "Name: {$f['name']}\nEmail: {$f['email']}\nPhone: {$f['phone']}\n"
		. ( $is_struct ? "Address: {$f['address']}\nStructure: {$st_title}\n\nRequirement:\n" : "Service: {$f['service']}\nPreferred date: {$f['pref_date']}\n\n" )
		. "{$f['message']}\n\n"
		. 'View in dashboard: ' . admin_url( 'post.php?post=' . $id . '&action=edit' );
	$headers = array();
	if ( is_email( $f['email'] ) ) {
		$headers[] = 'Reply-To: ' . $f['name'] . ' <' . $f['email'] . '>';
	}
	$subject = $is_job ? 'Job application: ' . $f['name'] : ( $is_struct ? 'Business structure enquiry (' . $st_title . '): ' . $f['name'] : 'Website enquiry: ' . $f['name'] );
	wp_mail( $to, $subject, $body, $headers );

	$go( 'sent' );
}
