<?php
/**
 * Entries Analytics admin page.
 *
 * @package EVFP_Dashboard_Widget
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EVFP_Dashboard_Widget.
 */
class EVFP_Dashboard_Widget {

	/**
	 * Allow empty field.
	 *
	 * @var array
	 */
	private $allowed_empty_fields = array();

	/**
	 * Hooks.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize Hooks.
	 */
	public function init_hooks() {
		add_action( 'everest_forms_before_entry_list', array( $this, 'display_dashboard_analytics' ) );
		add_action( 'everest_forms_pro_form_analytics_summary', array( $this, 'display_summary' ) );
	}


	/**
	 * Display the dashboard analytics dashboard.
	 *
	 * @param array $entries Entries.
	 */
	public function display_dashboard_analytics( $entries ) {
		if ( empty( $entries->items ) ) {
			return;
		}

		$selected_form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : absint( $entries->form_id ); //phpcs:ignore WordPress.Security.NonceVerification

		if ( empty( $selected_form_id ) ) {
			return;
		}

		$form_obj  = EVF()->form->get( $selected_form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

		if ( isset( $form_data['settings']['enable_entries_dashboard_analytics'] ) && '1' !== $form_data['settings']['enable_entries_dashboard_analytics'] ) {
			return;
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'everest_form',
				'posts_per_page' => -1,
			)
		);

		$forms = array();
		while ( $query->have_posts() ) {
			$query->the_post();
			$id           = get_the_ID();
			$title        = get_the_title();
			$forms[ $id ] = $title;
		}
		wp_reset_postdata();

		$duration = array(
			'day'   => esc_html__( 'Day', 'everest-forms-pro' ),
			'week'  => esc_html__( 'Week', 'everest-forms-pro' ),
			'month' => esc_html__( 'Month', 'everest-forms-pro' ),
		);

		evf_get_template(
			'/templates/analytics/analytics.php',
			array(
				'forms'             => $forms,
				'selected_form_id'  => $selected_form_id,
				'duration'          => $duration,
				'selected_duration' => 'month',
			),
			'',
			dirname( EFP_PLUGIN_FILE )
		);
	}
}
return new EVFP_Dashboard_Widget();
