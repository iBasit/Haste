/*
 * @provides global
 * @requires tips
 */
// JavaScript Document
jQuery(function($) {
	//cache nav
	var nav = $("#topNav");
	
	//add indicator and hovers to submenu parents
	nav.find("li").each(function() {
		if ($(this).find("ul").length > 0) 
		{
			$("<span>").html("&nabla;").appendTo($(this).children(":first"));
			
			if ($(this).hasClass('active'))
			{
				$(this).find('a:first').addClass('active');
				$(this).find("ul").addClass('active');
			}
			
			//show subnav on hover
			$(this).find('a:first').click(function() 
			{
				if ($(this).parent().hasClass('active'))
				{
					nav.find('.active').removeClass('active');
					$(this).parent().removeClass('active'); // li
					$(this).removeClass('active'); // link
					$(this).next('ul').removeClass('active'); // ul
				}
				else
				{
					nav.find('.active').removeClass('active');
					$(this).parent().addClass('active'); // li
					$(this).addClass('active'); // link
					$(this).next('ul').addClass('active'); // ul
				}
				
				return false;
			});
		}
	});
	
	// comment section
	$('.addCommentText')
		.live('focus', function(event) 
		{
			$(this).parents('.addComment').addClass('active');
		})
		.live('focusout', function(event) 
		{
			if ($(this).attr('title') == $(this).val() || !$(this).val())
				$(this).parents('.addComment').removeClass('active');
		});
		
	// global tips on the site
	// define styles for the tip
	$.fn.qtip.styles.plain = { 
		color: '#fff',
		textAlign: 'center',
		border: {
			color: '#505050'
		},
		tip: {
			corner: true,
			size: {
				x: 16,
				y: 8
			}
		},
		name: 'dark' // Inherit the rest of the attributes from the preset dark style
	}
	$.fn.qtip.styles.help = { 
		border: {
			color: '#505050',
			width: 3,
			radius: 6
		},
		tip: {
			corner: true,
			size: {
				x: 8,
				y: 16
			}
		},
		width: {max: 300}
	}
	
	globalRun();
});

/*
 * This method holds all the methods, that we need to run each time the page is been loaded from ajax.
 */
function globalRun ()
{
	liveTips(); // create tips.
}