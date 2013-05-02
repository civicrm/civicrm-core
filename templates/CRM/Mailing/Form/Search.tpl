{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
<div class="crm-block crm-form-block crm-search-form-block">
<table class="form-layout">
    <tr>
        <td>{$form.mailing_name.label}<br />
            {$form.mailing_name.html|crmAddClass:big} {help id="id-mailing_name"}
        </td>
    </tr>
    <tr>
        <td>
	    <label>{if $sms eq 1}{ts}SMS Date{/ts}{else}{ts}Mailing Date{/ts}{/if}</label>
  </td>
    </tr>
    <tr>
  {include file="CRM/Core/DateRange.tpl" fieldName="mailing" from='_from' to='_to'}
    </tr>
    <tr>
        <td colspan="1">{$form.sort_name.label}<br />
            {$form.sort_name.html|crmAddClass:big} {help id="id-create_sort_name"}
        </td>
        <td width="100%"><label>{if $sms eq 1}{ts}SMS Status{/ts}{else}{ts}Mailing Status{/ts}{/if}</label><br />
        <div class="listing-box" style="width: auto; height: 60px">
            {foreach from=$form.mailing_status item="mailing_status_val"}
            <div class="{cycle values="odd-row,even-row"}">
                {$mailing_status_val.html}
            </div>
            {/foreach}
        </div><br />
        </td>
    </tr>

    {* campaign in mailing search *}
    {include file="CRM/Campaign/Form/addCampaignToComponent.tpl"
    campaignContext="componentSearch" campaignTrClass='' campaignTdClass=''}

    <tr>
        <td>{$form.buttons.html}</td><td colspan="2"></td>
    </tr>
</table>
</div>
