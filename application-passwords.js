/* global appPass, console, wp */
(function($,appPass){
	var $appPassSection   = $( '#application-passwords-section' ),
		$newAppPassForm   = $appPassSection.find( '.create-application-password' ),
		$newAppPassField  = $newAppPassForm.find( '.input' ),
		$newAppPassButton = $newAppPassForm.find( '.button' ),
		$appPassTbody     = $appPassSection.find( 'tbody' ),
		$appPassTrNoItems = $appPassTbody.find( '.no-items' ),
		$removeAllBtn     = $( '#revoke-all-application-passwords' ),
		tmplNewAppPass    = wp.template( 'new-application-password' ),
		tmplAppPassRow    = wp.template( 'application-password-row' );

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

			$appPassTrNoItems.hide();
		} );
	});

	$appPassTbody.on( 'click', '.delete a', function(e){
		e.preventDefault();
		var $tr  = $( e.target ).closest( 'tr' ),
			slug = $tr.data( 'slug' );

		$.ajax( {
			url        : appPass.root + appPass.namespace + '/application-passwords/' + appPass.user_id + '/' + slug,
			method     : 'DELETE',
			beforeSend : function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', appPass.nonce );
			}
		} ).done( function ( response ) {
			if ( response ) {
				$tr.remove();
			}
		} );
	});

	$removeAllBtn.on( 'click', function(e) {
		e.preventDefault();

		$.ajax( {
			url        : appPass.root + appPass.namespace + '/application-passwords/' + appPass.user_id,
			method     : 'DELETE',
			beforeSend : function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', appPass.nonce );
			}
		} ).done( function ( response ) {
			// If we've successfully removed them…
			if ( parseInt( response, 10 ) > 0 ) {
				$appPassTbody.children().not( $appPassTrNoItems ).remove();
				$appPassTrNoItems.show();
				$appPassSection.children( '.new-application-password' ).remove();
			}
		} );
	});
})(jQuery,appPass);