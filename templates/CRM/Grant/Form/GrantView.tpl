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
{* this template is used for viewing grants *}
<div class="crm-block crm-content-block crm-grant-view-block">
    <div class="crm-submit-buttons">
        {if call_user_func(array('CRM_Core_Permission','check'), 'edit grants')}
            {assign var='urlParams' value="reset=1&id=$id&cid=$contactId&action=update&context=$context"}
            {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
                {assign var='urlParams' value="reset=1&id=$id&cid=$contactId&action=update&context=$context&key=$searchKey"}
            {/if}
            <a class="button" href="{crmURL p='civicrm/contact/view/grant' q=$urlParams}" accesskey="e"><span><i class="crm-i fa-pencil"></i> {ts}Edit{/ts}</span></a>
        {/if}
        {if call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviGrant')}
          {assign var='urlParams' value="reset=1&id=$id&cid=$contactId&action=delete&context=$context"}
            {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
                {assign var='urlParams' value="reset=1&id=$id&cid=$contactId&action=delete&context=$context&key=$searchKey"}
            {/if}
            <a class="button" href="{crmURL p='civicrm/contact/view/grant' q=$urlParams}"><span><i class="crm-i fa-trash"></i> {ts}Delete{/ts}</span></a>
        {/if}
        {include file="CRM/common/formButtons.tpl" location="top"}
    </div>
    <table class="crm-info-panel">
        <tr class="crm-grant-view-form-block-name"><td class="label">{ts}Name{/ts}</td><td class="bold">{$displayName}</td></tr>
        <tr class="crm-grant-view-form-block-status_id"><td class="label">{ts}Grant Status{/ts}</td> <td>{$grantStatus}</td></tr>
        <tr class="crm-grant-view-form-block-grant_type_id"><td class="label">{ts}Grant Type{/ts}</td> <td>{$grantType}</td></tr>
        <tr class="crm-grant-view-form-block-application_received_date"><td class="label">{ts}Application Received{/ts}</td> <td>{$application_received_date|crmDate}</td></tr>
        <tr class="crm-grant-view-form-block-decision_date"><td class="label">{ts}Grant Decision{/ts}</td> <td>{$decision_date|crmDate}</td></tr>
        <tr class="crm-grant-view-form-block-money_transfer_date"><td class="label">{ts}Money Transferred{/ts}</td> <td>{$money_transfer_date|crmDate}</td></tr>
        <tr class="crm-grant-view-form-block-grant_due_date"><td class="label">{ts}Grant Report Due{/ts}</td> <td>{$grant_due_date|crmDate}</td></tr>
        <tr class="crm-grant-view-form-block-amount_total"><td class="label">{ts}Amount Requested{/ts}</td> <td>{$amount_total|crmMoney}</td></tr>
        <tr class="crm-grant-view-form-block-amount_requested"><td class="label">{ts}Amount Requested{/ts}<br />
                              {ts}(original currency){/ts}   </td> <td>{$amount_requested|crmMoney}</td></tr>
        <tr class="crm-grant-view-form-block-amount_granted"><td class="label">{ts}Amount Granted{/ts}</td> <td>{$amount_granted|crmMoney}</td></tr>
        <tr class="crm-grant-view-form-block-grant_report_received"><td class="label">{ts}Grant Report Received?{/ts}</td> <td>{if $grant_report_received}{ts}Yes{/ts} {else}{ts}No{/ts}{/if}</td></tr>
        <tr class="crm-grant-view-form-block-rationale"><td class="label">{ts}Rationale{/ts}</td> <td>{$rationale|nl2br}</td></tr>
        <tr class="crm-grant-view-form-block-note"><td class="label">{ts}Notes{/ts}</td> <td>{$note|nl2br}</td></tr>
        {if $attachment}
            <tr class="crm-grant-view-form-block-attachment"><td class="label">{ts}Attachment(s){/ts}</td><td>{$attachment}</td></tr>
        {/if}
    </table>
    {include file="CRM/Custom/Page/CustomDataView.tpl"}
    <div class="crm-submit-buttons">
        {if call_user_func(array('CRM_Core_Permission','check'), 'edit grants')}
            {assign var='urlParams' value="reset=1&id=$id&cid=$contactId&action=update&context=$context"}
          {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
              {assign var='urlParams' value="reset=1&id=$id&cid=$contactId&action=update&context=$context&key=$searchKey"}
          {/if}
            <a class="button" href="{crmURL p='civicrm/contact/view/grant' q=$urlParams}" accesskey="e"><span><i class="crm-i fa-pencil"></i> {ts}Edit{/ts}</span></a>
        {/if}
        {if call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviGrant')}
        {assign var='urlParams' value="reset=1&id=$id&cid=$contactId&action=delete&context=$context"}
          {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
              {assign var='urlParams' value="reset=1&id=$id&cid=$contactId&action=delete&context=$context&key=$searchKey"}
          {/if}
            <a class="button" href="{crmURL p='civicrm/contact/view/grant' q=$urlParams}"><span><i class="crm-i fa-trash"></i> {ts}Delete{/ts}</span></a>
        {/if}
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>
