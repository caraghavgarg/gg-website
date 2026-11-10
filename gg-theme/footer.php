<?php
/**
 * Footer: firm contact, utilities, services; disclaimer bar.
 */
$services = function_exists( 'ggs_services' ) ? ggs_services() : array();
?>
</main>
<footer class="site-foot">
	<div class="wrap foot-grid">
		<div>
			<h2><?php echo esc_html( ggt( 'firm_name' ) ); ?></h2>
			<ul class="foot-list">
				<?php if ( ggt( 'address' ) ) : ?><li><?php echo ggt_icon( 'pin' ); // phpcs:ignore ?><span><?php echo esc_html( preg_replace( '/\s*\n\s*/', ', ', ggt( 'address' ) ) ); ?></span></li><?php endif; ?>
				<?php if ( ggt( 'email' ) ) : ?><li><?php echo ggt_icon( 'mail' ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( ggt( 'email' ) ); ?>"><?php echo esc_html( ggt( 'email' ) ); ?></a></li><?php endif; ?>
				<?php if ( ggt( 'phone' ) ) : ?><li><?php echo ggt_icon( 'phone' ); // phpcs:ignore ?><a href="<?php echo esc_attr( ggt_tel( ggt( 'phone' ) ) ); ?>"><?php echo esc_html( ggt( 'phone' ) ); ?></a></li><?php endif; ?>
				<?php if ( ggt( 'whatsapp' ) ) : ?><li><?php echo ggt_icon( 'chat' ); // phpcs:ignore ?><a href="https://wa.me/<?php echo esc_attr( ggt( 'whatsapp' ) ); ?>" target="_blank" rel="noopener">WhatsApp</a></li><?php endif; ?>
			</ul>
		</div>
		<div>
			<h2>Utilities</h2>
			<ul class="foot-links">
				<li><a href="<?php echo esc_url( home_url( '/calculators/' ) ); ?>">Calculators</a></li>
				<li><a href="<?php echo esc_url( home_url( '/due-dates/' ) ); ?>">Due Date Calendar</a></li>
				<li><a href="<?php echo esc_url( home_url( '/income-tax/' ) ); ?>">Income Tax</a></li>
				<li><a href="<?php echo esc_url( home_url( '/gst/' ) ); ?>">GST</a></li>
				<li><a href="<?php echo esc_url( home_url( '/mca-roc/' ) ); ?>">MCA &amp; ROC</a></li>
				<li><a href="<?php echo esc_url( home_url( '/finance/' ) ); ?>">Finance</a></li>
				<li><a href="<?php echo esc_url( home_url( '/labour-law/' ) ); ?>">Labour Law</a></li>
				<li><a href="<?php echo esc_url( home_url( '/others/' ) ); ?>">Other Laws</a></li>
				<li><a href="<?php echo esc_url( home_url( '/professional-resources/' ) ); ?>">Portal links</a></li>
			</ul>
		</div>
		<div>
			<h2>Services</h2>
			<div class="chips">
				<?php foreach ( $services as $s ) : ?>
					<a href="<?php echo esc_url( get_permalink( $s ) ); ?>"><?php echo esc_html( get_the_title( $s ) ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</footer>
<div class="foot-bottom">
	<div class="wrap">
		<p class="foot-disclaimer">Disclaimer: This website gives general information about the firm and its services, as permitted by the Institute of Chartered Accountants of India. It is not an advertisement or a solicitation of work, and it is not professional advice. <a href="<?php echo esc_url( home_url( '/disclaimer/' ) ); ?>">Read the full disclaimer</a>.</p>
		<span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( ggt( 'firm_name' ) ); ?>, Chartered Accountants</span>
			<span class="foot-legal"><a href="<?php echo esc_url( home_url( '/disclaimer/' ) ); ?>">Disclaimer</a> · <a href="<?php echo esc_url( get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a> · <a href="<?php echo esc_url( home_url( '/terms-of-use/' ) ); ?>">Terms of Use</a> · <a href="<?php echo esc_url( home_url( '/careers/' ) ); ?>">Careers</a></span>
		<?php if ( ggt( 'icai_reg' ) ) : ?><span>ICAI Firm Reg. No. <?php echo esc_html( ggt( 'icai_reg' ) ); ?></span><?php endif; ?>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
