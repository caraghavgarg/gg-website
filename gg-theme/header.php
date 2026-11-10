<?php
/**
 * Header: logo and contact line, main menu (two-line items), professional utilities bar.
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#28292c">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Skip to content</a>

<?php if ( ggt( 'announcement' ) ) : ?>
	<div class="announce" role="note"><?php echo esc_html( ggt( 'announcement' ) ); ?></div>
<?php endif; ?>

<div class="wrap">
	<header class="head">
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<span class="logo-mark" aria-hidden="true">GG</span>
			<span><span class="logo-name"><?php echo esc_html( ggt( 'firm_name' ) ); ?></span><span class="logo-sub">Chartered Accountants</span></span>
		</a>
		<div class="head-tag"><?php echo esc_html( ggt_mod( 'head_line1' ) ); ?><span><?php echo esc_html( ggt_mod( 'head_line2' ) ); ?></span></div>
		<?php if ( ggt( 'phone' ) ) : ?>
			<div class="head-phone">Phone<span><a href="<?php echo esc_attr( ggt_tel( ggt( 'phone' ) ) ); ?>"><?php echo esc_html( ggt( 'phone' ) ); ?></a></span></div>
		<?php endif; ?>
		<button class="nav-burger" type="button" aria-controls="primary-nav" aria-expanded="false" aria-label="Open menu"><span></span><span></span><span></span></button>
	</header>
	<nav class="primary-nav" id="primary-nav" aria-label="Main navigation">
		<?php ggt_menu( 'primary' ); ?>
	</nav>
</div>

<?php ggt_render_bars(); ?>
<main id="main" tabindex="-1">
