{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-contribution-search_contribution-form-block">
<h3>{ts}Find Contribution Pages{/ts}</h3>
<table class="form-layout-compressed">
    <tr>
        <td>{$form.title.html}</td>
        <td>
            <label>{ts}Financial Type{/ts}</label>
            <div class="listing-box">
                {foreach from=$form.financial_type_id item="contribution_val"}
                <div class="{cycle values="odd-row,even-row"}">
                     {$contribution_val.html}
                  </div>
                {/foreach}
            </div>
        </td>
    </tr>

    {* campaign in contribution page search *}
    {include file="CRM/Campaign/Form/addCampaignToSearch.tpl"
    campaignTrClass='' campaignTdClass=''}

 </table>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>
</div>
