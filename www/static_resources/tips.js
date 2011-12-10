/**
 * @provides tips
 * @requires jquery global
 */
/*
 * build and assign tips to the page. its using each method and not live method, so we have to run this method each time page gets loaded.
 */
function liveTips ()
{
// Define corners opposites
	var tipsPositionOpposites = {
		'bottomLeft': 'topRight', 
		'bottomRight': 'topLeft', 
		'bottomMiddle': 'topMiddle',
		'topRight': 'bottomLeft', 
		'topLeft': 'bottomRight', 
		'topMiddle': 'bottomMiddle',
		'leftMiddle': 'rightMiddle', 
		'leftTop': 'rightBottom', 
		'leftBottom': 'rightTop',
		'rightMiddle': 'leftMiddle', 
		'rightBottom': 'leftTop', 
		'rightTop': 'leftBottom'
	};
	
	// start assinging tips to the containers
	$('.tips').each(function()
	{
		var tooltip = ($(this).attr('tips-position') == null) ? tipsPositionOpposites.topMiddle : tipsPositionOpposites[$(this).attr('tips-position')];
		var target = ($(this).attr('tips-position') == null) ? 'topMiddle' : $(this).attr('tips-position');
		
		// dynamic arrow sizes
		if (tooltip == 'topRight' || tooltip == 'topLeft' || tooltip == 'topMiddle' || tooltip == 'bottomLeft' || tooltip == 'bottomRight' || tooltip == 'bottomMiddle')
		{
			var sizeX = 16;
			var sizeY = 8;
		}
		else
		{
			var sizeX = 8;
			var sizeY = 16;
		}

		$(this).qtip(
		{
			position: {
				corner: {
					tooltip: tooltip,
					target: target
				}
			},	
 			style: {
				name: 'plain',
				tip: {
					size: {
						x: sizeX,
						y: sizeY
					}
				}
			}
		});
	});
		

	$('.tipsBig').each(function()
	{
		var element = $(this);
		var tooltip = ($(this).attr('tips-position') == null) ? tipsPositionOpposites.leftMiddle : tipsPositionOpposites[$(this).attr('tips-position')];
		var target = ($(this).attr('tips-position') == null) ? 'leftMiddle' : $(this).attr('tips-position');
		
		// dynamic arrow sizes
		if (tooltip == 'topRight' || tooltip == 'topLeft' || tooltip == 'topMiddle' || tooltip == 'bottomLeft' || tooltip == 'bottomRight' || tooltip == 'bottomMiddle')
		{
			var sizeX = 16;
			var sizeY = 8;
		}
		else
		{
			var sizeX = 8;
			var sizeY = 16;
			
			// auto adjust little to right or to left
			if (tooltip == 'rightMiddle' || tooltip == 'rightBottom' || tooltip == 'rightTop')
			{
				var adjustX = -10; // move left on right arrow
			}
			else
			{
				var adjustX = 10; // move right on left arrow
			}
		}
		
		$(this).qtip(
		{
			content: $('#tipsBigDemo')
						.clone()
						.removeClass('hide') // unHide
						.find('.close') // find close button
						.bind('click', function() { // bind close button
							$(element).qtip('hide');  // put trigger on close button
						})
						.parents('#tipsBigDemo'), // go back to parent div and use that for content
			position: {
				corner: {
					tooltip: tooltip,
					target: target
				},
				adjust: { x: adjustX, y: 0}
			},	
 			style: {
				name: 'help',
				tip: {
					size: {
						x: sizeX,
						y: sizeY
					}
				}
			},
			
			show: {
			  when: false, // Don't specify a show event
			  ready: true // Show the tooltip when ready
			},
			hide: false // Don't specify a hide event			
		});
	});	
}