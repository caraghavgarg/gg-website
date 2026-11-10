<?php
/** A single article or update. */
get_header();
while ( have_posts() ) :
	the_post();
	$posts_page = get_option( 'page_for_posts' );
	ggt_page_hero( get_the_title(), '', $posts_page ? array( get_the_title( $posts_page ) => get_permalink( $posts_page ) ) : array() );
	?>
	<div class="content-area">
		<div class="wrap">
			<p class="post-meta"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?><?php $c = get_the_category(); if ( $c ) { echo ' · ' . esc_html( $c[0]->name ); } ?></p>
			<article class="entry"><?php the_content(); ?></article>
		</div>
	</div>
	<?php
endwhile;
get_template_part( 'template-parts/cta' );
get_footer();
