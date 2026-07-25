<?php
/**
 * Everest Forms Active Campaign Api Interface.
 */

namespace EverestForms\Pro\Addons\ActiveCampaign\API;

interface EVF_Interface_ActiveCampaign_Api {
	public function create( $data );
	public function retrieve( $id );
	public function update( $id, $data );
	public function delete( $id );
	public function list( $args );
}
