{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
    {if $formName == "Contribute_Preferences"}
      <table class = "form-layout">
        {foreach from=$htmlFields item=desc key=htmlField}
          {if $form.$htmlField}
	    {assign var=n value=$htmlField|cat:'_description'}
            <tr class="crm-preferences-form-block-{$htmlField}">
              {if $form.$htmlField.html_type EQ 'checkbox'|| $form.$htmlField.html_type EQ 'checkboxes'}
                <td class="label"></td>
                <td>
                  {$form.$htmlField.html} {$form.$htmlField.label}
                  {if $desc}
                    <br /><span class="description">{$desc}</span>
                  {/if}
                </td>
              {else}
                <td class="label">{$form.$htmlField.label}&nbsp;{if $htmlField eq 'acl_financial_type'}{help id="$htmlField"}{/if}</td>
                <td>
                  {if $htmlField eq 'prior_financial_period'}
                    {include file="CRM/common/jcalendar.tpl" elementName=$htmlField}
 		  {else}
                     {$form.$htmlField.html}
                  {/if}
                  {if $desc}
                    <br /><span class="description">{$desc}</span>
                  {/if}
                </td>
              {/if}
            </tr>
          {/if}
        {/foreach}
	{$form.prior_financial_period_M_hidden.html}
	{$form.prior_financial_period_d_hidden.html}
      </table>
    {/if}
    <table class="form-layout" id="invoicing_blocks">
        {foreach from=$fields item=field key=fieldName}
            {assign var=n value=$fieldName}
            {if $form.$n}
              <tr class="crm-preferences-form-block-{$fieldName}">
                    {if $field.html_type EQ 'checkbox'|| $field.html_type EQ 'checkboxes'}
                        <td class="label"></td>
                        <td>
                            {$form.$n.html} {$form.$n.label}
                            {if $field.description}
                                <br /><span class="description">{$field.description}</span>
                            {/if}
                        </td>
                    {else}
                        <td class="label">{$form.$n.label}</td>
                        <td>
                            {$form.$n.html}
                            {if $field.description}
                                <br /><span class="description">{$field.description}</span>
                            {/if}
                        </td>
                    {/if}
              </tr>
          {/if}
        {/foreach}
  </table>

    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{if $formName == "Contribute_Preferences"}
  {literal}
    <script type="text/javascript">
      cj(document).ready(function() {
        if (document.getElementById("invoicing").checked) {
          cj("#invoicing_blocks").show();
        }
        else {
          cj("#invoicing_blocks").hide();
        }
      });
      cj(function () {
        cj("input[type=checkbox]").click(function() {
          if (cj("#invoicing").is(":checked")) {
            cj("#invoicing_blocks").show();
          }
          else {
            cj("#invoicing_blocks").hide();
          }
        });
      });
    </script>
  {/literal}
{/if}
