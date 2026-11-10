<?php
get_header();
ggt_page_hero( 'Search: ' . get_search_query() );
get_template_part( 'template-parts/post-list' );
get_footer();
