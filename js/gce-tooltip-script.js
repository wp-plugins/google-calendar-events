function gce_simple_tooltip(target_items){
	if(jQuery('#gce-tooltip').html() == null){
		//Appends a div, which will be used as the tooltip
		jQuery('body').append('<div id="gce-tooltip"></div>');
	};

	jQuery(target_items).each(function(){
		//Reference to div appended above
		var my_tooltip = jQuery('#gce-tooltip');

		//On mouse over
		jQuery(this).mouseover(function(){
			//Sets the tooltip to the correct information for the day (hidden in <td>)
			my_tooltip.html(jQuery(this).children('div').html());
			//Shows the tooltip
			my_tooltip.show();
		//On mouse move
		}).mousemove(function(kmouse){
			//All this stuff prevents tooltip going out of viewport
			var border_top = jQuery(window).scrollTop();
			var border_right = jQuery(window).width();
			var left_pos;
			var top_pos;
			var offset = 5; //Offset from mouse pointer

			if(border_right - (offset * 2) >= my_tooltip.width() + kmouse.pageX){
				left_pos = kmouse.pageX + offset;
			}else{
				left_pos = border_right - my_tooltip.width() - offset;
			}

			if(border_top + (offset * 2) >= kmouse.pageY - my_tooltip.height()){
				top_pos = border_top + offset;
			}else{
				top_pos = kmouse.pageY - my_tooltip.height() - offset;
			}

			my_tooltip.css({left:left_pos, top:top_pos});
		//On mouse out
		}).mouseout(function(){
			//Hide the tooltip
			my_tooltip.hide();
		});
	});
}

function gce_ajaxify(target, feed_id, type){
	//Add click event to change month links
	jQuery('#' + target + ' .gce-change-month').click(function(){
		//Extract month and year
		var month_year = jQuery(this).attr('name').split('-', 2);
		//Add loading text to table caption
		jQuery('#' + target + ' caption').html('Loading...');
		//Send AJAX request
		jQuery.get('index.php', {gce_type:type, gce_feed_id:feed_id, gce_widget_id:target, gce_month:month_year[0], gce_year:month_year[1]}, function(data){
			//Replace existing data with returned AJAX data
			if(type == 'widget'){
				jQuery('#' + target).children('.gce-widget-grid').html(data);
				gce_simple_tooltip('.widget_gce_widget .gce-has-events');
			}else{
				jQuery('#' + target).replaceWith(data);
				gce_simple_tooltip('.gce-page-grid .gce-has-events');
			}
		});
	});
}

jQuery(document).ready(function(){
	gce_simple_tooltip('.widget_gce_widget .gce-has-events');
	gce_simple_tooltip('.gce-page-grid .gce-has-events');
});