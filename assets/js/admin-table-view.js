/**
 * @see /style.css
 */
jQuery(document).ready(function ($) {
	const courseInfoCell = $('td.column-course_info');
	const courseInfoText = courseInfoCell.text();
	if( courseInfoText ) {
		if( courseInfoText.length > 5) {
			courseInfoCell.css('cursor', 'pointer');
			courseInfoCell.prop('title', 'Click to show');
			courseInfoCell.click( (e) => {
				const el = $(e.target);
				let state = el.css('white-space');
				if( 'nowrap' === state ) { 
					courseInfoCell.prop('title', 'Click to hide');
					el.css('white-space', 'unset');
				} else {
					courseInfoCell.prop('title', 'Click to show');
					el.css('white-space', 'nowrap');
				}
		});
		}
	}
	// hide arrow for duplicates
	$('td.column-name').html(function (i, t) {
		return t.replace('&nbsp;&nbsp;←', '')
	});
	if(table_view && table_view.post_status_isset == 0){
		$('#posts-filter').find('input[name="post_status"]').val('open_for_enrollment')
	}

});
