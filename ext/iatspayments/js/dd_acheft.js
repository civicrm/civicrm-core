/*jslint indent: 2 */
/*global CRM, ts */

function iatsACHEFTRefresh() {
  cj(function ($) {
    'use strict';
    if (0 < $('#iats-direct-debit-extra').length) {
      /* move my custom fields up where they belong */
      $('.direct_debit_info-section').prepend($('#iats-direct-debit-extra'));
    }
  });
}
