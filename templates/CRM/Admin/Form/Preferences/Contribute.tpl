{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
  {docURL page=""}
</div>
{include file="CRM/Admin/Form/Generic.tpl"}

{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      function toggleInvoiceBlocks() {
        if ($('#invoicing').prop('checked')) {
          $('.crm-setting-section-invoice tr').show();
        } else {
          $('.crm-setting-section-invoice tr').hide();
          // but don't hide the invoice checkbox itself
          $('tr.crm-setting-form-block-invoicing').show();
          $('#invoice_is_email_pdf').prop('checked', false);
        }
      }

      $('#invoicing').click(toggleInvoiceBlocks);
      toggleInvoiceBlocks();
    });
  </script>
{/literal}
