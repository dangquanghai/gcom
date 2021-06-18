$(function() {
	var $wrapper = $('#wrapper');


	// display scripts on the page
	$('script', $wrapper).each(function() {
		var code = this.text;
		if (code && code.length) {
			var lines = code.split('\n');
			var indent = null;

			for (var i = 0; i < lines.length; i++) {
				if (/^[	 ]*$/.test(lines[i])) continue;
				if (!indent) {
					var lineindent = lines[i].match(/^([ 	]+)/);
					if (!lineindent) break;
					indent = lineindent[1];
				}
				lines[i] = lines[i].replace(new RegExp('^' + indent), '');
			}

			var code = $.trim(lines.join('\n')).replace(/	/g, '    ');
			var $pre = $('<pre>').addClass('js').text(code);
			$pre.insertAfter(this);
		}
	});

	
});

function LoadList(Element, ds) 
{
	var $select = $(Element).selectize({
		maxItems: null,
		valueField: 'id',
		labelField: 'name',
		searchField: 'name',
		options: ds,
		plugins: ['remove_button'],
		create: false
	});
}


