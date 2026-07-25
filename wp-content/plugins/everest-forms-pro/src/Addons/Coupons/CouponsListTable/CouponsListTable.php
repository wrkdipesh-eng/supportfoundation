<?php
/**
 * EverestForms Pro Coupons Table List
 *
 * @package EverestForms\Pro\Addons\Coupons\CouponsListTable
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\Coupons\CouponsListTable;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'EVF_Base_List_Table' ) ) {
	require_once EVF_ABSPATH . 'includes/admin/class-evf-base-list-table.php';
}

/**
 * Coupons table list class.
 */
class CouponsListTable extends \EVF_Base_List_Table {

	/**
	 * Initialize the coupon table list.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => esc_html__( 'coupon', 'everest-forms-pro' ),
				'plural'   => esc_html__( 'coupons', 'everest-forms-pro' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * No items found text.
	 */
	public function no_items() {
		$image_url = esc_url( plugin_dir_url( EFP_PLUGIN_FILE ) . 'src/Addons/Coupons/assets/img/empty-coupon.png' );
		?>
		<div class="evf-no-coupons">
			<img src="<?php echo $image_url; ?>" alt="<?php esc_attr_e( 'No coupons found', 'everest-forms-pro' ); ?>" />
			<p><?php esc_html_e( 'No coupons found.', 'everest-forms-pro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'       => '<input type="checkbox" />',
			'title'    => esc_html__( 'Coupon Title', 'everest-forms-pro' ),
			'code'     => esc_html__( 'Coupon Code', 'everest-forms-pro' ),
			'amount'   => esc_html__( 'Amount', 'everest-forms-pro' ),
			'end_date' => esc_html__( 'Expires', 'everest-forms-pro' ),
			'status'   => esc_html__( 'Status', 'everest-forms-pro' ),
		);
	}

	/**
	 * Define the sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'title'    => array( 'title', false ),
			'code'     => array( 'code', false ),
			'amount'   => array( 'amount', false ),
			'end_date' => array( 'end_date', false ),
		);
	}

	/**
	 * Column cb (checkbox).
	 *
	 * @param array $item Coupon array.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="coupon_ids[]" value="%s" />',
			esc_attr( $item['id'] )
		);
	}

	/**
	 * Column Title with inline row actions.
	 *
	 * @param array $item Coupon array.
	 * @return string
	 */
	public function column_title( $item ) {
		$posts = get_post( $item['id'] );

		if ( ! $posts ) {
			return esc_html( $item['title'] );
		}

		$post_status      = $posts->post_status;
		$post_type_object = get_post_type_object( 'evf_coupons' );
		$edit_link        = admin_url( 'admin.php?page=evf-coupons&action=edit&coupon=' . $posts->ID );

		$output = '<strong>';
		if ( 'trash' === $post_status ) {
			$output .= esc_html( $item['title'] );
		} elseif ( current_user_can( 'manage_everest_forms' ) ) {
			$output .= '<a href="' . esc_url( $edit_link ) . '" class="row-title">' . esc_html( $item['title'] ) . '</a>';
		} else {
			$output .= esc_html( $item['title'] );
		}
		$output .= '</strong>';

		$actions = array();

		if ( current_user_can( 'manage_everest_forms' ) && 'trash' !== $post_status ) {
			$actions['edit'] = '<a href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit', 'everest-forms-pro' ) . '</a>';
		}

