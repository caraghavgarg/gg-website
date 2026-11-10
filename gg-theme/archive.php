<?php
/** Category and date archives. */
get_header();
$pid = get_option( 'page_for_posts' );
ggt_page_hero( wp_strip_all_tags( get_the_archive_title() ), wp_strip_all_tags( get_the_archive_description() ), $pid ? array( get_the_title( $pid ) => get_permalink( $pid ) ) : array() );
get_template_part( 'template-parts/post-list' );
get_footer();
