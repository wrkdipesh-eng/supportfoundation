/**
 * EverestFormsEntries JS
 */
jQuery( function ( $ ) {
	var EverestFormsEntries = {
		settings: {
			spinner: '<div class="evf-pro-loader"><i class="evf-loading evf-loading-active" /></div>',
			duration: 'month'
		},
		charts: {
			submission: {}
		},
		data: {},
		options: {
			submissionSummaryChart: {
				fill: true,
				backgroundColor: '#ff0000',
				legend: {
					display: false
				},
				responsive : true,
				maintainAspectRatio: false,
				scales: {
					xAxes: [{
						display: false,
						type: 'time',
						distribution: 'series',
					}],
					yAxes: [{
						display: false,
						ticks: {
							beginAtZero: true,
							display: false
						}
					}]
				}
			},
			deviceChart: {
				elements: {
					arc: {
						backgroundColor: randomColor({
							luminosity: 'light',
							count: 3
						})
					}
				}
			},
			entriesChart: {
				time: {
					responsive : true,
					scales: {
						xAxes: [{
							type: 'time',
							distribution: 'series',
						}],
						yAxes: [{
							ticks: {
								beginAtZero: true,
							}
						}]
					}
				},
				pie: {
					legend: {
						position: 'bottom'
					},
					elements: {
						arc: {
							backgroundColor: [],
							hoverBackgroundColor: []
						}
					}
				}
			}
		},

		init: function() {
			var form_id;
			everest = this.settings;
			if( evfp_params.form_id.length < 1 ){
				form_id = $('#evf-forms-analytics-form-list option:selected').val();
			} else {
				form_id = evfp_params.form_id;
			}
			if( undefined !== form_id ){
				EverestFormsEntries.bindUIActions(form_id);
				EverestFormsEntries.refreshDashboard( form_id );
			}
		},

		/**
		 * Element bindings
		 */
		bindUIActions: function(formId) {
			var duration = 'day';

			$( '#everest-forms-analytics-duration' ).click( function( e ) {
				e.preventDefault();
				duration = $( e.target ).data( 'duration' );
				EverestFormsEntries.refreshDashboard( formId, duration );
				EverestFormsEntries.settings.duration = duration;
			} );

			$( '#evf-forms-analytics-form-list' ).change( function( e ) {
				formId = $( this ).val();
				$('#filter-by-form').val( formId );
				$('#post-query-submit').trigger('click');
			} );

			$( '#evf-form-analytics-date-range' ).flatpickr( {
				mode: "range",
				dateFormat: "Y-m-d",
				onChange: function( value ) {
					if ( 1 === value.length ) {
						return;
					}

					var from = moment( value[0].toUTCString() ).format( 'YYYY-MM-DD' );
					var to = moment( value[1].toUTCString() ).format( 'YYYY-MM-DD' );

					EverestFormsEntries.refreshDashboard( formId, duration, from, to );
				}
			} );

			$( '#evf-entries-chart-types' ).click( function( e ) {
				e.preventDefault();

				var $entries = $( '#everest-forms-entries-analytics' );
				var ctx;
				var type     = $( e.target ).closest( 'a' ).data( 'chart-type' );
				var chart    = EverestFormsEntries.charts.entries;
				var data     = EverestFormsEntries.processSeries( EverestFormsEntries.data.entries.total.series );

				if( $entries.length > 0 ) {
					ctx = $entries.find('canvas').get(0).getContext('2d');
				}
				if( undefined !== ctx ) {
					if ( 'bar' === type || 'line' === type ) {
						chart.destroy();
						EverestFormsEntries.charts.entries = EverestFormsEntries.generateChart( ctx, type, data );
					} else if( 'pie' === type ) {
						chart.destroy();
						var series = EverestFormsEntries.data.entries.total.series;
						EverestFormsEntries.options.entriesChart.pie.elements.arc.backgroundColor = randomColor({
							luminosity: 'light',
							count : Object.keys( series ).length
						});
						EverestFormsEntries.options.entriesChart.pie.elements.arc.hoverBackgroundColor = randomColor({
							luminosity: 'light',
							count : Object.keys( series ).length
						});

						var data = EverestFormsEntries.processSeries( series );
						var labels = $.map( data, function(  datum ) {
							return datum['t'];
						} );
						var values = $.map( data, function(  datum ) {
							return datum['y'];
						} );

						EverestFormsEntries.charts.entries = new Chart( ctx, {
							type: 'pie',
							responsive: true,
							data: {
								datasets: [{
									data: Object.values(values),
								}],
								labels: Object.values(labels)
							},
							options: EverestFormsEntries.options.entriesChart.pie
						});
					}
				}
			} );
		},

		generateChart: function( context, type, data ) {
			type = type || 'bar';
			var color =  randomColor({ luminosity: 'light' });

			return new Chart( context, {
				type: type,
				data : {
					datasets: [{
						label: 'Entries',
						data: data,
						lineTension: 0.000001,
						borderColor: color,
						backgroundColor: color,
					}]
				},
				options: EverestFormsEntries.options.entriesChart.time
			} );
		},

		refreshDashboard: function( formId, duration, from, to ) {
			duration = duration || 'month';
			from     = from || '';
			to       = to || '';

			$.ajax({
				url: evfp_params.ajax_url,
				method: 'POST',
				data: {
					action: 'evfp_get_analytics',
					form_id: formId,
					duration: duration,
					from: from,
					to: to
				},
				beforeSend: function( xhr, settings ) {
					$( '#evf-dashboard-analytisc-body' ).hide();
					if( 0 === $( '#evf-dashboard-analytics' ).find( '.evf-pro-loader').length ){
						$('#evf-dashboard-analytisc-header').after('<div class="evf-pro-loader"><i class="evf-loading evf-loading-active"></i></div>');
					}
				}
			} ).done( function( result ) {
				EverestFormsEntries.data = result.data;
				EverestFormsEntries.populateDevice( result.data['device'] );
				EverestFormsEntries.populateSubmissionSummary( result.data['entries'] );
				EverestFormsEntries.displaySubmissionChart( result.data['entries'] );
				EverestFormsEntries.displayEntriesChart( result.data['entries'] );
			} ).fail( function( error ) {
				// $.alert({
				// 	boxWidth: '30%',
				// 	useBootstrap: false,
				// 	title: 'Error',
				// 	content: 'Something went wrong. Please, try again'
				// });
			} ).complete( function( xhr, textStatus) {
				$( '#evf-dashboard-analytics' ).find( '.evf-pro-loader').remove();
				$( '#evf-dashboard-analytisc-body' ).show();
			});
		},

		processSeries: function( series ) {
			var data = [];
			var formattedTime = '';
			for( time in series ) {
				formattedTime = moment( time ).format( 'MMMM Do, YYYY' );
				data.push( {
					t: formattedTime,
					y: series[time]
				});
			}

			return data;
		},

		displayEntriesChart: function( submissions ) {
			var ctx;
			var $entries     = $( '#everest-forms-entries-analytics' );
			var entriesChart = EverestFormsEntries.charts.entries;
			var data         = EverestFormsEntries.processSeries( submissions.total.series );

			if( $entries.length > 0 ) {
				ctx = $entries.find('canvas').get(0).getContext('2d');
			}
			if( undefined !== ctx ){
				if( undefined === entriesChart ) {
					EverestFormsEntries.charts.entries = EverestFormsEntries.generateChart( ctx, 'bar', data );
				} else {
					entriesChart.data.datasets[0].data = data;
					entriesChart.update();
				}
			}
		},

		populateSubmissionSummary: function( submissions ) {
			var $total = $( '#everest-forms-analyltics-total-submission' );
			var $complete = $( '#everest-forms-analyltics-complete-submission' );
			var $incomplete = $( '#everest-forms-analyltics-incomplete-submission' );

			$total.find( '.evf-h2' ).html( submissions.total.count );
			$complete.find( '.evf-h2' ).html( submissions.complete.count );
			$incomplete.find( '.evf-h2' ).html( submissions.incomplete.count );
		},

		displaySubmissionChart: function( submissions ) {
			var submissionType = [ 'total', 'complete', 'incomplete' ];

			$.each( submissionType, function(index, type ) {
				$submission = $('#everest-forms-analyltics-' + type + '-submission');
				$submission.find('p').html( submissions[type].count);

				var data            = EverestFormsEntries.processSeries( submissions[type].series );
				var ctx;
				var submissionChart = EverestFormsEntries.charts.submission[ type ];
				var color           = randomColor({ luminosity: 'light' });

				if( $submission.length > 0 ){
					ctx = $submission.find('canvas').get(0).getContext('2d');
				}

				if( undefined !== ctx ){
					if( undefined === submissionChart ) {
						EverestFormsEntries.charts.submission[type] = new Chart( ctx, {
							type: 'line',
							data : {
								datasets: [{
									data: data,
									lineTension: 0.000001,
									borderWidth: 2,
									borderColor: color,
									backgroundColor: color,
								}]
							},
							options: EverestFormsEntries.options.submissionSummaryChart
						} );
					} else {
						submissionChart.data.datasets[0].data = data;
						submissionChart.update();
					}
				}
			});
		},

		populateSubmissionSummary: function( submissions ) {
			var $total = $( '#everest-forms-analyltics-total-submission' );
			var $complete = $( '#everest-forms-analyltics-complete-submission' );
			var $incomplete = $( '#everest-forms-analyltics-incomplete-submission' );

			var $complete_percentage = Math.floor( parseInt( submissions.complete.count ) / parseInt( submissions.total.count ) * 100 );
			var $incomplete_percentage = Math.floor( parseInt( submissions.incomplete.count ) / parseInt( submissions.total.count ) * 100 );

			$total.find( '.evf-h2' ).html( submissions.total.count );
			$complete
				.find(".evf-h2")
				.html(
					$complete_percentage + '%'
				)
				.attr('title', 'Count: ' + submissions.complete.count);
			$incomplete
				.find(".evf-h2")
				.html(
					$incomplete_percentage + '%'
				)
				.attr('title', 'Count: ' + submissions.incomplete.count);
		},

		displaySubmissionChart: function( submissions ) {
			var submissionType = [ 'total', 'complete', 'incomplete' ];

			$.each( submissionType, function(index, type ) {
				$submission = $('#everest-forms-analyltics-' + type + '-submission');
				$submission.find('p').html( submissions[type].count);

				var ctx;
				var data = [];
				var submissionChart = EverestFormsEntries.charts.submission[ type ];
				var color           = randomColor({ luminosity: 'light' });

				for( time in submissions.complete.series ) {
					data.push( {
						t: new Date(time).toISOString() + '',
						y: submissions[type].series[time]
					});
				}

				if( $submission.length > 0 ){
					ctx = $submission.find('canvas').get(0).getContext('2d');
				}

				if( undefined !== ctx ) {
					if( undefined === submissionChart ) {
						EverestFormsEntries.charts.submission[type] = new Chart( ctx, {
							type: 'line',
							data : {
								datasets: [{
									data: data,
									lineTension: 0.000001,
									borderWidth: 2,
									borderColor: color,
									backgroundColor: color,
								}]
							},
							options: EverestFormsEntries.options.submissionSummaryChart
						} );
					} else {
						submissionChart.data.datasets[0].data = data;
						submissionChart.update();
					}
				}
			});
		},

		populateDevice: function( devices ) {
			$container = $( '#evf-dashboard-analytics' ).find( '.evf-device-analytics' );
			$.each( devices, function(key, device) {
				$container.find('td[data-type="' + key + '"]').html( device );
			} );

			var ctx;

			if($container.length > 0){
				ctx = $container.find('canvas').get(0).getContext( '2d' );
			}

			if( undefined !== ctx){
				var deviceChart = EverestFormsEntries.charts.device;
				if( undefined === deviceChart ) {
					EverestFormsEntries.charts.device = new Chart( ctx, {
						type: 'doughnut',
						responsive: true,
						data: {
							datasets: [{
								data: Object.values(devices)
							}],
							labels: Object.keys(devices),
						},
						options: EverestFormsEntries.options.deviceChart
					} );
				} else {
					deviceChart.data.datasets[0].data = Object.values(devices);
					deviceChart.update();
				}
			}

		}
	}

	if( undefined !== evfp_params.form_id ) {
		EverestFormsEntries.init();
	}
} );
