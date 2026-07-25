<?php

namespace EverestForms\Pro\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Capability integrations with third-party plugins.
 *
 * @since 1.4.0
 */
class Integrations {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.4.0
	 * @var object
	 */
	protected static $instance;

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.4.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is forbidden.', 'everest-forms-pro' ), '1.4.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.4.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'everest-forms-pro' ), '1.4.0' );
	}

	/**
	 * Main plugin class instance.
	 *
	 * Ensures only one instance of the plugin is loaded or can be loaded.
	 *
	 * @since 1.4.0
	 *
	 * @return object Main instance of the class.
	 */
	final public static function instance() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Plugin Constructor.
	 *
	 * @since 1.4.0
	 */
	public function __construct() {
		// Members plugin integration.
		add_action( 'admin_enqueue_scripts', array( $this, 'members_enqueue_scripts' ) );
		add_action( 'members_register_cap_groups', array( $this, 'members_register_cap_group' ) );
		add_action( 'members_register_caps', array( $this, 'members_register_caps' ) );
		add_filter( 'members_get_capabilities', array( $this, 'members_get_capabilities' ) );
		// User Role Editor plugin integration.
		add_filter( 'ure_capabilities_groups_tree', array( $this, 'ure_capabilities_groups_tree' ) );
		add_filter( 'ure_custom_capability_groups', array( $this, 'ure_custom_capability_groups' ), 10, 2 );
		add_filter( 'ure_full_capabilites', array( $this, 'ure_full_capabilities' ), 10, 2 );
	}

	/**
	 * Get the core capabilities.
	 *
	 * @since 1.4.0
	 *
	 * @return array $capabilities Core capabilities.
	 */
	public function get_core_caps() {
		$capabilities = array(
			// Forms capabilities.
			'everest_forms_create_forms'          => __( 'Create Forms', 'everest-forms-pro' ),
			'everest_forms_view_forms'            => __( 'View Forms', 'everest-forms-pro' ),
			'everest_forms_view_others_forms'     => __( 'View Others\' Forms', 'everest-forms-pro' ),
			'everest_forms_edit_forms'            => __( 'Edit Forms', 'everest-forms-pro' ),
			'everest_forms_edit_others_forms'     => __( 'Edit Others\' Forms', 'everest-forms-pro' ),
			'everest_forms_delete_forms'          => __( 'Delete Forms', 'everest-forms-pro' ),
			'everest_forms_delete_others_forms'   => __( 'Delete Others\' Forms', 'everest-forms-pro' ),
			// Entries capabilities.
			'everest_forms_view_entries'          => __( 'View Entries', 'everest-forms-pro' ),
			'everest_forms_view_others_entries'   => __( 'View Others\' Entries', 'everest-forms-pro' ),
			'everest_forms_edit_entries'          => __( 'Edit Entries', 'everest-forms-pro' ),
			'everest_forms_edit_others_entries'   => __( 'Edit Others\' Entries', 'everest-forms-pro' ),
			'everest_forms_delete_entries'        => __( 'Delete Entries', 'everest-forms-pro' ),
			'everest_forms_delete_others_entries' => __( 'Delete Others\' Entries', 'everest-forms-pro' ),
			// Plugin caps.
			'manage_everest_forms'                => __( 'Manage Everest Forms', 'everest-forms-pro' ),
		);

		return apply_filters( 'everest_forms_get_core_caps', $capabilities );
	}

	/**
	 * Get primitive capabilities of CPT.
	 *
	 * @since 1.4.0
	 *
	 * @return array Default primitive capabilities.
	 */
	public function get_primitive_caps() {
		return array(
			'edit_everest_forms',
			'edit_others_everest_forms',
			'delete_everest_forms',
			'publish_everest_forms',
			'read_private_everest_forms',
		);
	}

	/**
	 * Enqueue scripts on Members plugin screen.
	 *
	 * @since 1.4.0
	 */
	public function members_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Don't load if Members roles page is not editable.
		if ( ! isset( $_GET['action'] ) && 'members_page_roles' === $screen_id ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Check to make sure we're on a Members admin page.
		if ( in_array( $screen_id, array( 'toplevel_page_members', 'members_page_roles' ), true ) ) {
			$custom_css = '
				/* Everest Forms icons for Members */
				.members-tab-nav li .dashicons-everest-forms {
					background-image: url(' . \EVF_Admin_Menus::get_icon_svg( '#0073aa' ) . ');
				}
				.members-tab-nav li[aria-selected="true"] .dashicons-everest-forms {
					background-image: url(' . \EVF_Admin_Menus::get_icon_svg( '#555' ) . ');
				}
			';
			wp_add_inline_style( 'members-admin', $custom_css );
		}
	}

	/**
	 * Register a custom cap group in the Members plugin.
	 *
	 * @since 1.4.0
	 */
	public function members_register_cap_group() {
		$post_object = get_post_type_object( 'everest_form' );

		if ( $post_object ) {
			members_register_cap_group(
				'everest-forms',
				array(
					'label'    => esc_html__( 'Everest Forms', 'everest-forms-pro' ),
					'icon'     => 'dashicons-everest-forms',
					'priority' => 11,
				)
			);

			// Unregister Everest Forms CPT cap group.
			members_unregister_cap_group( 'type-everest_form' );
		}
	}

	/**
	 * Register the plugin capabilities group in Members plugin.
	 *
	 * @since 1.4.0
	 */
	public function members_register_caps() {
		$caps = $this->get_core_caps();

		foreach ( $caps as $cap => $label ) {
			members_register_cap(
				$cap,
				array(
					'label' => $label,
					'group' => 'everest-forms',
				)
			);
		}
	}

	/**
	 * Remove CPT default primitive capabilities from Members plugin "All" tab.
	 *
	 * @since 1.4.0
	 *
	 * @param array $list Current capabilities list.
	 *
	 * @return array
	 */
	public function members_get_capabilities( $list ) {
		return array_diff( $list, $this->get_primitive_caps() );
	}

	/**
	 * Register plugin capabilities group in the User Role Editor plugin.
	 *
	 * @since 1.4.0
	 *
	 * @param array $groups Current capability groups.
	 *
	 * @return array
	 */
	public function ure_capabilities_groups_tree( $groups = array() ) {
		$groups['everest_forms_caps'] = array(
			'caption' => esc_html__( 'Everest Forms', 'everest-forms' ),
			'parent'  => 'custom',
			'level'   => 2,
		);

		$remove_groups = array_intersect_key( $groups, array_flip( array( 'everest_form' ) ) );

		foreach ( $remove_groups as $key => $data ) {
			if ( isset( $groups[ $key ]['parent'] ) && 'custom_post_types' === $groups[ $key ]['parent'] ) {
				unset( $groups[ $key ] );
			}
		}

		return $groups;
	}

	/**
	 * Register plugin capabilities in the User Role Editor plugin.
	 *
	 * @since 1.4.0
	 *
	 * @param array  $groups Current capability groups.
	 * @param string $cap_id Capability ID.
	 *
	 * @return array
	 */
	public function ure_custom_capability_groups( $groups = array(), $cap_id = '' ) {
		$caps = array_keys( $this->get_core_caps() );

		// If capability belongs to our's, register it to a group.
		if ( in_array( $cap_id, $caps, true ) ) {
			$groups[] = 'everest_forms_caps';
		}

		return $groups;
	}

	/**
	 * Add human readable capabilities label in the User Role Editor plugin.
	 *
	 * @since 1.4.0
	 *
	 * @param array $list Current capabilities list.
	 *
	 * @return array Array of modified capabilities list.
	 */
	public function ure_full_capabilities( $list ) {
		$caps = $this->get_core_caps();

		foreach ( $caps as $cap_id => $cap_name ) {
			$list[ $cap_id ] = array(
				'inner'   => $cap_id,
				'human'   => $cap_name,
				'wp_core' => false,
			);
		}

		$list = array_diff_key( $list, array_flip( $this->get_primitive_caps() ) );

		return $list;
	}
}
