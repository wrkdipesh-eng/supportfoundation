<?php
/**
 * Api Logs model.
 *
 * @since 1.7.8
 *
 * @package  EverestFormsPro/RestApi/ApiLogs/Model
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVFP_Api_Logs Class.
 */
class EVFP_Api_Logs_Model {
	/**
	 * Get the api logs.
	 *
	 * @since 1.7.8
	 *
	 * @param  [array] $params The params.
	 */
	public function get_api_logs( $params ) {
		global $wpdb;

		$sql          = "SELECT l.*, p.post_title as form_name FROM {$wpdb->prefix}evfp_api_logs as l LEFT JOIN {$wpdb->prefix}posts as p ON l.form_id = p.ID";
		$sql_count    = "SELECT COUNT(*) FROM {$wpdb->prefix}evfp_api_logs as l LEFT JOIN {$wpdb->prefix}posts as p ON l.form_id = p.ID";
		$whereClauses = array();

		if ( isset( $params['id'] ) ) {
			$sql    .= $wpdb->prepare( ' WHERE l.id = %d', $params['id'] );
			$results = $wpdb->get_results( $sql );
			return $results;
		}

		if ( isset( $params['searchByDates'] ) && ! empty( $params['searchByDates'] ) ) {
			$startDate      = gmdate( 'Y-m-d H:i:s', strtotime( $params['searchByDates']['startDate'] ) );
			$endDate        = gmdate( 'Y-m-d H:i:s', strtotime( $params['searchByDates']['endDate'] ) );
			$whereClauses[] = $wpdb->prepare(
				'l.created_date BETWEEN %s AND %s',
				$startDate,
				$endDate
			);
		}
		if ( isset( $params['searchByStatus'] ) && ! empty( $params['searchByStatus'] ) ) {
			$status         = $params['searchByStatus'];
			$whereClauses[] = $wpdb->prepare(
				'l.status  = %d',
				$status
			);
		}
		if ( isset( $params['searchByItem'] ) && ! empty( $params['searchByItem'] ) ) {
			$item_value     = '%' . $params['searchByItem'] . '%';
			$whereClauses[] = $wpdb->prepare(
				'l.entry_id LIKE %s OR l.status LIKE %s OR l.log LIKE %s OR l.source LIKE %s OR p.post_title LIKE %s',
				$item_value,
				$item_value,
				$item_value,
				$item_value,
				$item_value
			);
		}

		if ( ! empty( $whereClauses ) ) {
			$sql       .= ' WHERE ' . implode( ' OR ', $whereClauses );
			$sql_count .= ' WHERE ' . implode( ' OR ', $whereClauses );
		}
		$total_count = $wpdb->get_var( $sql_count );

		if ( isset( $params['limit'] ) && isset( $params['offset'] ) ) {
			if ( $params['offset'] === 0 ) {
				$params['offset'] = 1;
			}
			if ( $total_count <= $params['limit'] ) {
				$params['offset'] = 1;
			}
			$sql .= $wpdb->prepare( ' LIMIT %d, %d', ( $params['offset'] - 1 ) * $params['limit'], $params['limit'] );
		}
		$results = $wpdb->get_results( $sql );

		return array(
			'total_count' => $total_count,
			'result'      => $results,
		);
	}
	/**
	 * Remove the logs.
	 *
	 * @since 1.7.8
	 *
	 * @param  [integer] $id The log id.
	 */
	public function remove_log( $id ) {
		global $wpdb;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}evfp_api_logs WHERE id = %d", $id ) );
	}

}
