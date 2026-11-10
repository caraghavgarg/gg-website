<?php
get_header();
ggt_page_hero( 'Page not found', 'The page you were looking for has moved or no longer exists.' );
?>
<div class="content-area"><div class="wrap">
	<p><a class="gg-btn gg-btn--gold" href="<?php echo esc_url( home_url( '/' ) ); ?>">Go to the home page</a> &nbsp; <a class="gg-btn gg-btn--line" href="<?php echo esc_url( home_url( '/calculators/' ) ); ?>">Calculators</a></p>
	<?php get_search_form(); ?>
</div></div>
<?php
get_footer();
