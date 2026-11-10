<?php
/**
 * Home page. Wording is edited in Appearance → Customize → Home page (GG Theme);
 * the welcome text is the content of the "Home" page; services and due dates come
 * from their own screens in the dashboard.
 */
get_header();
$has_plugin = function_exists( 'ggs_due_dates' );
$pics       = array( 'about', 'services', 'team', 'resources', 'calculators', 'contact' );
?>
<div class="wrap">

	<section class="tiles-wrap" aria-label="Explore the site">
		<button class="arrow l" type="button" aria-label="Previous">‹</button>
		<button class="arrow r" type="button" aria-label="Next">›</button>
		<div class="tiles">
			<?php foreach ( ggt_lines( 'tiles' ) as $i => $t ) :
				$pic  = in_array( $t[3] ?? '', $pics, true ) ? $t[3] : $pics[ $i % count( $pics ) ];
				$link = $t[2] ?? '/';
				$href = 0 === strpos( $link, 'http' ) ? $link : home_url( $link );
				?>
				<a class="tile" href="<?php echo esc_url( $href ); ?>">
					<div class="tile-pic"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/' . $pic . '.svg' ); ?>" alt="" width="400" height="300" loading="<?php echo $i < 3 ? 'eager' : 'lazy'; ?>"></div>
					<div class="tile-text"><div class="tile-badge"><?php echo ggt_icon( $t[4] ?? 'doc' ); // phpcs:ignore ?></div><h3><?php echo esc_html( $t[0] ); ?></h3><p><?php echo esc_html( $t[1] ?? '' ); ?></p></div>
				</a>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="sec two">
		<div>
			<h2 class="sec-title">Welcome</h2>
			<h1 class="big"><?php echo esc_html( ggt_mod( 'welcome' ) ); ?></h1>
			<div class="lead">
				<?php
				while ( have_posts() ) :
					the_post();
					the_content();
				endwhile;
				?>
			</div>
		</div>
		<?php if ( $has_plugin ) : ?>
			<div>
				<h2 class="sec-title">This month's due dates</h2>
				<?php
				$monthly = array_values( array_filter( ggs_due_dates(), function ( $r ) { return 'Monthly' === $r['freq']; } ) );
				$monthly = array_slice( $monthly, 0, 6 );
				if ( $monthly ) :
					?>
					<ul class="rows">
						<?php foreach ( $monthly as $r ) : ?>
							<li><span><?php echo esc_html( $r['title'] ); ?></span><b><?php echo esc_html( $r['due'] ); ?></b></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<p class="rows-note">Subject to extensions by notification · <a href="<?php echo esc_url( home_url( '/due-dates/' ) ); ?>">Full calendar →</a></p>
			</div>
		<?php endif; ?>
	</section>

	<?php
	if ( function_exists( 'ggs_structures_strip' ) ) {
		echo ggs_structures_strip(); // phpcs:ignore
	}
	?>

	<?php if ( $has_plugin ) : ?>
	<section class="sec">
		<h2 class="sec-title">Our Services</h2>
		<?php echo ggt_services_grid(); // phpcs:ignore ?>
	</section>
	<?php endif; ?>

	<section class="sec">
		<h2 class="sec-title">Experience &amp; Credentials</h2>
		<div class="boxes">
			<?php foreach ( ggt_lines( 'creds' ) as $c ) : ?>
				<div class="box"><span class="circle"><?php echo ggt_icon( $c[2] ?? 'award' ); // phpcs:ignore ?></span><h3><?php echo esc_html( $c[0] ); ?></h3><p><?php echo esc_html( $c[1] ?? '' ); ?></p></div>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="sec">
		<h2 class="sec-title">Clients We Serve</h2>
		<div class="chips">
			<?php foreach ( ggt_lines( 'clients' ) as $c ) : ?><span><?php echo esc_html( $c[0] ); ?></span><?php endforeach; ?>
		</div>
	</section>

</div>
<?php
ggt_cta();
get_footer();
