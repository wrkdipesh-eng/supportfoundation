<?php
/**
 * Form Landing Page template.
 *
 * @since 1.7.2
 *
 * @package Form Landing Page template.
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width, height=device-height">
	<?php wp_head(); ?>
	<style>
		html,
		body {
			overflow: hidden;
			-webkit-overflow-scrolling: auto;
			margin: 0;
		}
	</style>
</head>

<body <?php body_class(); ?>>
	<div class="evf-landing-page-form">
		<?php do_action( 'everest_forms_form_landing_page_logo' ); ?>
		<div class="evf-landing-page-form-content">
			<?php do_action( 'everest_forms_form_landing_page_content_before' ); ?>
			<div class="evf-landing-page-form-content-form">
				<?php echo do_shortcode( '[everest_form  id="' . get_the_ID() . '"]' ); ?>
			</div>
		</div>
		<?php do_action( 'everest_forms_form_landing_page_footer' ); ?>
	</div>
<?php wp_footer(); ?>
</body>

</html>
<?php
