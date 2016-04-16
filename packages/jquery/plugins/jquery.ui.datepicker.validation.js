/* Datepicker Validation 1.0.1 for jQuery UI Datepicker 1.8.6.
   Requires Jörn Zaefferer's Validation plugin (http://plugins.jquery.com/project/validate).
   Written by Keith Wood (kbwood{at}iinet.com.au).
   Dual licensed under the GPL (http://dev.jquery.com/browser/trunk/jquery/GPL-LICENSE.txt) and 
   MIT (http://dev.jquery.com/browser/trunk/jquery/MIT-LICENSE.txt) licenses. 
   Please attribute the author if you use it. */

(function($) { // Hide the namespace

/* Add validation methods if validation plugin available. */
if ($.fn.validate) {

	$.datepicker._selectDate2 = $.datepicker._selectDate;
	
	$.extend($.datepicker.regional[''], {
		validateDate: 'Please enter a valid date',
		validateDateMin: 'Please enter a date on or after {0}',
		validateDateMax: 'Please enter a date on or before {0}',
		validateDateMinMax: 'Please enter a date between {0} and {1}',
		validateDateCompare: 'Please enter a date {0} {1}',
		validateDateToday: 'today',
		validateDateOther: 'the other date',
		validateDateEQ: 'equal to',
		validateDateNE: 'not equal to',
		validateDateLT: 'before',
		validateDateGT: 'after',
		validateDateLE: 'not after',
		validateDateGE: 'not before'
	});
	
	$.extend($.datepicker._defaults, $.datepicker.regional['']);

	$.extend($.datepicker, {

		/* Trigger a validation after updating the input field with the selected date.
		   @param  id       (string) the ID of the target field
		   @param  dateStr  (string) the chosen date */
		_selectDate: function(id, dateStr) {
			this._selectDate2(id, dateStr);
			var input = $(id);
			var inst = this._getInst(input[0]);
			if (!inst.inline && $.fn.validate)
				input.parents('form').validate().element(input);
		},

		/* Correct error placement for validation errors - after (before if R-T-L) any trigger.
		   @param  error    (jQuery) the error message
		   @param  element  (jQuery) the field in error */
		errorPlacement: function(error, element) {
			var trigger = element.next('.' + $.datepicker._triggerClass);
			var before = false;
			if (trigger.length == 0) {
				trigger = element.prev('.' + $.datepicker._triggerClass);
				before = (trigger.length > 0);
			}
			error[before ? 'insertBefore' : 'insertAfter'](trigger.length > 0 ? trigger : element);
		},

		/* Format a validation error message involving dates.
		   @param  message  (string) the error message
		   @param  params  (Date[]) the dates
		   @return  (string) the formatted message */
		errorFormat: function(inst, message, params) {
			var format = $.datepicker._get(inst, 'dateFormat');
			$.each(params, function(i, v) {
				message = message.replace(new RegExp('\\{' + i + '\\}', 'g'),
					$.datepicker.formatDate(format, v) || 'nothing');
			});
			return message;
		}
	});

	var lastElement = null;

	/* Validate date field. */
	$.validator.addMethod('dpDate', function(value, element, params) {
			lastElement = element;
			var inst = $.datepicker._getInst(element);
			var dateFormat = $.datepicker._get(inst, 'dateFormat');
			try {
				var date = $.datepicker.parseDate(dateFormat, value, $.datepicker._getFormatConfig(inst));
				var minDate = $.datepicker._determineDate(inst, $.datepicker._get(inst, 'minDate'), null);
				var maxDate = $.datepicker._determineDate(inst, $.datepicker._get(inst, 'maxDate'), null);
				var beforeShowDay = $.datepicker._get(inst, 'beforeShowDay');
				return this.optional(element) || !date || 
					((!minDate || date >= minDate) && (!maxDate || date <= maxDate) &&
					(!beforeShowDay || beforeShowDay.apply(element, [date])[0]));
			}
			catch (e) {
				return false;
			}
		}, function(params) {
			var inst = $.datepicker._getInst(lastElement);
			var minDate = $.datepicker._determineDate(inst, $.datepicker._get(inst, 'minDate'), null);
			var maxDate = $.datepicker._determineDate(inst, $.datepicker._get(inst, 'maxDate'), null);
			var messages = $.datepicker._defaults;
			return (minDate && maxDate ?
				$.datepicker.errorFormat(inst, messages.validateDateMinMax, [minDate, maxDate]) :
				(minDate ? $.datepicker.errorFormat(inst, messages.validateDateMin, [minDate]) :
				(maxDate ? $.datepicker.errorFormat(inst, messages.validateDateMax, [maxDate]) :
				messages.validateDate)));
		});

	/* And allow as a class rule. */
	$.validator.addClassRules('dpDate', {dpDate: true});

	var comparisons = {equal: 'eq', same: 'eq', notEqual: 'ne', notSame: 'ne',
		lessThan: 'lt', before: 'lt', greaterThan: 'gt', after: 'gt',
		notLessThan: 'ge', notBefore: 'ge', notGreaterThan: 'le', notAfter: 'le'};

	/* Cross-validate date fields.
	   params should be an array with [0] comparison type eq/ne/lt/gt/le/ge or synonyms,
	   [1] 'today' or date string or Date or other field selector/element/jQuery OR
	   an object with one attribute with name eq/ne/lt/gt/le/ge or synonyms
	   and value 'today' or date string or Date or other field selector/element/jQuery OR
	   a string with eq/ne/lt/gt/le/ge or synonyms followed by 'today' or date string or jQuery selector */
	$.validator.addMethod('dpCompareDate', function(value, element, params) {
			if (this.optional(element)) {
				return true;
			}
			params = normaliseParams(params);
			var thisDate = $(element).datepicker('getDate');
			var thatDate = extractOtherDate(element, params[1]);
			if (!thisDate || !thatDate) {
				return true;
			}
			lastElement = element;
			var result = true;
			switch (comparisons[params[0]] || params[0]) {
				case 'eq': result = (thisDate.getTime() == thatDate.getTime()); break;
				case 'ne': result = (thisDate.getTime() != thatDate.getTime()); break;
				case 'lt': result = (thisDate.getTime() < thatDate.getTime()); break;
				case 'gt': result = (thisDate.getTime() > thatDate.getTime()); break;
				case 'le': result = (thisDate.getTime() <= thatDate.getTime()); break;
				case 'ge': result = (thisDate.getTime() >= thatDate.getTime()); break;
				default:   result = true;
			}
			return result;
		},
		function(params) {
			var inst = $.datepicker._getInst(lastElement);
			var messages = $.datepicker._defaults;
			params = normaliseParams(params);
			var thatDate = extractOtherDate(lastElement, params[1], true);
			thatDate = (params[1] == 'today' ? messages.validateDateToday : (thatDate ?
				$.datepicker.formatDate($.datepicker._get(inst, 'dateFormat'), thatDate,
				$.datepicker._getFormatConfig(inst)) : messages.validateDateOther));
			return messages.validateDateCompare.replace(/\{0\}/,
				messages['validateDate' + (comparisons[params[0]] || params[0]).toUpperCase()]).
				replace(/\{1\}/, thatDate);
		});

	/* Normalise the comparison parameters to an array.
	   @param  params  (array or object or string) the original parameters
	   @return  (array) the normalised parameters */
	function normaliseParams(params) {
		if (typeof params == 'string') {
			params = params.split(' ');
		}
		else if (!$.isArray(params)) {
			var opts = [];
			for (var name in params) {
				opts[0] = name;
				opts[1] = params[name];
			}
			params = opts;
		}
		return params;
	}

	/* Determine the comparison date.
	   @param  element  (element) the current datepicker element
	   @param  source   (string or Date or jQuery or element) the source of the other date
	   @param  noOther  (boolean) true to not get the date from another field
	   @return  (Date) the date for comparison */
	function extractOtherDate(element, source, noOther) {
		if (source.constructor == Date) {
			return source;
		}
		var inst = $.datepicker._getInst(element);
		var thatDate = null;
		try {
			if (typeof source == 'string' && source != 'today') {
				thatDate = $.datepicker.parseDate($.datepicker._get(inst, 'dateFormat'),
					source, $.datepicker._getFormatConfig(inst));
			}
		}
		catch (e) {
			// Ignore
		}
		thatDate = (thatDate ? thatDate : (source == 'today' ? new Date() :
			(noOther ? null : $(source).datepicker('getDate'))));
		if (thatDate) {
			thatDate.setHours(0, 0, 0, 0);
		}
		return thatDate;
	}
}

})(jQuery);
