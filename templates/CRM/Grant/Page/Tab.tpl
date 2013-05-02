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
{if $action eq 1 or $action eq 2 or $action eq 8 }{* add, update or delete *}
    {include file="CRM/Grant/Form/Grant.tpl"}
{elseif $action eq 4 }
    {include file="CRM/Grant/Form/GrantView.tpl"}
{else}
    <div class="view-content">
     {if $permission EQ 'edit'}
        {capture assign=newGrantURL}{crmURL p="civicrm/contact/view/grant" q="reset=1&action=add&cid=`$contactId`&context=grant"}{/capture}
    {/if}

    <div id="help">
        <p>{ts 1=$displayName}This page lists all grants for %1 since inception.{/ts} 
        {if $permission EQ 'edit'}
            {ts 1=$newGrantURL}Click <a accesskey='N' href='%1'>Add Grant</a> to record a Grant for this contact.{/ts}
        {/if}
        </p>
    </div>
{if $action eq 16 and $permission EQ 'edit'}
            <div class="action-link">
            <a href="{$newGrantURL}" class="button"><span><div class="icon add-icon"></div>{ts}Add Grant{/ts}</span></a><br/><br/>
            </div>
        {/if}
    {if $rows}
        
        {include file="CRM/Grant/Form/Selector.tpl"}
    {else}
        <div class="messages status">
             <div class="icon inform-icon"></div>&nbsp;
             {ts}No grants have been recorded for this contact.{/ts}
       </div>
    {/if}
    </div>
{/if}
