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
        if (this.checked) {
          $('.crm-setting-section-invoice tr').show();
        } else {
          $('.crm-setting-section-invoice tr').not($(this).parents()).hide();
          $('#invoice_is_email_pdf_invoice_is_email_pdf').prop('checked', false);
        }
      }

      $("#invoicing_invoicing").each(toggleInvoiceBlocks).click(toggleInvoiceBlocks);
    });
  </script>
{/literal}
