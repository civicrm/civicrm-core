{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      function showHideOperator() {
        var val = $(this).val();
        $(this).siblings("span.crm-multivalue-search-op").toggle(!!(val && val.length > 1));
      }
      $("span.crm-multivalue-search-op").siblings('select')
        .off('.crmMultiValue')
        .on('change.crmMultiValue', showHideOperator)
        .each(showHideOperator);
    });
  </script>
{/literal}
