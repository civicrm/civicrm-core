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
<div class="crm-block crm-form-block crm-contribution-search_contribution-form-block">
<h3>{ts}Find Contribution Pages{/ts}</h3>
<table class="form-layout-compressed">
    <tr>
        <td>{$form.title.html}
            <div class="description font-italic">
                {ts}Complete OR partial Contribution Page title.{/ts}
            </div>
            <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
        </td>

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
    {include file="CRM/Campaign/Form/addCampaignToComponent.tpl"
    campaignContext="componentSearch" campaignTrClass='' campaignTdClass=''}

 </table>
</div>
