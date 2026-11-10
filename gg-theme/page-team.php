<?php
/**
 * Template Name: Team
 */
get_header();
while ( have_posts() ) :
	the_post();
	ggt_page_hero( get_the_title(), wp_strip_all_tags( get_the_content() ), array( 'About Us' => home_url( '/about/' ) ) );
endwhile;
?>
<div class="content-area"><div class="wrap entry"><?php echo do_shortcode( '[gg_team]' ); ?></div></div>
<?php
get_template_part( 'template-parts/cta' );
get_footer();
