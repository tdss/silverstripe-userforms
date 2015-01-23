jQuery(function($) {
	/**
	 * Make sure the form does not expire on the user.
	 */
	setInterval(function() {
		// Ping every 3 mins.
		$.ajax({url: "UserFormsPingController/ping"});
	}, 180*1000);

	$(".udf_add_record").click(function(e) {
		e.preventDefault();

		var container = $(this).parents(".udf_nested").first(),
			items = container.find('.udf_nested_nest');
			nested = items.first(),
			copy = nested.clone(),
			count = items.length + 1;

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

		$(':input', copy)
 			.not(':button, :submit, :reset, :hidden')
 			.val('')
 			.removeAttr('checked')
 			.removeAttr('selected');


		if(count % 2 == 0) {
			copy.addClass('even');
		}

		items.last().after(copy);
	});
});
