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
});
