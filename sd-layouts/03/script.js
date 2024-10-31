jQuery(
	function( $ )
	{
		// Menu header
		if($('.sell-downloads-filters').length && !$('.sell-downloads-filters').is(':empty')) $('.sell-downloads-header').prepend( '<span class="header-handle"></span>' );
		$(document).on(
			'click',
			'.sell-downloads-header .header-handle',
			function()
			{
				$('.sell-downloads-filters').toggle(300);
			}
		);

		$(document)
		.on(
			'mouseover',
			'.product-payment-buttons input[type="image"]',
			function()
			{
				var me = $(this);
				if( !me.hasClass('rotate-in-hor'))
				{
					$(this).addClass('rotate-in-hor');
					setTimeout(
						function()
						{
							me.removeClass('rotate-in-hor');
						},
						1000
					);
				}

			}
		);

		// Set buttons classes
		$('.sd-shopping-cart-list .button,.sd-shopping-cart-list .button,.sd-shopping-cart-resume .button').addClass('bttn-stretch bttn-sm bttn-primary').removeClass('button').wrap('<span class="bttn-stretch bttn-sm bttn-primary" style="margin-top: -6px !important;"></span>');

		$('.sd-shopping-cart').next('.sell-downloads-song,.sell-downloads-collection').find('.left-column.single').css('padding-top','36px');
	}
);