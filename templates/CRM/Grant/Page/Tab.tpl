{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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

    <div class="help">
        <p>{ts 1=$displayName}This page lists all grants for %1 since inception.{/ts}
        {if $permission EQ 'edit'}
          {capture assign=link}accesskey='N' href='{$newGrantURL}' class='action-item'{/capture}
            {ts 1=$link}Click <a %1>Add Grant</a> to record a Grant for this contact.{/ts}
        {/if}
        </p>
    </div>
{if $action eq 16 and $permission EQ 'edit'}
            <div class="action-link">
            <a href="{$newGrantURL}" class="button"><span><i class="crm-i fa-plus-circle"></i> {ts}Add Grant{/ts}</span></a><br/><br/>
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
