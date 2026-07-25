<?php
/**
 * Handles entry export.
 *
 * @package EverestForms-Pro\Includes\Export
 * @since   1.5.1
 */

defined( 'ABSPATH' ) || exit;

require EFP_ABSPATH . 'vendor/autoload.php';
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
/**
 * Export Entries.
 *
 * @since 1.5.1
 */
class EVF_Entry_Exporter {

	/**
	 * Export Entries.
	 *
	 * @since 1.5.1
	 *
	 * @param  array  $column_names Column Names.
	 * @param  array  $row_data Row Data.
	 * @param  array  $type Export File Type.
	 * @param  string $file_name File Name.
	 */
	public static function export( $column_names = array(), $row_data = array(), $type = 'csv', $file_name = 'evf-form-entry.csv' ) {
		try {
			if ( 'ods' === $type ) {
				$writer = WriterEntityFactory::createODSWriter();
			} elseif ( 'csv' === $type ) {
				$writer = WriterEntityFactory::createCSVWriter();
			} else {
				$writer = WriterEntityFactory::createXLSXWriter();
			}

			$writer->openToBrowser( $file_name );
			
			if ( count( $column_names ) ) {
				$column_headers = WriterEntityFactory::createRowFromArray( $column_names );
				$writer->addRow( $column_headers );
			}

			if ( count( $row_data ) ) {
				foreach ( $row_data as $row ) {
					$rowFromValues = WriterEntityFactory::createRowFromArray( $row );
					$writer->addRow( $rowFromValues );
				}
			}

			$writer->close();
			die();
		} catch ( Exception $e ) {
			exit( $e->getMessage() ); //phpcs:ignore
		}
	}
}