		if ( current_user_can( 'manage_everest_forms' ) ) {
			if ( 'trash' === $post_status ) {
				$actions['untrash'] = '<a href="' . wp_nonce_url(
					admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $posts->ID ) ),
					'untrash-post_' . $posts->ID
				) . '">' . esc_html__( 'Restore', 'everest-forms-pro' ) . '</a>';
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = '<a class="submitdelete" href="' . get_delete_post_link( $posts->ID ) . '">' . esc_html__( 'Trash', 'everest-forms-pro' ) . '</a>';
			}

			if ( 'trash' === $post_status || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = '<a class="submitdelete" href="' . get_delete_post_link( $posts->ID, '', true ) . '">' . esc_html__( 'Delete permanently', 'everest-forms-pro' ) . '</a>';
			}
		}

		$row_actions = array();
		foreach ( $actions as $action => $link ) {
			$row_actions[] = '<span class="' . esc_attr( $action ) . '">' . $link . '</span>';
		}

		$output .= '<div class="row-actions">' . implode( ' | ', $row_actions ) . '</div>';

		return $output;
	}

	/**
	 * Return code column.
	 *
	 * @param array $item Coupon array.
	 * @return string
	 */
	public function column_code( $item ) {
		return $item['code'];
	}

	/**
	 * Return amount column.
	 *
	 * @param array $item Coupon array.
	 * @return string
	 */
	public function column_amount( $item ) {
		return $item['amount'];
	}

	/**
	 * Return end_date column.
	 *
	 * @param array $item Coupon array.
	 * @return string
	 */
	public function column_end_date( $item ) {
		return $item['end_date'];
	}

	/**
	 * Return status column.
	 *
	 * @param array $item Coupon array.
	 * @return string
	 */
	public function column_status( $item ) {
		if ( $this->changeCouponStatus( $item ) ) {
			$item['status'] = '0';
		}

		$item['status'] = '1' === $item['status'] ? 'Active' : 'Inactive';

		return '<span class="evf-coupon-status evf-coupon-status--' . strtolower( $item['status'] ) . '">' . $item['status'] . '</span>';
	}

	/**
	 * Default column rendering.
	 *
	 * @param array  $item        Coupon array.
	 * @param string $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
	}

	/**
	 * Table list views.
	 *
	 * @return array
	 */
	protected function get_views() {
		return array();
	}

	/**
	 * Override search_box to wrap in its own GET form so search submits correctly.
	 *
	 * @param string $text     The submit button label.
	 * @param string $input_id ID attribute value for the search input field.
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$input_id = $input_id . '-search-input';
		$s        = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		?>
			<input type="hidden" name="page" value="evf-coupons" />
			<?php if ( ! empty( $_REQUEST['coupon_status'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<input type="hidden" name="coupon_status" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['coupon_status'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification ?>" />
			<?php endif; ?>
			<?php if ( ! empty( $_REQUEST['orderby'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<input type="hidden" name="orderby" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification ?>" />
			<?php endif; ?>
			<?php if ( ! empty( $_REQUEST['order'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<input type="hidden" name="order" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification ?>" />
			<?php endif; ?>
			<div class="search-box evf-search">
				<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">
					<?php echo esc_html( $text ); ?>:
				</label>
				<input type="search"
					id="<?php echo esc_attr( $input_id ); ?>"
					name="s"
					value="<?php echo esc_attr( $s ); ?>"
					placeholder="<?php echo esc_attr( $text ); ?>" />
				<button type="submit" id="search-submit" class="evf-search-submit button">
					<span class="screen-reader-text"><?php echo esc_html( $text ); ?></span>
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24">
						<path fill="currentColor" fill-rule="evenodd"
							d="M4 11a7 7 0 1 1 12.042 4.856 1.012 1.012 0 0 0-.186.186A7 7 0 0 1 4 11Zm12.618 7.032a9 9 0 1 1 1.414-1.414l3.675 3.675a1 1 0 0 1-1.414 1.414l-3.675-3.675Z"
							clip-rule="evenodd"></path>
					</svg>
				</button>
			</div>
		<?php
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		if ( isset( $_GET['coupon_status'] ) && 'trash' === $_GET['coupon_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			return array(
				'untrash' => __( 'Restore', 'everest-forms-pro' ),
				'delete'  => __( 'Delete permanently', 'everest-forms-pro' ),
			);
		}

		return array(
			'trash' => __( 'Move to trash', 'everest-forms-pro' ),
		);
	}

	/**
	 * Display the table navigation.
	 *
	 * @param string $which Top or bottom.
	 */
	public function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php if ( 'top' === $which ) : ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
				<?php $this->extra_tablenav( $which ); ?>
			<?php else : ?>
				<?php $this->extra_tablenav( $which ); ?>
			<?php endif; ?>
			<br class="clear" />
		</div>
		<?php
	}

	/**
	 * Process bulk actions.
	 */
	public function process_bulk_action() {
		$action     = ! empty( $this->current_action() )
		? $this->current_action()
		: ( isset( $_REQUEST['coupon_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['coupon_status'] ) ) : '' );
		$coupon_ids = isset( $_REQUEST['coupon_ids'] ) ? wp_parse_id_list( wp_unslash( $_REQUEST['coupon_ids'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
		$count      = 0;
		switch ( $action ) {
			case 'trash':
				foreach ( $coupon_ids as $coupon_id ) {
					if ( wp_trash_post( $coupon_id ) ) {
						++$count;
					}
				}

				break;

			case 'untrash':
				foreach ( $coupon_ids as $coupon_id ) {
					if ( wp_untrash_post( $coupon_id ) ) {
						++$count;
					}
				}
				wp_safe_redirect( admin_url( 'admin.php?page=evf-coupons' ) );

				break;

			case 'delete':
				foreach ( $coupon_ids as $coupon_id ) {
					if ( wp_delete_post( $coupon_id, true ) ) {
						++$count;
					}
				}

				break;

			case 'delete_all':
				$this->empty_coupon_trash();
				break;
		}
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which The location of the extra table nav markup.
	 */
	protected function extra_tablenav( $which ) {
		$num_posts = wp_count_posts( 'everest_form', 'readable' );

		echo '<div class="everest-forms-extra-table-nav">';

		if ( 'top' === $which ) {
			$this->forms_status_dropdown();

			if ( $num_posts->trash && isset( $_GET['status'] ) && 'trash' === $_GET['status'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				submit_button( __( 'Empty Trash', 'everest-forms' ), 'apply', 'delete_all', false );
			}
		}

		echo '</div>';
	}

	/**
	 * Display a status dropdown for filtering coupons.
	 */
	public function forms_status_dropdown() {
		$current_status = isset( $_REQUEST['coupon_status'] ) // phpcs:ignore WordPress.Security.NonceVerification
			? sanitize_text_field( wp_unslash( $_REQUEST['coupon_status'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
			: '';

		$published_coupons = new \WP_Query(
			array(
				'post_type'      => 'evf_coupons',
				'post_status'    => array( 'publish' ),
				'posts_per_page' => -1,
			)
		);

		$total_count    = 0;
		$active_count   = 0;
		$inactive_count = 0;

		foreach ( $published_coupons->posts as $post ) {
			$coupon = ! empty( $post->post_content ) ? evf_decode( $post->post_content ) : '';

			if ( empty( $coupon ) ) {
				continue;
			}

			++$total_count;

			if ( isset( $coupon['status'] ) && '1' === $coupon['status'] ) {
				++$active_count;
			} else {
				++$inactive_count;
			}
		}

		$post_counts = wp_count_posts( 'evf_coupons' );
		$trash_count = isset( $post_counts->trash ) ? (int) $post_counts->trash : 0;

		$statuses = array(
			''         => sprintf( __( 'All (%d)', 'everest-forms-pro' ), $total_count ),
			'active'   => sprintf( __( 'Active (%d)', 'everest-forms-pro' ), $active_count ),
			'inactive' => sprintf( __( 'Inactive (%d)', 'everest-forms-pro' ), $inactive_count ),
		);

		if ( $trash_count > 0 ) {
			$statuses['trash'] = sprintf( __( 'Trash (%d)', 'everest-forms-pro' ), $trash_count );
		}
		?>
		<label for="filter-by-coupon-status" class="screen-reader-text">
			<?php esc_html_e( 'Filter by coupon status', 'everest-forms-pro' ); ?>
		</label>
		<select name="coupon_status" class="evf-enhanced-select" id="filter-by-coupon-status" onchange="window.location.href='<?php echo esc_url( admin_url( 'admin.php?page=evf-coupons' ) ); ?>&coupon_status='+this.value">
			<?php foreach ( $statuses as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_status, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Prepare the items for the table to process.
	 */
	public function prepare_items() {
		$per_page     = $this->get_items_per_page( 'evf_coupons_per_page' );
		$current_page = $this->get_pagenum();

		$coupon_status = isset( $_REQUEST['coupon_status'] ) // phpcs:ignore WordPress.Security.NonceVerification
			? sanitize_text_field( wp_unslash( $_REQUEST['coupon_status'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
			: 'all';

		if ( 'trash' === $coupon_status ) {
			$post_status = array( 'trash' );
		} else {
			$post_status = array( 'publish' );
		}

		$args = array(
			'post_type'           => 'evf_coupons',
			'posts_per_page'      => -1,
			'ignore_sticky_posts' => true,
			'post_status'         => $post_status,
		);

		if ( ! empty( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$args['s'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		$query = new \WP_Query( $args );

		$filtered_coupons = array();

		foreach ( $query->posts as $post ) {
			$coupon = ! empty( $post->post_content ) ? evf_decode( $post->post_content ) : '';

			if ( empty( $coupon ) ) {
				continue;
			}

			$coupon['id'] = $post->ID;

			if ( 'active' === $coupon_status && '1' !== $coupon['status'] ) {
				continue;
			}

			if ( 'inactive' === $coupon_status && '1' === $coupon['status'] ) {
				continue;
			}

			$filtered_coupons[] = $coupon;
		}

		$total_items = count( $filtered_coupons );

		$filtered_coupons = array_slice(
			$filtered_coupons,
			( $current_page - 1 ) * $per_page,
			$per_page
		);

		$this->items = $filtered_coupons;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Change Coupon Status if expired.
	 *
	 * @param array $item Coupon data array.
	 * @return bool
	 */
	private function changeCouponStatus( $item ) {
		$today       = date_i18n( 'Y-m-d', strtotime( 'today' ) );
		$expiry_date = date_i18n( 'Y-m-d', strtotime( $item['end_date'] ) );

		if ( '1' === $item['status'] && strtotime( $expiry_date ) < strtotime( $today ) ) {
			$item['status'] = '0';
			$post_id        = wp_update_post(
				array(
					'ID'           => $item['id'],
					'post_content' => wp_json_encode( $item ),
					'post_type'    => 'evf_coupons',
				)
			);

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Empty the coupon trash.
	 */
	private function empty_coupon_trash() {
		$query = new \WP_Query(
			array(
				'post_type'   => 'evf_coupons',
				'post_status' => 'trash',
				'nopaging'    => true,
			)
		);

		while ( $query->have_posts() ) {
			$query->the_post();
			wp_delete_post( get_the_ID(), true );
		}
	}
}
