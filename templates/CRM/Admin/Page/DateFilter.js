CRM.$(function($) {
  'use strict';
  //Pull the values for calculating the relative dates from the Form.
  var relative = $('#relative_terms').val();
  var units = $('#units').val();
  var params = {relativeTerms: relative,
                Units: units};
  $.getJSON(CRM.url('civicrm/ajax/previewRelativeDateFilter', params), function(data) {
    $('#relative-date-preview-from').text(data.fromDate);
    $('#relative-date-preview-to').text(data.toDate);
  });
});
