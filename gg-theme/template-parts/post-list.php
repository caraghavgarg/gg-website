<?php
/** List of posts with category chips. */
$cats = get_categories( array( 'hide_empty' => false, 'exclude' => array( get_option( 'default_category' ) ) ) );
?>
<div class="content-area">
	<div class="wrap">
		<?php if ( $cats ) : ?>
			<nav class="gg-chips" aria-label="Categories" style="margin-top:0;margin-bottom:28px">
				<?php foreach ( $cats as $c ) : ?>
					<a href="<?php echo esc_url( get_category_link( $c ) ); ?>"<?php echo is_category( $c->term_id ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
		<?php if ( have_posts() ) : ?>
			<div class="post-list">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<a class="post-card" href="<?php the_permalink(); ?>">
						<span class="post-meta"><?php echo esc_html( get_the_date( 'j M Y' ) ); ?></span>
						<h2><?php the_title(); ?></h2>
						<p><?php echo esc_html( get_the_excerpt() ); ?></p>
					</a>
				<?php endwhile; ?>
			</div>
			<div class="pagination"><?php echo paginate_links(); // phpcs:ignore ?></div>
		<?php else : ?>
			<div class="empty-state">
				<p>Nothing has been published here yet.</p>
				<p>Meanwhile, see <a href="<?php echo esc_url( home_url( '/acts-rules/' ) ); ?>">Acts, Rules &amp; Notifications</a> or the <a href="<?php echo esc_url( home_url( '/due-dates/' ) ); ?>">Due Date Calendar</a>.</p>
			</div>
		<?php endif; ?>
	</div>
</div>
