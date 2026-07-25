/**
 * WooCommerce addons js for admin
 */
jQuery( function ( $ ) {

	// Check all checkbox.
	$(document).on(
		"change",
		".everest_forms_woocommerce_form_fields_wrapper .evfwc-select-all",
		function () {
		var $this = $(this),
			$parent_table = $this.closest(
			".everest_forms_woocommerce_form_fields_wrapper table"
			),
			$table_body = $parent_table.find("tbody");

		if ($this.is(":checked")) {
			$table_body
			.find('tr td:first-child input[type="checkbox"]')
			.prop("checked", true);
			$parent_table.find(".evfwc-select-all").prop("checked", true);
		} else {
			$table_body
			.find('tr td:first-child input[type="checkbox"]')
			.prop("checked", false);
			$parent_table.find(".evfwc-select-all").prop("checked", false);
		}
		}
	);

	//WooCommerce product tab panel handel
	$( "select.evfwc-product-tab-panel-select" ).on( 'change' , function () {
		handleWoocommerceProductPageSettings( $(this) );
	})

	function handleWoocommerceProductPageSettings( node ){
		if ( "0" === node.val() ) {
			$(document).find(".wp-list-table ").hide();
		}else{
			$(document).find(".wp-list-table ").show();
			var $table_wrapper = $(document)
			.find("div.everest_forms_woocommerce_form_fields_wrapper");
			var data = {
				action: "everest_forms_woocommerce_setting_form_field_listing",
				security: everest_forms_woocommerce_params.everest_forms_woocommerce_form_field_listing,
				form_id: node.val(),
				option_key: node.attr('product_form_field_key')
			};
			  if ("0" !== node.val()) {
				  $.ajax({
					  url: everest_forms_woocommerce_params.ajax_url,
					  data: data,
					  type: 'POST',
					  beforeSend: function () {
						node.attr("disabled", "disabled");
						},
					  success: function(response){
						  if (typeof response.data.table === "undefined") {
							  $table_wrapper.html("");
						  } else {
							  $table_wrapper.html(response.data.table);
						  }
					  },
					  complete: function () {
						node.prop("disabled", false);
						},
				  })
			  }else{
				$table_wrapper.html("");
			  }
		}
	}
})
