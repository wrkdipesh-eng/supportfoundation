/**
 * EverestFormsSmsNotifications JS
 */
 (function ($) {
	var EverestFormsSmsNotifications = {
		settings: {
			form   : $('#everest-forms-builder-form'),
			spinner: '<i class="evf-loading evf-loading-active" />'
		},
		init: function () {

			s = this.settings;

			$('.everest-forms-active-sms-notifications-connections-list li').first().addClass('active-user');
			$('.evf-content-sms-notifications-settings-inner').first().addClass('active-connection');

			EverestFormsSmsNotifications.bindUIActions();
		},
		ready: function() {

			s.formID = $('#everest-forms-builder-form').data('id');
		},

		/**
		 * Element bindings
		 */
		 bindUIActions: function () {
			// Twilio Toggler
			$(document).on(
				"change",
				".evf-content-sms-notifications-settings .evf-toggle-switch input",
				function (e) {
					EverestFormsSmsNotifications.toggleContent(e, this);
				}
			);

			$(document).ready(function(){
				EverestFormsSmsNotifications.serviceSettings();
				EverestFormsSmsNotifications.clickSendServiceSelector();
			});

			//SMS Service Selector.
			$(document).on('change click', '#everest-forms-panel-field-sms_notifications-sms_service_type', function(e){
				EverestFormsSmsNotifications.serviceSelector(e, this);
			})

			//Selects the ClickSend Service type.
			$(document).on('change click', '#everest-forms-panel-field-sms_notifications-clicksend_service_type', function(e){
				EverestFormsSmsNotifications.clickSendServiceSelector();
			})

			$(document).on('click', '.everest-forms-sms-notifications-add', function(e) {
				EverestFormsSmsNotifications.connectionAdd(this, e);
			});

			$(document).on('click', '.everest-forms-active-sms-notifications-connections-list li', function(e) {
				EverestFormsSmsNotifications.selectActiveAccount(this, e);
			});

			$(document).on('click', '.sms-notifications-remove', function(e) {
				EverestFormsSmsNotifications.removeAccount(this, e);
			});

			$(document).on('click', '.sms-notifications-default-remove', function(e) {
				EverestFormsSmsNotifications.removeDefaultAccount(this, e);
			});

			$(document).on('input', '.everest-forms-sms-notifications-name input', function(e) {
				EverestFormsSmsNotifications.renameConnection(this, e);
		   });

		   $(document).on('focusin', '.everest-forms-sms-notifications-name input', function(e) {
			EverestFormsSmsNotifications.focusConnectionName(this, e);
		});
		$(document).on('createSMSNotificationConnection', '.everest-forms-sms-notifications-add', function(e, data){
			EverestFormsSmsNotifications.addNewSMSNotificationonnection($(this), data);
		});

		$( document ).on( 'mouseenter', '.evf-content-sms-notifications-settings-inner .everest-forms-help-tooltip:not(.tooltipstered)', function() {
			$( this ).tooltipster({
				maxWidth: 200,
				multiple: true,
				interactive: true,
				position: 'bottom',
				contentAsHTML: true,
				updateAnimation: false,
				restoration: 'current',
				functionInit: function( instance, helper ) {
					var $origin = $( helper.origin ),
						dataTip = $origin.attr( 'data-tip' );
					if ( dataTip ) {
						instance.content( dataTip );
					}
				}
			});
			$( this ).tooltipster( 'open' );
		});

		$( document ).on( 'click', '.everest-forms-sms-notifications-add', function() {
			$( '.evf-content-sms-notifications-settings-inner .tooltipstered' ).tooltipster( 'destroy' );
		});

		},

	/**
	 * Toggle Twilio.
	 *
	 * @since 1.7.9
	 */
		toggleContent: function (e, el) {
			var $this = $( el ),
			value = $this.prop('checked');
				if ( false === value ) {
					$this.val('');
					$this.closest( '.evf-content-sms-notifications-settings' ).find( '.sms-notifications-message' ).remove();
					$this.closest( '.evf-content-sms-notifications-title' ).siblings( '.evf-content-sms-notifications-settings-inner' ).addClass( 'everest-forms-hidden' );
					$( '<p class="sms-notifications-disable-message everest-forms-notice everest-forms-notice-info">' + everest_forms_sms_notifications_params.sms_notifications_disable_message + '</p>' ).insertAfter( $this.closest( '.evf-content-sms-notifications-title' ) );
				} else if ( true === value ) {
					$this.val('1');
					$this.closest( '.evf-content-section-title' ).siblings( '.evf-content-sms-notifications-settings-inner' ).removeClass( 'everest-forms-hidden' );
					$this.closest( '.evf-content-sms-notifications-settings' ).find( '.sms-notifications-disable-message' ).remove();
				}

		},

			connectionAdd: function(el, e) {
				e.preventDefault();

				var $this    = $(el),
				source       = 'sms-notifications',
				type         = $this.data('type'),
				namePrompt   = everest_forms_sms_notifications_params.i18n_sms_notifications_connection,
				nameField    = '<input autofocus="" type="text" id="provider-connection-name" placeholder="'+everest_forms_sms_notifications_params.i18n_sms_notifications_placeholder+'">',
				nameError    = '<p class="error">'+everest_forms_sms_notifications_params.i18n_sms_notifications_error_name+'</p>',
				modalContent = namePrompt+nameField+nameError;

				modalContent = modalContent.replace(/%type%/g,type);
				$.confirm({
					title: false,
					content: modalContent,
				   icon: 'dashicons dashicons-info',
					type: 'blue',
					backgroundDismiss: false,
					closeIcon: false,
					buttons: {
						confirm: {
							text: everest_forms_sms_notifications_params.i18n_sms_notifications_ok,
							btnClass: 'btn-confirm',
							keys: ['enter'],
							action: function() {
								var input = this.$content.find('input#provider-connection-name');
								var error = this.$content.find('.error');
								var value = input.val().trim();
								if ( value.length === 0 ) {
									error.show();
									return false;
								} else {
									var name = value;

								   // Fire AJAX
								   var data =  {
									   action  : 'everest_forms_new_sms_notification_add',
									   source  : source,
									   name    : name,
									   id      : s.form.data('id'),
									   security: everest_forms_sms_notifications_params.ajax_sms_notifications_nonce
								   }
								   $.ajax({
									   url: everest_forms_sms_notifications_params.ajax_url,
									   data: data,
									   type: 'POST',
									   success: function(response) {
										EverestFormsSmsNotifications.addNewSMSNotificationonnection($this, {response:response, name:name});
									   }
								   });
							   }
						   }
					   },
					   cancel: {
						   text: everest_forms_sms_notifications_params.i18n_sms_notifications_cancel
					   }
				   }
			   });
		   	},

			addNewSMSNotificationonnection: function( el, data ){
			var $this= el;
			var response = data.response;
			var name = data.name;
			var $connections = $this.closest('.everest-forms-panel-sidebar-content');
			var form_title = $('#everest-forms-panel-field-settings-form_title:first').val() + '-' + Date.now();
			var cloned_sms_notifications = $('.evf-content-sms-notifications-settings').first().clone();
			$('.evf-content-sms-notifications-settings-inner').removeClass('active-connection');
			 cloned_sms_notifications.find('input:not(#qt_everest_forms_panel_field_sms_notifications_sms_notification_connection_1_sms_notifications_message_toolbar input[type="button"], .evf_conditional_logic_container input)').val('');
			cloned_sms_notifications.find('.evf_conditional_logic_container input[type="checkbox"]').prop('checked', false);
			cloned_sms_notifications.find('.evf-field-conditional-container').hide();
			cloned_sms_notifications.find('.evf-field-conditional-wrapper li:not(:first)').remove();
			cloned_sms_notifications.find('.conditional_or:not(:first)').remove();
			cloned_sms_notifications.find('.everest-forms-sms-notifications-name input').val(name);

			setTimeout(function() {
				cloned_sms_notifications.find('.evf-field-conditional-input').val('');
			}, 2000);

			cloned_sms_notifications.find('.evf-content-sms-notifications-settings-inner').attr('data-connection_id',response.data.connection_id);
			cloned_sms_notifications.find('.evf-content-sms-notifications-settings-inner').removeClass( 'everest-forms-hidden' );

			//Twilio toggle options.
			cloned_sms_notifications.find( '.evf-toggle-switch input' ).attr( 'name', 'settings[sms_notifications][' + response.data.connection_id + '][enable_sms_notification]' );
			cloned_sms_notifications.find( '.evf-toggle-switch input:checkbox' ).attr( 'data-connection-id',  response.data.connection_id );
			cloned_sms_notifications.find( '.evf-toggle-switch input:checkbox' ).prop( 'checked', true );
			cloned_sms_notifications.find( '.evf-toggle-switch input:checkbox' ).val( '1' );

			// Hiding Toggle for Prevous Sms Notifications Setting.
			$('.evf-content-sms-notifications-settings .evf-content-section-title').css( 'display', 'none' );
			// Removing sms-notifications-disable-message;
			$( '.sms-notifications-disable-message' ).remove();
			// Removing Cloned sms-notifications-disable-message;
			cloned_sms_notifications.find( '.sms-notifications-disable-message' ).remove();
			// Showing Toggle for Current Twilio Setting.
			cloned_sms_notifications.find( '.evf-toggle-switch' ).parents( '.evf-content-section-title' ).css( 'display', 'flex' );

			cloned_sms_notifications.find('.evf-field-conditional-container').attr('data-connection_id',response.data.connection_id);
			cloned_sms_notifications.find('#everest-forms-panel-field-sms_notifications-sms_notification_connection_1-connection_name').attr('name', 'settings[sms_notifications]['+response.data.connection_id+'][connection_name]');
			cloned_sms_notifications.find('#everest-forms-panel-field-sms_notifications-sms_notification_connection_1-sms_notifications_user_phone_no').attr('name', 'settings[sms_notifications]['+response.data.connection_id+'][sms_notifications_user_phone_no]');
			cloned_sms_notifications.find('#everest-forms-panel-field-sms_notifications-sms_notification_connection_1-sms_notifications_user_phone_no').val( '' );
			cloned_sms_notifications.find('#everest_forms_panel_field_sms_notifications_sms_notification_connection_1_sms_notifications_message').attr('name', 'settings[sms_notifications]['+response.data.connection_id+'][sms_notifications_message]');
			cloned_sms_notifications.find('#everest_forms_panel_field_sms_notifications_sms_notification_connection_1_sms_notifications_message').val( 'Thanks for contacting us! We will be in touch with you shortly');

			cloned_sms_notifications.find('#everest-forms-panel-field-sms_notifications-sms_notification_connection_1-conditional_logic_status').attr('name', 'settings[sms_notifications]['+response.data.connection_id+'][conditional_logic_status]');
			cloned_sms_notifications.find('.evf_conditional_logic_container input[type="hidden"]').attr('name', 'settings[sms_notifications]['+response.data.connection_id+'][conditional_logic_status]');
			cloned_sms_notifications.find('.evf-field-show-hide').attr('name', 'settings[sms_notifications]['+response.data.connection_id+'][conditional_option]');
			cloned_sms_notifications.find('.evf-field-conditional-field-select').attr('name', 'settings[sms_notifications]['+response.data.connection_id+'][conditionals][1][1][field]');
			cloned_sms_notifications.find('.evf-field-conditional-condition').attr('name', 'settings[sms_notifications]['+response.data.connection_id+'][conditionals][1][1][operator]');
			cloned_sms_notifications.find('.evf-field-conditional-input').attr('name', 'settings[sms_notifications]['+response.data.connection_id+'][conditionals][1][1][value]');
			$cloned_sms_notifications = cloned_sms_notifications.append('<input type="hidden" name="settings[sms_notifications]['+response.data.connection_id+'][connection_name]" value="'+name+'">');

			$('.evf-sms-notifications-settings-wrapper').append(cloned_sms_notifications);
			$connections.find('.evf-content-sms-notifications-settings-inner').last().addClass('active-connection');
			$this.parent().find('.everest-forms-active-sms-notifications-connections-list li').removeClass('active-user');
			$this.closest('.everest-forms-active-sms-notifications.active').children('.everest-forms-active-sms-notifications-connections-list').removeClass('empty-list');
			$this.parent().find('.everest-forms-active-sms-notifications-connections-list ').append( '<li class="connection-list active-user" data-connection-id= "'+response.data.connection_id+'"><a class="user-nickname" href="#">'+name+'</a><a href="#"><span class="sms-notifications-remove">Remove</span></a></li>' );
		    },

			selectActiveAccount: function(el, e) {
				e.preventDefault();

				var $this         = $(el),
				connection_id = $this.data('connection-id'),
				active_block  = $('.evf-content-sms-notifications-settings').find('[data-connection_id="' + connection_id + '"]'),
				lengthOfActiveBlock = $(active_block).length;

				$('.evf-content-sms-notifications-settings').find('.evf-content-sms-notifications-settings-inner').removeClass('active-connection');

				// Hiding Twilio Notificaton Trigger (Previous).
				$( '.evf-content-section-title' ).has('[data-connection-id=' + $this.siblings('.active-user').attr( 'data-connection-id' ) +']').css( 'display', 'none' );
				$this.siblings().removeClass('active-user');
				$this.addClass('active-user');

				if( lengthOfActiveBlock ){
					$( active_block ).addClass('active-connection');
				}

				// Removing SMS Notifications Notification Turn On Message.
				$('.sms-notifications-disable-message').remove();
				if( $( 'input[data-connection-id=' + $this.attr( 'data-connection-id' ) +']:last' ).prop( 'checked' ) == false ) {
					$( '<p class="sms-notifications-disable-message everest-forms-notice everest-forms-notice-info">' +  everest_forms_sms_notifications_params.sms_notifications_disable_message + '</p>' ).insertAfter( $( '.evf-content-sms-notifications-title' ).has('[data-connection-id=' + $this.attr( 'data-connection-id' ) +']') );
				}

				// Displaying SMS Notificatons Trigger (Current).
				$( '.evf-content-section-title' ).has('[data-connection-id=' + $this.attr( 'data-connection-id' ) +']').css( 'display', 'flex' );
			},

			removeAccount: function(el, e) {
				e.preventDefault();
				var $this = $(el),
				connection_id = $this.parent().parent().data('connection-id'),
				active_block  = $('.evf-content-sms-notifications-settings').find('[data-connection_id="' + connection_id + '"]'),
				lengthOfActiveBlock = $(active_block).length;
					$.confirm({
						title: false,
						content: "Are you sure you want to delete this SMS Notifications?",
						backgroundDismiss: false,
						closeIcon: false,
						icon: 'dashicons dashicons-info',
						type: 'orange',
						buttons: {
							confirm: {
								text: everest_forms_sms_notifications_params.i18n_sms_notifications_ok,
								btnClass: 'btn-confirm',
								keys: ['enter'],
								action: function(){
									if( lengthOfActiveBlock ){
										var toBeRemoved = $this.parent().parent();
										active_block_after  = $('.evf-provider-connections').find('[data-connection_id="' + connection_id + '"]'),
										lengthOfActiveBlockAfter = $(active_block).length;
										if( toBeRemoved.prev().length ){
											toBeRemoved.prev('.connection-list').trigger('click');
										}else {
											toBeRemoved.next('.connection-list').trigger('click');
										}

										$( active_block ).parent().remove();
										toBeRemoved.remove();
									}
								}
							},
							cancel: {
								text: everest_forms_sms_notifications_params.i18n_sms_notifications_cancel
							}
						}
					});
			},

			removeDefaultAccount: function( el, e ) {
				e.preventDefault;
				$.alert({
					title: false,
					content: "Default SMS Notifications can not be deleted !",
					icon: 'dashicons dashicons-info',
					type: 'blue',
					buttons: {
						ok: {
							text: everest_forms_sms_notifications_params.i18n_ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ]
						}
					}
				});
			},

			focusConnectionName: function( el,e ){
				var $this = $(el);
				$this.data('val', $this.val().trim());
			},

			renameConnection: function( el,e ){
				e.preventDefault;
				var $this = $(el);
				var connection_id = $this.closest('.evf-content-sms-notifications-settings-inner').data('connection_id');
				$active_block = $('.everest-forms-active-sms-notifications-connections-list').find('[data-connection-id="' + connection_id + '"]');
				$active_block.find('.user-nickname').text($this.val());
				if ( $this.val().trim().length === 0 ) {
					$this.parent('.everest-forms-sms-notifications-name').find('.everest-forms-error').remove();
					$this.parent('.everest-forms-sms-notifications-name').append('<p class="everest-forms-error everest-forms-text-danger">SMS Notifications name cannot be empty.</p>');
					$this.next('.everest-forms-error').fadeOut(3000);
					setTimeout(function() {
						if ( $this.val().length === 0 ){
							$this.val($this.data('val'));
							$active_block.find('.user-nickname').text($this.data('val'));
						}
					}, 3000);
				}
			},

			/**
			 * SMS notification service type selector.
			 *
			 * @since 1.7.9
			*/
			serviceSelector: function( e, el ){
				e.preventDefault();
				var $this = $( el );
				if('twilio' === $this.val()){
					$($this).closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_service_type-wrap').addClass('everest-forms-hidden');
					$($this).closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_contact_list-wrap').addClass('everest-forms-hidden');
					$($this).closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-sms_notification_connection_1-clicksend_campaign_name-wrap').addClass('everest-forms-hidden');
					$($this).closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_campaign_name-wrap').addClass('everest-forms-hidden');
				} else {
					var clicksend_type = $this.closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_service_type').val();
					$this.closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_service_type-wrap').removeClass('everest-forms-hidden');

					if( 'single_sms' === clicksend_type ) {
						$this.closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_contact_list-wrap').addClass('everest-forms-hidden');
						$this.closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-sms_notification_connection_1-clicksend_campaign_name-wrap').addClass('everest-forms-hidden');
					} else {
						$this.closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_contact_list-wrap').removeClass('everest-forms-hidden');
						$this.closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-sms_notification_connection_1-clicksend_campaign_name-wrap').removeClass('everest-forms-hidden');
					}
				}
			},

			serviceSettings: function(){
				var service_type = $('#everest-forms-panel-field-sms_notifications-sms_service_type').val();
				if('twilio' === service_type){
					$('#everest-forms-panel-field-sms_notifications-sms_service_type').closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_service_type-wrap').addClass('everest-forms-hidden');
					$('#everest-forms-panel-field-sms_notifications-sms_service_type').closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_contact_list-wrap').addClass('everest-forms-hidden');
					$('#everest-forms-panel-field-sms_notifications-sms_service_type').closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-sms_notification_connection_1-clicksend_campaign_name-wrap').addClass('everest-forms-hidden');
				}else{
					$('#everest-forms-panel-field-sms_notifications-sms_service_type').closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_service_type-wrap').removeClass('everest-forms-hidden');
					$('#everest-forms-panel-field-sms_notifications-sms_service_type').closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_contact_list-wrap').removeClass('everest-forms-hidden');
					$('#everest-forms-panel-field-sms_notifications-sms_service_type').closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-sms_notification_connection_1-clicksend_campaign_name-wrap').removeClass('everest-forms-hidden');
				}
			},

			/**
			 * ClickSend Notification Service Type Selector.
			 *
			 * @since 1.7.9
			 */
			clickSendServiceSelector: function(){
				var click_send_service_type = $('#everest-forms-panel-field-sms_notifications-clicksend_service_type').val()

				if('single_sms' === click_send_service_type){
					$('#everest-forms-panel-field-sms_notifications-clicksend_service_type').closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_campaign_name-wrap').addClass('everest-forms-hidden');
					$('#everest-forms-panel-field-sms_notifications-clicksend_service_type').closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_contact_list-wrap').addClass('everest-forms-hidden');
					$('#everest-forms-panel-field-sms_notifications-clicksend_service_type').closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-sms_notification_connection_1-clicksend_campaign_name-wrap').addClass('everest-forms-hidden');
				}else{
					$('#everest-forms-panel-field-sms_notifications-clicksend_service_type').closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_campaign_name-wrap').removeClass('everest-forms-hidden');
					$('#everest-forms-panel-field-sms_notifications-clicksend_service_type').closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-clicksend_contact_list-wrap').removeClass('everest-forms-hidden');
					$('#everest-forms-panel-field-sms_notifications-clicksend_service_type').closest('.evf-content-sms-notifications-settings-inner').find('#everest-forms-panel-field-sms_notifications-sms_notification_connection_1-clicksend_campaign_name-wrap').removeClass('everest-forms-hidden');
				}
			}

	};
	EverestFormsSmsNotifications.init(jQuery);
})(jQuery);
