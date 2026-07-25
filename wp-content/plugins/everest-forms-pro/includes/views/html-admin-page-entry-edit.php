<?php
/**
 * Admin View: Edit Entry
 *
 * @package EverestForms/Admin/Entries/Views
 */

defined( 'ABSPATH' ) || exit;

$form_id    = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$entry_id   = isset( $_GET['edit-entry'] ) ? absint( $_GET['edit-entry'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$entry      = evf_get_entry( $entry_id, true );
$form_data  = evf()->form->get( $form_id, array( 'content_only' => true ) );
$hide_empty = isset( $_COOKIE['everest_forms_entry_hide_empty'] ) && 'true' === $_COOKIE['everest_forms_entry_hide_empty'];

$form_atts = array(
	'id'    => 'everest-forms-edit-entry-form',
	'class' => array( 'everest-form', 'everest-forms-validate' ),
	'data'  => array(
		'formid' => $form_id,
	),
	'atts'  => array(
		'method'  => 'POST',
		'enctype' => 'multipart/form-data',
		'action'  => esc_url_raw( remove_query_arg( 'everest-forms' ) ),
	),
);

// For hooks that might need meta (payment details, quiz results, etc.).
$entry_fields = json_decode( $entry->fields );
$entry_meta   = apply_filters( 'everest_forms_entry_single_data', $entry->meta, $entry, $form_data );

?>
<div class="wrap everest-forms evf-entry-view-wrapper">
	<!-- Header Section -->
	<div class="evf-entry-header">
		<div class="evf-entry-header-left">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-entries&amp;form_id=' . $form_id ) ); ?>" class="evf-back-link">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 22"><path d="M10.352 3.935a.917.917 0 0 1 1.296 1.297l-5.769 5.767 5.769 5.77a.916.916 0 1 1-1.296 1.296l-6.417-6.417a.917.917 0 0 1 0-1.296z"/><path d="M17.416 10.083a.917.917 0 0 1 0 1.834H4.583a.917.917 0 0 1 0-1.834z"/></svg>
			</a>
			<div class="evf-entry-title">
				<?php
				/* translators: %s: Entry ID */
				printf(
					esc_html__( '%1$s: Entry #%2$s', 'everest-forms' ),
					esc_html( _draft_or_post_title( $form_id ) ),
					absint( $entry_id )
				);
				?>
			</div>
		</div>
	</div>

	<!-- Main Content Wrapper -->
	<div class="evf-entry-content-wrapper">
		<!-- Left Column: Entry Fields -->
		<div class="evf-entry-main-content">
			<?php
			printf( '<div id="everest-forms-%d" class="everest-forms-edit-entry-container">', (int) $form_id );
			echo '<form ' . evf_html_attributes( $form_atts['id'], $form_atts['class'], $form_atts['data'], $form_atts['atts'] ) . '>';
			?>

			<!-- Edit Entry Content (all editable fields) -->
			<?php do_action( 'everest_forms_entry_details_edit_content', $entry, $form_data ); ?>

			<?php if ( current_user_can( 'everest_forms_edit_entry', $entry->entry_id ) ) : ?>
				<!-- Save / Cancel buttons inside the card, bottom-right -->
				<div class="evf-entry-edit-actions">
					<?php
					// This will output the Update + Cancel buttons (display_action_button()).
					do_action( 'everest_forms_entry_details_sidebar_action', $entry, $form_data );
					?>
				</div>
			<?php endif; ?>

			</form>
			</div>

			<?php
			// Extra content under the form if needed.
			do_action( 'everest_forms_entry_details_content', $entry, $form_id );
			?>

			<!-- Entry Details Section (same style as View Entry screen) -->
			<div id="everest-forms-entry-details-table" class="evf-entry-section evf-entry-details-section stuffbox">
				<div class="evf-section-header">
					<div class="evf-section-title hndle">
						<span><?php esc_html_e( 'Entry Details', 'everest-forms' ); ?></span>
					</div>
				</div>

				<div class="evf-section-content inside">
					<div class="evf-details-table-wrapper">
						<table class="evf-details-table wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'ID', 'everest-forms' ); ?></th>
									<th><?php esc_html_e( 'User', 'everest-forms' ); ?></th>
									<th><?php esc_html_e( 'IP', 'everest-forms' ); ?></th>
									<th><?php esc_html_e( 'Status', 'everest-forms' ); ?></th>
									<th><?php esc_html_e( 'Submitted Date', 'everest-forms' ); ?></th>
									<?php if ( ! empty( $entry->date_modified ) ) : ?>
										<th><?php esc_html_e( 'Modified Date', 'everest-forms' ); ?></th>
									<?php endif; ?>
									<th><?php esc_html_e( 'Referer Link', 'everest-forms' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><?php echo absint( $entry_id ); ?></td>
									<td>
										<?php
										if ( ! empty( $entry->user_id ) && 0 !== $entry->user_id ) {
											$user      = get_userdata( $entry->user_id );
											$user_name = ! empty( $user->display_name ) ? $user->display_name : $user->user_login;
											$user_url  = add_query_arg(
												array(
													'user_id' => absint( $user->ID ),
												),
												admin_url( 'user-edit.php' )
											);
											echo '<a href="' . esc_url( $user_url ) . '">' . esc_html( $user_name ) . '</a>';
										} else {
											esc_html_e( 'Guest', 'everest-forms' );
										}
										?>
									</td>
									<td><?php echo ! empty( $entry->user_ip_address ) ? esc_html( $entry->user_ip_address ) : '-'; ?></td>
									<td>
										<?php
										$status       = ! empty( $entry->status ) ? sanitize_text_field( $entry->status ) : 'completed';
										$status_class = 'evf-status-' . strtolower( $status );
										?>
										<span class="evf-status-badge <?php echo esc_attr( $status_class ); ?>">
											<?php echo esc_html( ucwords( $status ) ); ?>
										</span>
									</td>
									<td><?php echo esc_html( date_i18n( 'Y-m-d H:i:s', strtotime( $entry->date_created ) + ( get_option( 'gmt_offset' ) * 3600 ) ) ); ?></td>
									<?php if ( ! empty( $entry->date_modified ) ) : ?>
										<td><?php echo esc_html( date_i18n( 'Y-m-d H:i:s', strtotime( $entry->date_modified ) + ( get_option( 'gmt_offset' ) * 3600 ) ) ); ?></td>
									<?php endif; ?>
									<td>
										<?php if ( ! empty( $entry->referer ) ) : ?>
											<a href="<?php echo esc_url( $entry->referer ); ?>" target="_blank" class="evf-referer-link">
												<?php esc_html_e( 'View', 'everest-forms' ); ?>
											</a>
										<?php else : ?>
											-
										<?php endif; ?>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<?php do_action( 'everest_forms_entry_details_sidebar_details', $entry, $entry_meta, $form_data ); ?>
				</div>
			</div>
		</div><!-- /.evf-entry-main-content -->

		<!-- Right Sidebar -->
		<div class="evf-entry-sidebar">

			<!-- Entry Actions (like View Entry sidebar) -->
			<div id="everest-forms-entry-actions" class="stuffbox">
				<div class="evf-section-header-actions"><?php esc_html_e( 'Entry Actions', 'everest-forms' ); ?></div>
				<div class="inside">
					<div class="everest-forms-entry-actions-meta">
						<?php
						// Favourites, Mark Unread, Approve, Export CSV, etc.
						do_action( 'everest_forms_entry_details_sidebar_actions', $entry, $form_data );
						?>

						<?php if ( current_user_can( 'everest_forms_delete_entry', $entry->entry_id ) ) : ?>
							<?php
							$trash_link = wp_nonce_url(
								add_query_arg(
									array(
										'trash' => $entry_id,
									),
									admin_url( 'admin.php?page=evf-entries&form_id=' . $form_id )
								),
								'trash-entry'
							);
							?>
							<p class="everest-forms-entry-delete">
								<a href="<?php echo esc_url( $trash_link ); ?>">
									<span class="dashicons dashicons-trash"></span>
									<?php esc_html_e( 'Delete Entry', 'everest-forms' ); ?>
								</a>
							</p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<?php
			// Payment details, quiz scores, etc.
			do_action( 'everest_forms_after_entry_details', $entry, $entry_meta, $form_data );
			?>
		</div><!-- /.evf-entry-sidebar -->
	</div><!-- /.evf-entry-content-wrapper -->
</div><!-- /.wrap -->

<!-- Toggle displaying empty fields -->
<script type="text/javascript">
jQuery(document).on(
	'click',
	'#everest-forms-entry-fields .everest-forms-empty-field-toggle',
	function (event) {
		event.preventDefault();

		var $btn = jQuery(this);

		if (wpCookies.get('everest_forms_entry_hide_empty') === 'true') {
			wpCookies.remove('everest_forms_entry_hide_empty');

			$btn
				.removeClass('dashicons-hidden')
				.addClass('dashicons-visibility')
				.attr('title', '<?php esc_attr_e( 'Hide password', 'everest-forms' ); ?>')
				.text('<?php esc_html_e( 'Hide Empty Fields', 'everest-forms' ); ?>');
		} else {
			wpCookies.set('everest_forms_entry_hide_empty', 'true', 2592000);

			$btn
				.removeClass('dashicons-visibility')
				.addClass('dashicons-hidden')
				.attr('title', '<?php esc_attr_e( 'Show password', 'everest-forms' ); ?>')
				.text('<?php esc_html_e( 'Show Empty Fields', 'everest-forms' ); ?>');
		}

		jQuery('.everest-forms-edit-entry-field.empty').toggle();
	}
);

</script>
<?php
wp_print_admin_notice_templates();
