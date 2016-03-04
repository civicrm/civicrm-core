/*
 * jQuery plugin: fieldSelection - v0.1.0 - last change: 2006-12-16
 * (c) 2006 Alex Brem <alex@0xab.cd> - http://blog.0xab.cd
 *
 */

(function() {

	var fieldSelection = {

		getSelection: function() {

			var e = this.jquery ? this[0] : this;

			return (

				/* mozilla / dom 3.0 */
				('selectionStart' in e && function() {
					var l = e.selectionEnd - e.selectionStart;
					return { start: e.selectionStart, end: e.selectionEnd, length: l, text: e.value.substr(e.selectionStart, l) };
				}) ||

				/* exploder */
				(document.selection && function() {

					e.focus();

					var r = document.selection.createRange();
					if (r == null) {
						return { start: 0, end: e.value.length, length: 0 }
					}

					var re = e.createTextRange();
					var rc = re.duplicate();
					re.moveToBookmark(r.getBookmark());
					rc.setEndPoint('EndToStart', re);

					return { start: rc.text.length, end: rc.text.length + r.text.length, length: r.text.length, text: r.text };
				}) ||

				/* browser not supported */
				function() {
					return { start: 0, end: e.value.length, length: 0 };
				}

			)();

		},

		replaceSelection: function() {

			var e = this.jquery ? this[0] : this;
			var text = arguments[0] || '';

			return (

				/* mozilla / dom 3.0 */
				('selectionStart' in e && function() {
                    var cursorlength = e.selectionStart + text.length;
                    e.value = e.value.substr(0, e.selectionStart) + text + e.value.substr(e.selectionEnd, e.value.length);
					e.selectionStart = e.selectionEnd = cursorlength;
                    return this;
				}) ||

				/* exploder */
				(document.selection && function() {
                    // get the current cursor position
                    // currently IE 8 does not support methods to get cursor start position
                    // replace below code with the equivalent once method is available 
                    var startPosition = e.value.length;
                    var endPosition = startPosition + text.length;
		    
                    // set the value
                    e.value = e.value.substr(0, startPosition) + text + e.value.substr( endPosition, e.value.length);
                    
                    // move the focus to correct position, end of inserted token 
					e.focus();
                    var range = e.createTextRange(); 
                    range.move( "character", endPosition ); 
                    range.select(); 
					return this;
				}) ||

				/* browser not supported */
				function() {
					e.value += text;
					return this;
				}

			)();

		}
	};

	cj.each(fieldSelection, function(i) { cj.fn[i] = this; });

})();
