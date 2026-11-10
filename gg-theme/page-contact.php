<?php
/**
 * Template Name: Contact
 */
get_header();
while ( have_posts() ) :
	the_post();
	ggt_page_hero( get_the_title(), wp_strip_all_tags( get_the_content() ) );
endwhile;
$map = function_exists( 'ggs_setting' ) ? ggs_setting( 'map_query' ) : '';
?>
<div class="content-area">
	<div class="wrap">
		<?php if ( $map ) : ?>
			<div class="map-frame"><iframe title="Map: head office" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q=<?php echo rawurlencode( $map ); ?>&amp;output=embed"></iframe></div>
		<?php endif; ?>
		<div class="contact-grid" style="margin-top:36px">
			<div>
				<h2 class="sec-title">Request an appointment</h2>
				<?php echo function_exists( 'ggs_setting' ) ? do_shortcode( '[gg_enquiry]' ) : ''; // phpcs:ignore ?>
			</div>
			<div>
				<h2 class="sec-title">General enquiries</h2>
				<ul class="contact-list">
					<li><?php echo ggt_icon( 'pin' ); // phpcs:ignore ?><span><?php echo esc_html( ggt( 'address' ) ); ?></span></li>
					<?php if ( ggt( 'branch' ) ) : ?><li><?php echo ggt_icon( 'bank' ); // phpcs:ignore ?><span><?php echo esc_html( ggt( 'branch' ) ); ?></span></li><?php endif; ?>
					<li><?php echo ggt_icon( 'mail' ); // phpcs:ignore ?><span><a href="mailto:<?php echo esc_attr( ggt( 'email' ) ); ?>"><?php echo esc_html( ggt( 'email' ) ); ?></a><?php if ( ggt( 'email_2' ) ) : ?><br><a href="mailto:<?php echo esc_attr( ggt( 'email_2' ) ); ?>"><?php echo esc_html( ggt( 'email_2' ) ); ?></a><?php endif; ?></span></li>
					<li><?php echo ggt_icon( 'phone' ); // phpcs:ignore ?><span><a href="<?php echo esc_attr( ggt_tel( ggt( 'phone' ) ) ); ?>"><?php echo esc_html( ggt( 'phone' ) ); ?></a></span></li>
					<?php if ( ggt( 'whatsapp' ) ) : ?><li><?php echo ggt_icon( 'chat' ); // phpcs:ignore ?><span><a href="https://wa.me/<?php echo esc_attr( ggt( 'whatsapp' ) ); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a></span></li><?php endif; ?>
					<li><?php echo ggt_icon( 'clock' ); // phpcs:ignore ?><span><?php echo esc_html( ggt( 'hours' ) ); ?></span></li>
				</ul>
				<?php if ( $map ) : ?><p class="gg-note"><a href="https://www.google.com/maps/search/?api=1&amp;query=<?php echo rawurlencode( $map ); ?>" target="_blank" rel="noopener">Open in Google Maps ↗</a></p><?php endif; ?>
			</div>
		</div>
	</div>
</div>
<?php
get_footer();
