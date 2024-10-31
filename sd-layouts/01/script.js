jQuery(
	function( $ )
	{
		// Correct the header and items width
		var correct_header = function()
			{
				$( '.sell-downloads-items,.sell-downloads-pagination' ).each(
					function()
					{
						var e = $( this );
						if( e.parents( '.widget' ).length == 0 )
						{
							e.css( 'min-width', $( '.sell-downloads-header' ).outerWidth() );
						}
					}
				);
			};

		correct_header();
		$( window ).on( 'load', correct_header );

		// Correct the item heights
		var height_arr = [],
			max_height = 0,
			correct_heights = function()
			{
				$( '.sell-downloads-items' ).children( 'div' ).each(
					function()
					{
						var e = $( this );
						if( e.hasClass( 'sell-downloads-item' ) )
						{
							max_height = Math.max( e.height(), max_height );
						}
						else
						{
							height_arr.push( max_height );
							max_height = 0;
						}
					}
				);

				if( height_arr.length )
				{
					$( '.sell-downloads-items' ).children( 'div' ).each(
						function()
						{
							var e = $( this );
							if( e.hasClass( 'sell-downloads-item' ) )
							{
								e.height( height_arr[ 0 ] );
							}
							else
							{
								height_arr.splice( 0, 1 );
							}
						}
					);
				}
			};

        $( window ).on( 'load', correct_heights );

		// Modify the price box
		$( '.product-price.invalid' ).remove();
		$( '.product-price:not(invalid)' ).each(
			function()
			{
				var e = $( this ).wrap('<div class="price-box"></div>')
			}
		);

		// Indicate the active tab
		$( '.sell-downloads-tabs' ).children( 'li' ).click(
			function()
			{
				var e = $( this ),
					p = e.position(),
					w = e.width()/2;

				if( $( '.sell-downloads-corner' ).length == 0 )
				$( '.sell-downloads-tabs-container' ).prepend( $( '<div class="sell-downloads-corner"></div>' ) );
				$( '.sell-downloads-corner' ).css( 'margin-left', ( p.left + w ) + 'px' );
			}
		);
		$( 'li.active-tab' ).click();
	}
);