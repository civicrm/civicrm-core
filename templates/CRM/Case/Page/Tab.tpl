{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{if $notConfigured} {* Case types not present. Component is not configured for use. *}
    {include file="CRM/Case/Page/ConfigureError.tpl"}

{elseif $redirectToCaseAdmin}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>&nbsp;
         <strong>{ts}Oops, It looks like there are no active case types.{/ts}</strong>
           {if call_user_func(array('CRM_Core_Permission','check'), ' administer CiviCase')}
             {capture assign=adminCaseTypeURL}{crmURL p='civicrm/a/#/caseType'}
       {/capture}
             {ts 1=$adminCaseTypeURL 2=$adminCaseStatusURL}Enable <a href='%1'>case types</a>.{/ts}
           {/if}
    </div>

{else}

    {capture assign=newCaseURL}{crmURL p="civicrm/case/add" q="reset=1&action=add&cid=`$contactId`&context=case"}{/capture}

    {if $action eq 1 or $action eq 2 or $action eq 8 or $action eq 32768 } {* add, update, delete, restore*}
        {include file="CRM/Case/Form/Case.tpl"}
    {elseif $action eq 4 }
        {include file="CRM/Case/Form/CaseView.tpl"}

    {else}
    <div class="crm-block crm-content-block">
    <div class="view-content">
    <div class="help">
         {ts 1=$displayName}This page lists all case records for %1.{/ts}
         {if $permission EQ 'edit' and call_user_func(array('CRM_Core_Permission','check'), 'access all cases and activities') and $allowToAddNewCase}
         {ts 1="href='$newCaseURL' class='action-item'"}Click <a %1>Add Case</a> to add a case record for this contact.{/ts}{/if}
    </div>

    {if $action eq 16 and $permission EQ 'edit' and
        ( call_user_func(array('CRM_Core_Permission','check'), 'access all cases and activities') OR
          call_user_func(array('CRM_Core_Permission','check'), 'add cases') ) AND
        $allowToAddNewCase}
        <div class="action-link">
        <a accesskey="N" href="{$newCaseURL}" class="button"><span><i class="crm-i fa-plus-circle"></i> {ts}Add Case{/ts}</span></a>
        </div>
    {/if}

    {if $rows}
          {include file="CRM/Case/Form/Selector.tpl"}
    {else}
       <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
            {ts}There are no case records for this contact.{/ts}
          </div>
    {/if}
    </div>
    </div>
    {/if}
{/if}
