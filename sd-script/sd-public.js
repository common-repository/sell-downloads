var codepeople_sell_downloads = function(){
	var $ = jQuery;
	if('undefined' != typeof $.codepeople_sell_downloads_flag) return;
	$.codepeople_sell_downloads_flag = true;

	var hurl  = ( sd_global && sd_global['hurl'] ) ? sd_global['hurl'] : '/',
		hurlp = hurl+((hurl.indexOf('?') == -1) ? '?' : '&');

	function _increasePopularity( e )
	{
		e = $(e)
		var r = e.data('title'),
			p = e.closest('[data-productid]'),
			id = (p.length) ? p.data('productid') : 0;
		if(id)
			$.post(
				hurl,
				{'sd_action':'popularity', 'id':id, 'review':r},
				(function(id){
					return function(data)
					{
						if(data && 'average' in data)
						{
							var r = Math.floor(data.average)*1, v = data.votes*1;
							$('[data-productid="'+id+'"]').each(function(){
								var e = $(this);
								e.find('.star').each(function(i,s){
									if(i+1 <= r) $(s).removeClass('star-inactive').addClass('star-active');
									else $(s).removeClass('star-active').addClass('star-inactive');
								});
								e.find('.votes').text(v);
							});
						}
					};
				})(id),
				'json'
			);
	};

	// Replace the popularity texts with the stars
	$( '.product-popularity' ).each(
		function()
		{
			var e = $( this ),
				p = e.data('popularity'),
				v = e.data('votes'),
				str = '';

			for( var i = 1; i <= 5; i++ )
				str += '<div class="'+((i<=p) ? 'star-active' : 'star-inactive')+' star" data-title="'+i+'"></div>';
			str += '<span class="votes">'+v+'</span>';
			e.html( str );
		}
	);

	$(document).on('click', '[data-productid] .star', function(){_increasePopularity(this);});

	$('.sell-downloads-tabs').click(function(evt){
		var m = $(this),
			t = $(evt.target);

		m.find('.active-tab').removeClass('active-tab');
		t.addClass('active-tab');
		$('.sell-downloads-tabs-container').removeClass('active-tab').eq(m.children().index(t)).addClass('active-tab');
	});

	// ****** FUNCTIONS FOR DEMO ****** //
	$( document ).on( 'click', '.sd-demo-close', function( evt ){
		evt.preventDefault();

		var e  = $( evt.target );
		    c  = e.parents( '.sd-demo-container' ),
			sl = c.attr( 'sl' ),
			st = c.attr( 'st' );

		c.remove();
		$( 'body,html' ).removeClass( 'sd-demo' ).scrollLeft( sl ).scrollTop( st );
	} );

	$( window ).on( 'resize', function(){
		var b  = $( 'body' ),
		    h  = b.height();

		b.find( '#sd_demo_object' ).attr( 'height', ( h - b.find( '.sd-demo-head' ).height() ) + 'px' );
	} );

	$( '.sd-demo-link' ).click(function( evt ){
		evt.preventDefault();

		var e  = $( evt.target ),
		    m  = $(e).attr( 'mtype' ),
			l  = e.attr( 'href' ),
			i  = l.indexOf( 'file=' ),
			t  = $( 'html' ),
			b  = $( 'body' ),
			close_txt = ( typeof sd_global != 'undefined' && typeof sd_global.texts != 'undefined' && typeof sd_global.texts.close_demo != 'undefined' ) ? sd_global.texts.close_demo : 'close',
			download_txt = ( typeof sd_global != 'undefined' && typeof sd_global.texts != 'undefined' && typeof sd_global.texts.download_demo != 'undefined' ) ? sd_global.texts.download_demo : 'download file',
			plugin_fault_txt = ( typeof sd_global != 'undefined' && typeof sd_global.texts != 'undefined' && typeof sd_global.texts.plugin_fault != 'undefined' ) ? sd_global.texts.plugin_fault : 'The Object to display the demo file is not enabled in your browser. CLICK HERE to download the demo file',
			sl = b.scrollLeft(),
			st = b.scrollTop();

		l = decodeURIComponent( l.substr( i+5 ) );

		t.addClass( 'sd-demo' );
		b.addClass( 'sd-demo' );

		var h = $( window ).height();

		b.append( $( '<div class="sd-demo-container" sl="'+sl+'" st="'+st+'" style="width:100%;height:100%;top:0;left:0;bottom:o;right:0;position:fixed;z-index:99999;">\
		                <div class="sd-demo-head">\
						  <a href="'+e.attr( 'href' )+'">'+download_txt+'</a>\
						  <a href="#" class="sd-demo-close">'+close_txt+'</a>\
						</div>\
					    <div class="sd-demo-body" style="width:100%;height:100%;top:0;left:0;bottom:o;right:0;">\
			              <object id="sd_demo_object" data="'+l+'" type="'+m+'" style="width:100%;height:100%;top:0;left:0;bottom:o;right:0;"> \
						     <div style="margin-top:40px;">\
				             <a href="'+e.attr( 'href' )+'">'+plugin_fault_txt+'</a>\
							 </div>\
			              </object>\
					    </div>\
					  </div>' ) );

		b.find( '#sd_demo_object' ).attr( 'height', ( h - b.find( '.sd-demo-head' ).height() ) + 'px' );
	});

	$( '.sd-demo-media' ).mediaelementplayer();
	timeout_counter = 30;
    window['sell_downloads_counting'] = function()
    {
        var loc = document.location.href;
        document.getElementById( "sell_downloads_error_mssg" ).innerHTML = timeout_text+' '+timeout_counter;
        if( timeout_counter == 0 )
        {
            document.location = loc+( ( loc.indexOf( '?' ) == -1 ) ? '?' : '&' )+'timeout=1';
        }
        else
        {
            timeout_counter--;
            setTimeout( sell_downloads_counting, 1000 );
        }
    };

	if('fancybox' in $.fn)
	{
		$('.product-covers a').fancybox();
	}

    if( $( '[id="sell_downloads_error_mssg"]' ).length )
    {
        sell_downloads_counting();
    }
};

jQuery(codepeople_sell_downloads);
jQuery(window).on('load', codepeople_sell_downloads);