<?php
/** A single service. */
get_header();
while ( have_posts() ) :
	the_post();
	$current = get_the_ID();
	ggt_page_hero( get_the_title(), get_the_excerpt(), array( 'Services' => home_url( '/services/' ) ) );
	$hub = ggt_service_hub( get_post_field( 'post_name', $current ) );
	?>
	<div class="content-area">
		<div class="wrap with-aside">
			<div class="entry"><?php the_content(); ?></div>
			<aside class="sticky-aside">
				<div class="aside-card aside-card--gold">
					<h2>Discuss your requirement</h2>
					<p>Tell us what you need and we will arrange a meeting or a call.</p>
					<a class="gg-btn gg-btn--gold" href="#enquiry-box">Send your requirement</a>
				</div>
				<?php if ( $hub ) : ?>
					<div class="aside-card"><h2>Law &amp; forms</h2><ul><li><a href="<?php echo esc_url( home_url( '/' . $hub[0] . '/' ) ); ?>"><?php echo esc_html( $hub[1] ); ?> →</a></li></ul></div>
				<?php endif; ?>
				<div class="aside-card">
					<h2>Other services</h2>
					<ul>
						<?php foreach ( ggs_services() as $s ) : ?>
							<li class="<?php echo $s->ID === $current ? 'is-current' : ''; ?>"><a href="<?php echo esc_url( get_permalink( $s ) ); ?>"><?php echo esc_html( get_the_title( $s ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</aside>
		</div>
	</div>
	<?php
endwhile;
get_footer();
