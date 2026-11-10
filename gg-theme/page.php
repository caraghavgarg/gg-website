<?php
/**
 * Default page. Text-only pages get a side column (appointment, resources, contact)
 * so wide screens are used without stretching reading lines; pages built from the
 * GG Site tools ([gg_...] shortcodes) use the full width.
 */
get_header();
while ( have_posts() ) :
	the_post();
	$parent = wp_get_post_parent_id( get_the_ID() );
	ggt_page_hero( get_the_title(), has_excerpt() ? get_the_excerpt() : '', $parent ? array( get_the_title( $parent ) => get_permalink( $parent ) ) : array() );
	$full = false !== strpos( get_post_field( 'post_content', get_the_ID() ), '[gg_' );
	if ( $full ) :
		?>
		<div class="content-area"><div class="wrap entry"><?php the_content(); ?></div></div>
	<?php else : ?>
		<div class="content-area">
			<div class="wrap with-aside">
				<div class="entry"><?php the_content(); ?></div>
				<aside class="sticky-aside">
					<div class="aside-card aside-card--gold">
						<h2>Discuss your requirement</h2>
						<p>Tell us what you need and we will arrange a meeting or a call.</p>
						<a class="gg-btn gg-btn--gold" href="<?php echo esc_url( home_url( '/contact/#enquiry' ) ); ?>">Request an appointment</a>
					</div>
					<div class="aside-card">
						<h2>Resources</h2>
						<ul>
							<?php foreach ( array( 'income-tax' => 'Income Tax', 'gst' => 'GST', 'mca-roc' => 'MCA / ROC', 'finance' => 'Finance', 'others' => 'Others', 'calculators' => 'Calculators', 'due-dates' => 'Due Date Calendar' ) as $slug => $label ) : ?>
								<li><a href="<?php echo esc_url( home_url( '/' . $slug . '/' ) ); ?>"><?php echo esc_html( $label ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php if ( ggt( 'phone' ) || ggt( 'email' ) ) : ?>
						<div class="aside-card">
							<h2>Contact</h2>
							<ul>
								<?php if ( ggt( 'phone' ) ) : ?><li><a href="<?php echo esc_attr( ggt_tel( ggt( 'phone' ) ) ); ?>"><?php echo esc_html( ggt( 'phone' ) ); ?></a></li><?php endif; ?>
								<?php if ( ggt( 'email' ) ) : ?><li><a href="mailto:<?php echo esc_attr( ggt( 'email' ) ); ?>"><?php echo esc_html( ggt( 'email' ) ); ?></a></li><?php endif; ?>
							</ul>
						</div>
					<?php endif; ?>
				</aside>
			</div>
		</div>
	<?php
	endif;
endwhile;
get_template_part( 'template-parts/cta' );
get_footer();
