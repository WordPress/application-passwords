/* global appPass, console, wp */
(function($,appPass){
	var $appPassSection        	  = $( '#application-passwords-section' ),
		$newAppPassForm           = $appPassSection.find( '.create-application-password' ),
		$newAppPassField          = $newAppPassForm.find( '.input' ),
		$newAppPassButton         = $newAppPassForm.find( '.button' ),
		$appPassTwrapper          = $appPassSection.find( '.application-passwords-list-table-wrapper' ),
		$appPassTbody             = $appPassSection.find( 'tbody' ),
		$appPassTrNoItems         = $appPassTbody.find( '.no-items' ),
		$removeAllBtn             = $( '#revoke-all-application-passwords' ),
		tmplNewAppPass            = wp.template( 'new-application-password' ),
		tmplAppPassRow            = wp.template( 'application-password-row' );

	$newAppPassButton.click( function(e){
		e.preventDefault();
		var name = $newAppPassField.val();

		if ( 0 === name.length ) {
			$newAppPassField.focus();
			return;
		}

		$newAppPassField.prop('disabled', true);
		$newAppPassButton.prop('disabled', true);

		$.ajax( {
			url        : appPass.root + appPass.namespace + '/application-passwords/' + appPass.user_id + '/add',
			method     : 'POST',
			beforeSend : function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', appPass.nonce );
			},
			data       : {
				name : name
			}
		} ).done( function ( response ) {
			$newAppPassField.prop( 'disabled', false ).val('');
			$newAppPassButton.prop( 'disabled', false );

			$newAppPassForm.after( tmplNewAppPass( {
				name     : name,
				password : response.password
			} ) );

			$appPassTbody.prepend( tmplAppPassRow( response.row ) );

			$appPassTwrapper.show();
			$appPassTrNoItems.remove();
		} );
	});

	$appPassTbody.on( 'click', '.delete', function(e){

		var revoke_password = confirm(appPass.translations.revoke_password);

		if (revoke_password === true) {
		    ap_revoke_password(e);
		} else {
			e.preventDefault();
		}
	});

	$removeAllBtn.on( 'click', function(e) {

		var revoke_all_passwords = confirm(appPass.translations.revoke_all_passwords);

		if (revoke_all_passwords === true) {
		  ap_revoke_all_passwords(e);
		} else {
			e.preventDefault();
		}
	});

	$(document).on('click', '.application-password-modal-dismiss', function(e){
		e.preventDefault();

		$('.new-application-password.notification-dialog-wrap').hide();
	});

	// If there are no items, don't display the table yet.  If there are, show it.
	if ( 0 === $appPassTbody.children( 'tr' ).not( $appPassTrNoItems ).length ) {
		$appPassTwrapper.hide();
	}

	/**
	 * Revoke a password from the list table
	 *
	 * @param element
	 */
	function ap_revoke_password(element) {

		element.preventDefault();
		var $tr  = $( element.target ).closest( 'tr' ),
			slug = $tr.data( 'slug' );

		$.ajax( {
			url        : appPass.root + appPass.namespace + '/application-passwords/' + appPass.user_id + '/' + slug,
			method     : 'DELETE',
			beforeSend : function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', appPass.nonce );
			}
		} ).done( function ( response ) {
			if ( response ) {
				if ( 0 === $tr.siblings().length ) {
					$appPassTwrapper.hide();
				}
				$tr.remove();
			}
		} );
	}

	/**
	 * Revoke a password from the list table
	 *
	 * @param element
	 */
	function ap_revoke_all_passwords(element) {

		element.preventDefault();

		$.ajax( {
			url        : appPass.root + appPass.namespace + '/application-passwords/' + appPass.user_id,
			method     : 'DELETE',
			beforeSend : function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', appPass.nonce );
			}
		} ).done( function ( response ) {
			// If we've successfully removed them…
			if ( parseInt( response, 10 ) > 0 ) {
				$appPassTbody.children().remove();
				$appPassSection.children( '.new-application-password' ).remove();
				$appPassTwrapper.hide();
			}
		} );
	}

})(jQuery,appPass);
