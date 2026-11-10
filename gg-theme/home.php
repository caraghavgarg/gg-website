<?php
/** Posts page: Resources & Publications. */
get_header();
$pid = get_option( 'page_for_posts' );
ggt_page_hero( $pid ? get_the_title( $pid ) : 'Resources & Publications', 'Articles, professional updates, notifications and circulars from our team.' );
get_template_part( 'template-parts/post-list' );
get_footer();
