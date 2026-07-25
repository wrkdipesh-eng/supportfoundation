<?php

/**
 * Display Page Public Link Class
 *
 * Handles the generation of public link pages for forms using the QR Generator module.
 *
 * @package EverestForms\Pro\Addons\QRGenerator\Builder
 */

namespace EverestForms\Pro\Addons\QRGenerator\Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class DisplayPagePublicLink
 *
 * Extends EVF_Display_Page and manages displaying the public link for forms.
 */
class DisplayPagePublicLink extends \EVF_Display_Page {

	/**
	 * The form ID.
	 *
	 * @var int
	 */
	protected $form_id;

	/**
	 * The form object.
	 *
	 * @var object
	 */
	protected $form;

	/**
	 * Constructor for the DisplayPagePublicLink class.
	 *
	 * @since 1.7.9
	 *
	 * @param int $form_id The ID of the form.
	 */
	public function __construct( $form_id ) {
		$this->form_id = $form_id;
		$this->form    = evf()->form->get( $this->form_id, array( 'contents_only' => true ) );
		$form_array    = (array) $this->form;

		$form_data = isset( $form_array['post_content'] ) ? evf_decode( $form_array['post_content'] ) : array();

		if ( isset( $form_data['settings']['qr_generator'] ) && isset( $form_data['settings']['qr_generator'] ['evf_enable_public_link'] ) && $form_data['settings']['qr_generator'] ['evf_enable_public_link'] ) {
			parent::__construct();
		}
	}

	/**
	 * Get the content for the public form page.
	 *
	 * @since 1.7.9
	 *
	 * @return string HTML content.
	 */
	public function get_content() {
		return "[everest_form id='" . esc_attr( $this->form_id ) . "']";
	}

	/**
	 * Get the title for the public form page.
	 *
	 * Public form pages should not have visible page titles. This returns an empty
	 * span to prevent themes from showing titles like "Untitled" on such pages.
	 *
	 * @since 1.7.9
	 *
	 * @return string The HTML for a hidden title.
	 */
	public function get_title() {
		return '<span style="display:none;"></span>';
	}

	/**
	 * Get the GUID for the public form page.
	 *
	 * @since 1.7.9
	 *
	 * @return string The globally unique identifier for the form page.
	 */
	public function get_guid() {
		return 'everest-forms-public-form';
	}
}
