jQuery( document ).ready( function() {
	//Delete a list-item (e.g. delete a modul)
	jQuery( '#auf-filter-area' ).on( 'click', '.auf-delete', function( event ){
		event.preventDefault();
		jQuery( this ).closest( 'li' ).remove();
	});
	
	//Make elements drag & droppable
	jQuery( '.auf-js-draggable li' ).draggable();
	jQuery( '.auf-js-droppable' ).droppable({ 
		accept: ".auf-js-draggable li",
		hoverClass: "ui-state-highlight",
		drop: function( event, ui ) {
			var Element_ID = ui.draggable.attr( 'data-element' );
			var Element = aufElements[ Element_ID ];
			ui.draggable.css({left:'auto',top:'auto'});

			var $template = jQuery( jQuery( '#tmpl-filter-element' ).html() );
			$template.attr( 'data-element', Element_ID );
			$template.find( 'input.auf-element-id' ).val( Element_ID );
			$template.find( 'h3 .type ' ).text( Element.name );
			$template.find( '.element-area' ).html( jQuery( '#tmpl-filter-element-' + Element_ID ).html() );
			$template.appendTo( '#auf-filter-area' );
			aufPopulateModule( $template );
		}
	});

	//Open and close elements in #auf-filter-area
	jQuery( '#auf-filter-area' ).on( 'click', 'li>header>button', function() {
		jQuery( this ).closest( 'li' ).toggleClass( 'closed' );
		if ( 'true' === jQuery( this ).attr( 'aria-expanded' ) ) {
			jQuery( this ).attr( 'aria-expanded', 'false' );
		} else {
			jQuery( this ).attr( 'aria-expanded', 'true' );
		}
	});

	//Change label text of a filter element while input key
	jQuery( '#auf-filter-area' ).on( 'keyup', 'section[data-type="label"] input', function(){
		jQuery( this ).closest( 'li' ).find( 'header>h3 .label' ).text( jQuery( this ).val() );
	});

	//Go through all existing filter elements and load available sources
	jQuery( '#auf-filter-area li' ).each( function(){
		var error = false;

		//Check, if the element is registered
		if ( 'undefined' == typeof( aufElements[ jQuery( this ).attr( 'data-element' ) ] ) ) {
			aufError( 'The element "' + jQuery( this ).attr( 'data-element' ) + '" is not registered.' );
			error = true;
		}

		if ( ! error ) {
			aufPopulateModule( jQuery( this ) );


		}
	});
});

function aufPopulateModule( $modul ) {
	var Element = aufElements[ $modul.attr( 'data-element' ) ];
	var sources = {};

	var index = 1; //todo - find index
	var $select = $modul.find( 'section[data-type="source"] select' );
			
	for ( var i = 0; i < Element.sources.length; i++ ) {
		if ( 'undefined' == typeof( aufSources[ Element.sources[i] ] ) ) {
			aufError( 'The data source "' + Element.sources[i] + '" is not registered.' );
		} else {
			var current_source = aufSources[ Element.sources[i] ];					
			sources[ current_source.ID ] = { 'label':current_source.label, 'values' : [] };
			for ( var i_type = 0; i_type < Element.types.length; i_type++ ) {
				var current_type = Element.types[ i_type ];
				for ( i_current_source = 0; i_current_source < current_source.values.length; i_current_source++ ) {
					var current_source_type = current_source.values[ i_current_source ].type;
					if ( current_source_type == current_type ) {
						sources[ current_source.ID ].values.push( current_source.values[ i_current_source ] );
					}
				}
			}
		}
	}

	var options = '';
	var optgroup_open = false;
	for ( var sourceID in sources ) {
		if ( optgroup_open ) {
			options += '</optgroup>';
			optgroup_open = false;
		}

		if( sources[ sourceID ].values.length > 0 ) {
			options += '<optgroup label="' + sources[ sourceID ].label + '">';
			optgroup_open = true;
		} else {
			var selected = '';
			if ( $select.attr( 'data-selected' ) == sourceID + '::' + sourceID )
				selected = 'selected="selected"';
			options += '<option ' + selected + ' value="' + sourceID + '::' + sourceID + '">' + sources[ sourceID ].label + '</option>';
		}

		for ( var i = 0; i < sources[ sourceID ].values.length; i++ ) {
			var selected = '';
			if ( $select.attr( 'data-selected' ) == sourceID + '::' + sources[ sourceID ].values[i].ID )
				selected = 'selected="selected"';
			options += '<option ' + selected + ' value="' + sourceID + '::' + sources[ sourceID ].values[i].ID + '">' + sources[ sourceID ].values[i].label + '</option>';
		}
	}
	if ( optgroup_open ) {
		options += '</optgroup>';
	}
	console.log( options );
	$select.html( options );
}

function aufError( message ) {
	console.log( '[AUF::Error] ' + message );
}