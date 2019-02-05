{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-{$formName}-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  {include file="CRM/Form/basicFormFields.tpl"}

  <table class="form-layout" id="invoicing_blocks">
    {foreach from=$htmlFields item=fieldSpec key=htmlField}
      {if $form.$htmlField}
        {assign var=n value=$htmlField|cat:'_description'}
        <tr class="crm-preferences-form-block-{$htmlField}">
          {if $fieldSpec.html_type EQ 'checkbox'|| $fieldSpec.html_type EQ 'checkboxes'}
            <td class="label"></td>
            <td>
              {$form.$htmlField.html} {$form.$htmlField.label}
              {if $desc}
                <br /><span class="description">{$fieldSpec.description}</span>
              {/if}
            </td>
          {else}
            <td class="label">{$form.$htmlField.label}&nbsp;{if $htmlField eq 'acl_financial_type'}{help id="$htmlField"}{/if}</td>
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
        }
      });
    });
  </script>
{/literal}
