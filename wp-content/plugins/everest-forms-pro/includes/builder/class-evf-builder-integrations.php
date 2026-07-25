<?php
/**
 * EverestForms Builder Fields
 *
 * @package EverestForms_Pro\Admin
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Builder_Integrations', false ) ) {
	return new EVF_Builder_Integrations();
}

/**
 * EVF_Builder_Integrations class.
 */
class EVF_Builder_Integrations extends EVF_Builder_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id      = 'integrations';
		$this->label   = __( 'Integrations', 'everest-forms-pro' );
		$this->sidebar = true;

		parent::__construct();
	}

	/**
	 * Outputs the builder sidebar.
	 */
	public function output_sidebar() {
		$active_integrations = apply_filters( 'everest_forms_available_integrations', array() );
		$catalog             = $this->get_integrations_catalog();

		$active_by_id = array();
		foreach ( $active_integrations as $integration ) {
			$active_by_id[ $integration['id'] ] = $integration;
		}

		// Active integrations first.
		foreach ( $catalog as $addon ) {
			if ( ! isset( $active_by_id[ $addon['id'] ] ) ) {
				continue;
			}
			$integration = $active_by_id[ $addon['id'] ];
			$this->add_sidebar_tab( $integration['name'], $integration['id'], $integration['icon'], $this->id );
			do_action( 'everest_forms_integration_connections_' . $integration['id'], $integration );
		}

		// Active integrations not covered by the catalog.
		$catalog_ids = array_column( $catalog, 'id' );
		foreach ( $active_integrations as $integration ) {
			if ( ! in_array( $integration['id'], $catalog_ids, true ) ) {
				$this->add_sidebar_tab( $integration['name'], $integration['id'], $integration['icon'], $this->id );
				do_action( 'everest_forms_integration_connections_' . $integration['id'], $integration );
			}
		}

		// Non-active catalog items below.
		foreach ( $catalog as $addon ) {
			if ( isset( $active_by_id[ $addon['id'] ] ) ) {
				continue;
			}
			echo '<a href="#" '
				. 'class="integration-name evf-panel-tab evf-integrations-panel everest-forms-panel-sidebar-section everest-forms-panel-sidebar-section-' . esc_attr( $addon['id'] ) . ' evf-addon-install-trigger" '
				. 'data-section="' . esc_attr( $addon['id'] ) . '" '
				. 'data-name="' . esc_attr( $addon['name'] ) . '" '
				. 'data-slug="' . esc_attr( $addon['slug'] ) . '" '
				. 'data-status="' . esc_attr( $addon['status'] ) . '" '
				. 'data-description="' . esc_attr( $addon['excerpt'] ) . '" '
				. 'data-addons-url="' . esc_url( $addon['addons_url'] ) . '">';
			echo '<div style="display: flex; align-items: center; gap: 12px;">';
			if ( ! empty( $addon['icon'] ) ) {
				echo '<figure class="logo"><img src="' . esc_url( $addon['icon'] ) . '"></figure>';
			}
			echo '<span>' . esc_html( $addon['name'] ) . '</span>';
			echo '</div>';
			echo '</a>';
		}
	}

	/**
	 * Outputs the builder content.
	 */
	public function output_content() {
		$providers_active = apply_filters( 'everest_forms_available_integrations', array() );

		if ( ! empty( $providers_active ) ) {
			do_action( 'everest_forms_providers_panel_content', $this->form );
			wp_localize_script(
				'everest-forms-integrations-scripts',
				'evf_integration_data',
				isset( $this->form_data['integrations'] ) ? $this->form_data['integrations'] : array()
			);
			return;
		}

		$catalog = $this->get_integrations_catalog();
		?>
		<div class="evf-panel-content-section active evf-integrations-empty-state">
			<div class="evf-integrations-empty-state-icon">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="32" height="32" fill="none">
					<line x1="20" y1="20" x2="44" y2="20" stroke="#bdbdbd" stroke-width="2" stroke-linecap="round"/>
					<line x1="20" y1="44" x2="44" y2="44" stroke="#bdbdbd" stroke-width="2" stroke-linecap="round"/>
					<line x1="20" y1="20" x2="20" y2="44" stroke="#bdbdbd" stroke-width="2" stroke-linecap="round"/>
					<line x1="44" y1="20" x2="44" y2="44" stroke="#bdbdbd" stroke-width="2" stroke-linecap="round"/>
					<circle cx="20" cy="20" r="6" fill="#ffffff" stroke="#7545bb" stroke-width="2"/>
					<rect x="38" y="14" width="12" height="12" rx="2" fill="#ffffff" stroke="#9a9a9a" stroke-width="2"/>
					<rect x="14" y="38" width="12" height="12" rx="2" fill="#ffffff" stroke="#9a9a9a" stroke-width="2"/>
					<circle cx="44" cy="44" r="6" fill="#7545bb"/>
					<circle cx="32" cy="32" r="5" fill="#ffffff" stroke="#7545bb" stroke-width="2"/>
					<circle cx="32" cy="32" r="1.8" fill="#7545bb"/>
				</svg>
			</div>
			<h3><?php esc_html_e( 'No Integrations Connected', 'everest-forms-pro' ); ?></h3>
			<p><?php esc_html_e( 'Connect your favorite email marketing tools and CRMs to this form. Select an integration from the sidebar to install and activate it.', 'everest-forms-pro' ); ?></p>
			<?php if ( ! empty( $catalog ) ) : ?>
			<div class="evf-integrations-empty-state-pills">
				<?php foreach ( array_slice( $catalog, 0, 3 ) as $addon ) : ?>
				<span class="evf-integrations-empty-state-pill">
					<?php if ( ! empty( $addon['icon'] ) ) : ?>
					<img src="<?php echo esc_url( $addon['icon'] ); ?>" alt="<?php echo esc_attr( $addon['name'] ); ?>">
					<?php endif; ?>
					<?php echo esc_html( $addon['name'] ); ?>
				</span>
				<?php endforeach; ?>
				<?php if ( count( $catalog ) > 3 ) : ?>
				<span class="evf-integrations-empty-state-pill evf-integrations-empty-state-pill-more">
					+<?php echo esc_html( count( $catalog ) - 3 ); ?> <?php esc_html_e( 'more', 'everest-forms-pro' ); ?>
				</span>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Returns integration addons from the extensions JSON, filtered to integration categories.
	 *
	 * @return array
	 */
	private function get_integrations_catalog() {
		$catalog    = array();
		$addons_url = admin_url( 'admin.php?page=evf-addons' );
		$file       = defined( 'EVF_PLUGIN_FILE' )
			? dirname( EVF_PLUGIN_FILE ) . '/assets/extensions-json/sections/all_extensions.json'
			: '';

		if ( ! $file || ! file_exists( $file ) ) {
			return $catalog;
		}

		$content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data    = json_decode( $content, true );

		if ( ! isset( $data['products'] ) || ! is_array( $data['products'] ) ) {
			return $catalog;
		}

		$data['features'] = isset( $data['features'] ) && is_array( $data['features'] ) ? $data['features'] : array();
		$items            = array_merge( $data['products'], $data['features'] );

		// Individual slugs from other categories to include alongside Email Marketing / CRM.
		$allowed_ids = array( 'zapier' );

		foreach ( $items as $item ) {
			$category = isset( $item['category'] ) ? $item['category'] : '';
			$slug     = isset( $item['slug'] ) ? $item['slug'] : '';
			$id       = str_replace( 'everest-forms-', '', $slug );

			$in_category = in_array( $category, array( 'Email Marketing', 'CRM Integrations' ), true );
			if ( ! $in_category && ! in_array( $id, $allowed_ids, true ) ) {
				continue;
			}

			// Skip non-service addons in the Email Marketing category.
			if ( 'email-templates' === $id ) {
				continue;
			}

			$name  = isset( $item['name'] ) ? $item['name'] : '';
			$name  = trim( str_replace( array( 'Everest Forms - ', 'Everest Forms- ', 'Everest Forms-', 'Everest Forms ' ), '', $name ) );
			$image = isset( $item['image'] ) ? $item['image'] : '';
			$icon  = ( ! empty( $image ) && defined( 'EVF_PLUGIN_FILE' ) )
				? plugins_url( 'assets/' . ltrim( $image, '/' ), EVF_PLUGIN_FILE )
				: '';

			$plugin_file = WP_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php';
			$status      = file_exists( $plugin_file ) ? 'inactive' : 'not-installed';

			$catalog[ $id ] = array(
				'id'         => $id,
				'slug'       => $slug,
				'name'       => $name,
				'icon'       => $icon,
				'excerpt'    => isset( $item['excerpt'] ) ? $item['excerpt'] : '',
				'addons_url' => $addons_url,
				'status'     => $status,
			);
		}

		// Addons that are marketing/CRM integrations but absent from the JSON catalog.
		$extra = array(
			'getgist' => 'GetGist',
		);
		foreach ( $extra as $id => $name ) {
			if ( isset( $catalog[ $id ] ) ) {
				continue;
			}
			$slug        = 'everest-forms-' . $id;
			$plugin_file = WP_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php';
			$catalog[ $id ] = array(
				'id'         => $id,
				'slug'       => $slug,
				'name'       => $name,
				'icon'       => '',
				'excerpt'    => '',
				'addons_url' => $addons_url,
				'status'     => file_exists( $plugin_file ) ? 'inactive' : 'not-installed',
			);
		}

		return array_values( $catalog );
	}

	/**
	 * Outputs the integration addon catalog grid.
	 */
	private function output_install_catalog() {
		$catalog = $this->get_integrations_catalog();
		?>
		<div class="evf-panel-content-section evf-panel-content-section-info evf-builder-integration-catalog">
			<h3><?php esc_html_e( 'Install Your Addons', 'everest-forms-pro' ); ?></h3>
			<p><?php esc_html_e( 'Click an integration below to install and activate its addon directly from the form builder.', 'everest-forms-pro' ); ?></p>
			<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; margin-top: 20px;">
				<?php foreach ( $catalog as $addon ) : ?>
					<div class="evf-addon-catalog-item"
						role="button"
						tabindex="0"
						style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 16px 12px; border: 1px solid #e0e0e0; border-radius: 6px; cursor: pointer; background: #fff; text-align: center; min-height: 90px;"
						data-name="<?php echo esc_attr( $addon['name'] ); ?>"
						data-slug="<?php echo esc_attr( $addon['slug'] ); ?>"
						data-status="<?php echo esc_attr( $addon['status'] ); ?>"
						data-description="<?php echo esc_attr( $addon['excerpt'] ); ?>"
						data-addons-url="<?php echo esc_url( $addon['addons_url'] ); ?>">
						<?php if ( ! empty( $addon['icon'] ) ) : ?>
							<figure style="margin: 0 0 8px; padding: 0; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
								<img src="<?php echo esc_url( $addon['icon'] ); ?>"
									alt="<?php echo esc_attr( $addon['name'] ); ?>"
									style="max-width: 40px; max-height: 40px; object-fit: contain;">
							</figure>
						<?php endif; ?>
						<span style="font-size: 12px; font-weight: 500; color: #383838; line-height: 1.3;"><?php echo esc_html( $addon['name'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}

return new EVF_Builder_Integrations();
