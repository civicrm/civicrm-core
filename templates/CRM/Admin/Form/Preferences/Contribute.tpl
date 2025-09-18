{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm--block">
  {include file="CRM/Form/basicFormFields.tpl"}

  <table class="form-layout" id="invoicing_blocks">
    {foreach from=$invoiceDependentFields item=fieldSpec key=htmlField}
      {if $form.$htmlField}
        {assign var=n value=$htmlField|cat:'_description'}
        <tr class="crm-preferences-form-block-{$htmlField}">
          {if $fieldSpec.html_type EQ 'checkbox'|| $fieldSpec.html_type EQ 'checkboxes'}
            <td class="label"></td>
            <td>
              {$form.$htmlField.html} {$form.$htmlField.label}
              {if $fieldSpec.description}
                <br /><span class="description">{$fieldSpec.description}</span>
              {/if}
            </td>
          {else}
            <td class="label">{$form.$htmlField.label}&nbsp;{if $htmlField eq 'acl_financial_type'}{help id="$htmlField" title=$form.$htmlField.textLabel}{/if}</td>
            <td>
              {$form.$htmlField.html}
              {if $fieldSpec.description}
                <br /><span class="description">{$fieldSpec.description}</span>
              {/if}
            </td>
          {/if}
        </tr>
      {/if}
    {/foreach}
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{literal}
  <script type="text/javascript">
    cj(document).ready(function() {
      if (document.getElementById("invoicing_invoicing").checked) {
        cj("#invoicing_blocks").show();
      }
      else {
        cj("#invoicing_blocks").hide();
      }
    });
    cj(function () {
      cj("input[type=checkbox]").click(function() {
        if (cj("#invoicing_invoicing").is(":checked")) {
          cj("#invoicing_blocks").show();
        }
        else {
          cj("#invoicing_blocks").hide();
          cj('#invoice_is_email_pdf_invoice_is_email_pdf').prop('checked', false);
        }
      });
    });
  </script>
{/literal}
