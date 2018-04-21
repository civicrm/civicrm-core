/* custom js for the subscription form */

/*jslint indent: 2 */
/*global CRM, ts */

cj(function ($) {
  'use strict';
  var dupeSubscriptionFields = (typeof CRM.iatspayments != 'undefined') ? CRM.iatspayments.dupeSubscriptionFields : ((typeof CRM.vars != 'undefined') ? ((typeof CRM.vars.iatspayments != 'undefined') ? CRM.vars.iatspayments.dupeSubscriptionFields : []) : []); 
  
  if (0 < dupeSubscriptionFields.length) {
    $.each(dupeSubscriptionFields, function(index, value) {
      $('#contributionrecur-extra tr.'+value).remove();
    });
  }
  $('.crm-recurcontrib-form-block table').append($('#contributionrecur-extra tr'));
  $('.crm-recurcontrib-form-block table').prepend($('#contributionrecur-info tr'));
});
