/* custom js for public selection of future recurring start dates */
/* only show option when recurring is selected */
/*jslint indent: 2 */
/*global CRM, ts */

function iatsRecurStartRefresh() {
  cj(function ($) {
    'use strict';
     $('.is_recur-section').after($('#iats-recurring-start-date'));
     cj('input[id="is_recur"]').on('change', function() {
       toggleRecur();
     });
     toggleRecur();

     function toggleRecur( ) {
       var isRecur = cj('input[id="is_recur"]:checked');
       if (isRecur.val() > 0) {
         cj('#iats-recurring-start-date').show().val('');
       }
       else {
         cj('#iats-recurring-start-date').hide();
         $("#iats-recurring-start-date option:selected").prop("selected", false);
         $("#iats-recurring-start-date option:first").prop("selected", "selected");
       }
     }
  });
}
