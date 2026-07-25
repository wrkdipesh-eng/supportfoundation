<?php
/**
 * Dashboard Analytics View For Entries.
 *
 * @package Analytics Dashboard.
 */

?>

<div id="evf-dashboard-analytics">
	<div class="evf-container evf-px-0">
		<div class="evf-d-flex evf-align-items-center evf-my-2"
			id="evf-dashboard-analytisc-header">
			<!-- <select id="evf-forms-analytics-form-list" class="evf-enhanced-normal-select"  style="min-width: 350px;">
				<?php //foreach ( $forms as $id => $title ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride ?>
					<option value="<?php //echo esc_attr( $id ); ?>"
						<?php //selected( $id, $selected_form_id ); ?>">
						<?php //echo esc_html( $title ); ?>
					</option>
				<?php //endforeach; ?>
			</select> -->
			<div class="evf-ml-auto evf-d-flex evf-align-items-center">
				<div class="everest-forms-btn-group everest-forms-btn-group--inline evf-mr-1"
					id="everest-forms-analytics-duration">
				<?php foreach ( $duration as $key => $value ) : ?>
					<a data-duration="<?php echo esc_attr( $key ); ?>"
						class="everest-forms-btn <?php echo esc_attr( $key === $selected_duration ? 'is-active' : '' ); ?>">
						<?php echo esc_html( $value ); ?>
					</a>
				<?php endforeach; ?>
				</div>
				<input id="evf-form-analytics-date-range"
					placeholder="<?php echo esc_attr__( 'Select date range', 'everest-forms-pro' ); ?>" />
			</div>
		</div>

		<div class="evf-pro-loader">
			<i class="evf-loading evf-loading-active"></i>
		</div>

		<!-- Submission summary analytics -->
		<div class="evf-row" style="display: none"
			id="evf-dashboard-analytisc-body">
			<div class="evf-col-md-4">
				<div class="everest-forms-card" id="everest-forms-analyltics-total-submission">
					<div class="everest-forms-card__body">
						<div class="evf-row evf-align-items-center">
							<div class="evf-col">
								<h2 class="evf-mt-0 evf-mb-2">
									<?php echo esc_html__( 'Total Submission', 'everest-forms-pro' ); ?>
								</h2>
								<span class="evf-h2">0</span>
							</div>
							<div class="evf-col-auto">
								<div class="evf-icon evf-d-inline-block evf-color-primary evf-bg-primary-soft">
									<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
								</div>
							</div>
						</div>
						<div class="chart-container evf-mt-2" style="width: 100%; height: 80px">
							<canvas></canvas>
						</div>
					</div>
				</div>
			</div>
			<div class="evf-col-md-4">
				<div class="everest-forms-card" id="everest-forms-analyltics-complete-submission">
					<div class="everest-forms-card__body">
						<div class="evf-row evf-align-items-end">
							<div class="evf-col">
								<h2 class="evf-mt-0 evf-mb-2">
									<?php echo esc_html__( 'Complete Submission', 'everest-forms-pro' ); ?>
								</h2>
								<span class="evf-h2">0</span>
							</div>
							<div class="evf-col-auto">
								<div class="evf-icon evf-d-inline-block evf-color-primary evf-bg-primary-soft">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M15,7h2.59L15,4.41ZM8.29,14.29a1,1,0,0,1,1.42,0L11,15.59l3.29-3.3a1,1,0,0,1,1.42,1.42l-4,4a1,1,0,0,1-1.42,0l-2-2A1,1,0,0,1,8.29,14.29ZM19,9H14a1,1,0,0,1-1-1V3H6A1,1,0,0,0,5,4V20a1,1,0,0,0,1,1H18a1,1,0,0,0,1-1Zm2,11a3,3,0,0,1-3,3H6a3,3,0,0,1-3-3V4A3,3,0,0,1,6,1h8a1.09,1.09,0,0,1,.39.08,1,1,0,0,1,.32.21l6,6a.93.93,0,0,1,.21.33A1,1,0,0,1,21,8Z"/></svg>
								</div>
							</div>
						</div>
						<div class="chart-container evf-mt-2" style="width: 100%; height: 80px">
							<canvas></canvas>
						</div>
					</div>
				</div>
			</div>
			<div class="evf-col-md-4">
				<div class="everest-forms-card" id="everest-forms-analyltics-incomplete-submission">
					<div class="everest-forms-card__body">
						<div class="evf-row evf-align-items-end">
							<div class="evf-col">
								<h2 class="evf-mt-0 evf-mb-2">
									<?php echo esc_html__( 'Incomplete Submission', 'everest-forms-pro' ); ?>
								</h2>
								<span class="evf-h2">0</span>
							</div>
							<div class="evf-col-auto">
								<div class="evf-icon evf-d-inline-block evf-color-primary evf-bg-primary-soft">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M5,20a1,1,0,0,0,1,1H18a1,1,0,0,0,1-1V9H14a1,1,0,0,1-1-1V3H6A1,1,0,0,0,5,4ZM17.59,7,15,4.41V7Zm-.67,9.38a1,1,0,0,1-.21.33l-2,2a1,1,0,0,1-1.42,0,1,1,0,0,1,0-1.42l.3-.29H10a3,3,0,0,1,0-6h1a1,1,0,0,1,0,2H10a1,1,0,0,0,0,2h3.59l-.3-.29a1,1,0,0,1,1.42-1.42l2,2a1,1,0,0,1,.21.33A1,1,0,0,1,16.92,16.38ZM21,20a3,3,0,0,1-3,3H6a3,3,0,0,1-3-3V4A3,3,0,0,1,6,1h8a1.09,1.09,0,0,1,.39.08,1,1,0,0,1,.32.21l6,6a.93.93,0,0,1,.21.33A1,1,0,0,1,21,8Z"/></svg>
								</div>
							</div>
						</div>
						<div class="chart-container evf-mt-2" style="width: 100%; height: 80px">
							<canvas></canvas>
						</div>
					</div>
				</div>
			</div>
			<!-- ./ Submission summary analytics -->

			<div class="evf-col-md-8">
				<div class="everest-forms-card"
					id="everest-forms-entries-analytics">
					<div class="everest-forms-card__header evf-d-flex evf-align-items-center">
						<h3 class="evf-my-0"><?php esc_html_e( 'Entries', 'everest-forms-pro' ); ?></h3>
						<div class="everest-forms-btn-group evf-ml-auto"
							id="evf-entries-chart-types">
							<a href="#" data-chart-type="bar"
								class="everest-forms-btn everest-forms-btn-icon is-active"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" height="20" width="20"><path class="cls-1" d="M6,18H2V10H6Zm6,0H8V7h4Zm6,0H14V2h4Z"></path></svg></a>
							<a href="#" data-chart-type="pie"
								class="everest-forms-btn everest-forms-btn-icon evf-mx-1"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" height="20" width="20"><path class="cls-1" d="M9.5,18A7.49,7.49,0,0,1,9,3v7h8c0,.17,0,.33,0,.5A7.5,7.5,0,0,1,9.5,18ZM10,9V2a8,8,0,0,1,7.93,7Z"></path></svg></a>
							<a href="#" data-chart-type="line"
								class="everest-forms-btn everest-forms-btn-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" height="20" with="20"><path class="cls-1" d="M18,3.5a1.5,1.5,0,1,0-2.64,1l-3.51,7.58A1.34,1.34,0,0,0,11.5,12a1.48,1.48,0,0,0-1,.42L8.89,11.05A1.42,1.42,0,0,0,9,10.5a1.5,1.5,0,1,0-2.69.9L2,18l15,0V4.91A1.51,1.51,0,0,0,18,3.5ZM16,17,3.83,17,7.14,12A1.49,1.49,0,0,0,7.5,12a1.5,1.5,0,0,0,.73-.2L10,13.36c0,.05,0,.09,0,.14a1.5,1.5,0,0,0,3,0,1.57,1.57,0,0,0-.3-.9L16,5.47Z"></path></svg></a>
						</div>
					</div>
					<div class="everest-forms-card__body">
						<div class="chart-container">
							<canvas></canvas>
						</div>
					</div>
				</div>
			</div>

			<div class="evf-col-md-4">
				<div class="everest-forms-card evf-device-analytics">
					<div class="everest-forms-card__header">
						<h3 class="evf-my-0"><?php esc_html_e( 'Device Breakdown', 'everest-forms-pro' ); ?></h3>
					</div>
					<div class="everest-forms-card__body">
						<div class="chart-container">
							<canvas style="width: 100%; height: 200px"></canvas>
						</div>
					</div>
					<table class="everest-forms-table">
						<thead>
							<th><?php esc_html_e( 'Device', 'everest-forms-pro' ); ?></th>
							<th><?php esc_html_e( 'Usage', 'everest-forms-pro' ); ?></th>
						</thead>
						<tbody>
							<tr>
								<td><?php esc_html_e( 'Desktop', 'everest-forms-pro' ); ?></td>
								<td data-type="Desktop">0</td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Tablet', 'everest-forms-pro' ); ?></td>
								<td data-type="Tablet">0</td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Mobile', 'everest-forms-pro' ); ?></td>
								<td data-type="Mobile">0</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
