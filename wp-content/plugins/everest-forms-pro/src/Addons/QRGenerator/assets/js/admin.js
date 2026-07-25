/**
 * EverestFormsQRGenerator JS
 *
 * @since 1.7.9
 */
(function ($) {
    var EverestFormsQRGenerator = {

		init: function () {
            EverestFormsQRGenerator.bindUIActions();
        },

        /**
         * Element bindings
         */
        bindUIActions: function () {
            // QRGenerator Toggler
            $(document).on(
                "change",
                "#everest-forms-panel-field-settingsqr_generator-evf_enable_public_link",
                function (e) {
                    EverestFormsQRGenerator.toggleContent(e, this);
                }
            );

			$( document ).on( "click", "#everest_forms_generate_qr_btn", function ( e ){
				e.preventDefault();
				EverestFormsQRGenerator.generateQr();
			} )
        },

		 /**
         * Toggle QRGenerator.
         */
		toggleContent: function (e, el) {
			var $this = $(el),
			value = $this.prop('checked');

			if (false === value) {
                $this.val('');
				$(".evf-content-qr_generator-settings-inner").hide();
            } else if (true === value) {
				$this.val('1');
				$(".evf-content-qr_generator-settings-inner").removeClass('everest-forms-hidden').show();

            }
		},

		/**
		 * Generate QR Code.
		 *
		 * @since 1.7.9
		 */
		generateQr: function (){
			var qrContent = $( "#everest_forms_public_link_qr" ).val();

			$.alert({
				title: '',
				content: `<div id="everest_forms_qr_code_outer_wrapper">
								<div id="everest_forms_qr_code_text_container">
									<div id="everest_forms_qr_icon">
										<img src="${qr_generator.qr_icon}" alt="QR icon" />
									</div>
									<div>
										<div id="everest_forms_scan_qr_title">Scan QR Code</div>
										<div id="everest_forms_qr_description">Scan this QR code to get your Public Link.</div>
									</div>
								</div>
								<div id="everest_forms_qr_image_container">
									<img id="everest_forms_qr_image" src="${qrContent}" alt="QR Code" />
								</div>
								<button id="everest_forms_download_qr">Download</button>
							</div>
							`,
				icon: '',
				type: 'blue',
				buttons: {
					ok: {
						btnClass: 'btn-confirm',
						keys: [ 'enter' ]
					}
				},
				onContentReady : function(){
					$(".jconfirm-buttons").hide();
					$(document).on('click', "#everest_forms_download_qr", function(e) {
						e.preventDefault();
						let public_link = $('#everest_forms_public_link_qr').val();

						if (public_link) {
							const img = new Image();

							// Create a canvas element
							const canvas = document.createElement('canvas');
							const ctx = canvas.getContext('2d');

							// Set image source to the SVG data
							img.onload = function() {
								// Set canvas dimensions to match the SVG size
								canvas.width = img.width;
								canvas.height = img.height;

								// Draw the SVG image onto the canvas
								ctx.drawImage(img, 0, 0);

								// Export the canvas content to a PNG data URL
								const pngDataUrl = canvas.toDataURL('image/png');

								// Create a link element to download the PNG
								const link = document.createElement('a');
								link.href = pngDataUrl;
								link.download = 'qr_code.png'; // Specify the filename for the PNG
								document.body.appendChild(link);
								link.click();
								document.body.removeChild(link);
							};

							// Set the source of the image to the SVG data (skip the prefix)
							img.src = public_link;
						} else {
							console.error("No QR code available to download.");
						}
					});
				}
			});
		}
	}
    EverestFormsQRGenerator.init();
})(jQuery);
