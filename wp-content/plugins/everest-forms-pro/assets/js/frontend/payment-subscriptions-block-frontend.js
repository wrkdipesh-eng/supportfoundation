/**
 * Payment History block — transaction detail modal (frontend).
 */
( function () {
	'use strict';

	function esc( s ) {
		if ( s === null || s === undefined ) {
			return '';
		}
		var d = document.createElement( 'div' );
		d.textContent = String( s );
		return d.innerHTML;
	}

	function closeModal( root ) {
		var backdrop = root.querySelector( '.evf-ps-modal__backdrop' );
		var panel = root.querySelector( '.evf-ps-modal' );
		if ( backdrop ) {
			backdrop.hidden = true;
			backdrop.setAttribute( 'aria-hidden', 'true' );
		}
		if ( panel ) {
			panel.hidden = true;
		}
		document.body.classList.remove( 'evf-ps-modal-open' );
	}

	function openModal( root ) {
		var backdrop = root.querySelector( '.evf-ps-modal__backdrop' );
		var panel = root.querySelector( '.evf-ps-modal' );
		if ( backdrop ) {
			backdrop.hidden = false;
			backdrop.setAttribute( 'aria-hidden', 'false' );
		}
		if ( panel ) {
			panel.hidden = false;
		}
		document.body.classList.add( 'evf-ps-modal-open' );
	}

	function renderDetail( container, d ) {
		var title =
			( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.transaction ) ||
			'Transaction';
		var html =
			'<h2 class="evf-ps-modal__title" id="evf-ps-modal-title">' +
			esc( title ) +
			' ' +
			esc( d.transaction_no ) +
			'</h2>';
		html += '<div class="evf-ps-meta-grid">';
		html +=
			'<div class="evf-ps-meta-grid__cell"><strong>' +
			esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.transactionHash ) || 'Transaction #' ) +
			'</strong><span>' +
			esc( d.transaction_no ) +
			'</span></div>';
		html +=
			'<div class="evf-ps-meta-grid__cell"><strong>' +
			esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.date ) || 'Date' ) +
			'</strong><span>' +
			esc( d.date ) +
			'</span></div>';
		html +=
			'<div class="evf-ps-meta-grid__cell"><strong>' +
			esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.method ) || 'Payment Method' ) +
			'</strong><span>' +
			esc( d.payment_method ) +
			'</span></div>';
		html +=
			'<div class="evf-ps-meta-grid__cell"><strong>' +
			esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.status ) || 'Payment Status' ) +
			'</strong><span>' +
			esc( d.payment_status ) +
			'</span></div>';
		html += '</div>';

		html += '<table class="evf-ps-line-table"><thead><tr>';
		html +=
			'<th>' +
			esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.item ) || 'Item' ) +
			'</th>';
		html +=
			'<th>' +
			esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.qty ) || 'Quantity' ) +
			'</th>';
		html +=
			'<th>' +
			esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.price ) || 'Price' ) +
			'</th>';
		html +=
			'<th>' +
			esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.lineTotal ) || 'Line Total' ) +
			'</th>';
		html += '</tr></thead><tbody>';

		( d.line_items || [] ).forEach( function ( row ) {
			html += '<tr>';
			html += '<td>' + esc( row.item ) + '</td>';
			html += '<td>' + esc( row.quantity ) + '</td>';
			html += '<td>' + esc( row.price ) + '</td>';
			html += '<td>' + esc( row.line_total ) + '</td>';
			html += '</tr>';
		} );

		html += '<tr class="evf-ps-line-table__subtotal">';
		html +=
			'<td colspan="3" class="evf-ps-line-table__label">' +
			esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.subtotal ) || 'Sub-Total' ) +
			'</td>';
		html += '<td>' + esc( d.subtotal ) + '</td>';
		html += '</tr>';
		if ( d.discount !== null && d.discount !== undefined && d.discount !== '' ) {
			html += '<tr class="evf-ps-line-table__discount">';
			html +=
				'<td colspan="3" class="evf-ps-line-table__label" style="text-align:right">' +
				esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.discount ) || 'Discount' ) +
				'</td>';
			html += '<td> - ' + esc( d.discount ) + '</td>';
			html += '</tr>';
		}
		html += '<tr class="evf-ps-line-table__total">';
		html +=
			'<td colspan="3" class="evf-ps-line-table__label">' +
			esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.total ) || 'Total' ) +
			'</td>';
		html += '<td>' + esc( d.total ) + '</td>';
		html += '</tr>';

		html += '</tbody></table>';

		html += '<div class="evf-ps-customer"><h3 class="evf-ps-customer__heading">';
		html +=
			esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.customerDetails ) || 'Customer Details' );
		html += '</h3><ul class="evf-ps-customer__list">';
		html +=
			'<li><strong>' +
			esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.customerName ) || 'Customer Name' ) +
			':</strong> ' +
			esc( d.customer_name ) +
			'</li>';
		html +=
			'<li><strong>' +
			esc( ( window.evfPsBlock && window.evfPsBlock.i18n && window.evfPsBlock.i18n.customerEmail ) || 'Customer Email' ) +
			':</strong> ' +
			esc( d.customer_email ) +
			'</li>';
		html += '</ul></div>';

		container.innerHTML = html;
	}

	function fetchDetail( entryId, bodyEl, root ) {
		var cfg = window.evfPsBlock || {};
		var url =
			( cfg.restUrl || '' ).replace( /\/$/, '' ) +
			'/everest-forms-pro/v1/user-payment-entry/' +
			encodeURIComponent( entryId );

		bodyEl.innerHTML =
			'<p class="evf-ps-modal__loading">' +
			esc( cfg.i18n && cfg.i18n.loading ? cfg.i18n.loading : 'Loading…' ) +
			'</p>';

		openModal( root );

		fetch( url, {
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': cfg.nonce || '',
			},
		} )
			.then( function ( r ) {
				return r.json().then( function ( j ) {
					return { ok: r.ok, json: j };
				} );
			} )
			.then( function ( res ) {
				var j = res.json;
				if ( ! res.ok || ! j.success || ! j.data ) {
					var msg =
						( j && j.message ) ||
						( j && j.data && j.data.message ) ||
						( cfg.i18n && cfg.i18n.error ) ||
						'Unable to load transaction.';
					bodyEl.innerHTML =
						'<p class="evf-ps-modal__error">' + esc( msg ) + '</p>';
					return;
				}
				renderDetail( bodyEl, j.data );
			} )
			.catch( function () {
				bodyEl.innerHTML =
					'<p class="evf-ps-modal__error">' +
					esc( ( cfg.i18n && cfg.i18n.error ) || 'Unable to load transaction.' ) +
					'</p>';
			} );
	}

	function initBlock( block ) {
		block.addEventListener( 'click', function ( e ) {
			var accBtn = e.target.closest( '.js-evf-toggle-related-payments' );
			if ( accBtn && block.contains( accBtn ) ) {
				e.preventDefault();
				var panelId = accBtn.getAttribute( 'aria-controls' );
				var panel = panelId ? document.getElementById( panelId ) : null;
				if ( ! panel || ! block.contains( panel ) ) {
					return;
				}
				var cfg = window.evfPsBlock || {};
				var i18n = cfg.i18n || {};
				var expanded = accBtn.getAttribute( 'aria-expanded' ) === 'true';
				if ( expanded ) {
					panel.hidden = true;
					accBtn.setAttribute( 'aria-expanded', 'false' );
					accBtn.textContent = i18n.viewRelatedPayments || 'View Payments';
				} else {
					panel.hidden = false;
					accBtn.setAttribute( 'aria-expanded', 'true' );
					accBtn.textContent = i18n.hideRelatedPayments || 'Hide Payments';
				}
				return;
			}

			var body = block.querySelector( '.evf-ps-modal__body' );
			var btn = e.target.closest( '.js-evf-ps-view' );
			if ( btn && block.contains( btn ) ) {
				e.preventDefault();
				if ( ! body ) {
					return;
				}
				var id = btn.getAttribute( 'data-entry-id' );
				if ( id ) {
					fetchDetail( id, body, block );
				}
				return;
			}
			if ( e.target.closest( '.evf-ps-modal__close' ) || e.target.classList.contains( 'evf-ps-modal__backdrop' ) ) {
				closeModal( block );
			}
		} );
	}

	function init() {
		document.querySelectorAll( '.evf-payment-subscriptions-block' ).forEach( initBlock );
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key !== 'Escape' ) {
				return;
			}
			document.querySelectorAll( '.evf-payment-subscriptions-block' ).forEach( function ( block ) {
				var bd = block.querySelector( '.evf-ps-modal__backdrop' );
				if ( bd && ! bd.hidden ) {
					closeModal( block );
				}
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
