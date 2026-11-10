<?php
/**
 * Template Name: Services
 */
get_header();
while ( have_posts() ) :
	the_post();
	ggt_page_hero( get_the_title(), wp_strip_all_tags( get_the_content() ) );
endwhile;
?>
<div class="content-area"><div class="wrap"><?php echo ggt_services_grid(); // phpcs:ignore ?></div></div>
<?php
get_template_part( 'template-parts/cta' );
get_footer();
