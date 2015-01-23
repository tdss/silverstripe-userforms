jQuery(function($) {
	/**
	 * Make sure the form does not expire on the user.
	 */
	setInterval(function() {
		// Ping every 3 mins.
		$.ajax({url: "UserFormsPingController/ping"});
	}, 180*1000);

	$(".udf_add_record").each(function(i, elem) {
		$(elem).data('indexpos', $(this).parents(".udf_nested").first().find('.udf_nested_nest').length);
	});

	$(".udf_add_record").click(function(e) {
		e.preventDefault();

		var container = $(this).parents(".udf_nested").first(),
			items = container.find('.udf_nested_nest');
			nested = items.first(),
			copy = nested.clone(),
			count = $(".udf_add_record").data('indexpos') + 1;

		$(".udf_add_record").data('indexpos', count);

		copy.find('input, textarea, select, label').each(function(i, elem) {
			// replace the name and id
			if($(elem).attr('name')) {
				$(elem).attr('name', $(elem).attr('name').replace("[1]", "["+ count + "]"));
			}

			if($(elem).attr('id')) {
				$(elem).attr('id', $(elem).attr('id').replace("_1_", "_"+ count + "_"));
			}

			if($(elem).attr('for')) {
				$(elem).attr('for', $(elem).attr('for').replace("_1_", "_"+ count + "_"));
			}
		});

		copy.append($("<div class='udf_nested_delete' style='cursor: pointer'>&#10006;</div>"));

		var clear = copy.find(':input');

 		clear.not(':button, :submit, :reset').each(function(i, elem) {
 			clear.val('')
 				.removeAttr('checked')
 				.removeAttr('selected')
 				.prop('checked', false);
 		});
 
		if(count % 2 == 0) {
			copy.addClass('even');
		}

		items.last().after(copy);
	});

	$('body').on('click', '.udf_nested_delete', function() {
		$(this).parents('.udf_nested_nest').remove();

		// recalculate the even and odd classes
		var count = 1;

		$(this).parents('.udf_nested').find('.udf_nested_nest').each(function(i, elem) {
			if(count % 2 == 0) {
				$(elem).addClass('even');
			} else {
				$(elem).removeClass('even');
			}

			count++;
		});
	})
});
