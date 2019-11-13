{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for the dropdown menu of the "Actions" button on contacts. *}

<div id="crm-contact-actions-wrapper" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Page_Inline_Actions"{rdelim}'>
  {crmButton id="crm-contact-actions-link" href="#" icon="bars"}
    {ts}Actions{/ts}
  {/crmButton}
    {crmRegion name="contact-page-inline-actions"}
      <div class="ac_results" id="crm-contact-actions-list">
        <div class="crm-contact-actions-list-inner">
          <div class="crm-contact_activities-list">
          {include file="CRM/Activity/Form/ActivityLinks.tpl" as_select=false}
          </div>
          <div class="crm-contact_print-list">
            <ul class="contact-print">
              {foreach from=$actionsMenuList.otherActions item='row'}
                {if !empty($row.href) or !empty($row.tab)}
                <li class="crm-contact-{$row.ref}">
                  <a href="{if !empty($row.href)}{$row.href}{if strstr($row.href, '?')}&cid={$contactId}{/if}{else}#{/if}" title="{$row.title|escape}" data-tab="{$row.tab}" {if !empty($row.class)}class="{$row.class}"{/if}>
                    <span><i {if !empty($row.icon)}class="{$row.icon}"{/if}></i> {$row.title}</span>
                  </a>
                </li>
                {/if}
              {/foreach}
          </ul>
          </div>
          <div class="crm-contact_actions-list">
          <ul class="contact-actions">
            {foreach from=$actionsMenuList.moreActions item='row'}
            {if !empty($row.href) or !empty($row.tab)}
            <li class="crm-action-{$row.ref}">
              <a href="{if !empty($row.href)}{$row.href}{if strstr($row.href, '?')}&cid={$contactId}{/if}{else}#{/if}" title="{$row.title|escape}" data-tab="{$row.tab}" {if !empty($row.class)}class="{$row.class}"{/if}>{$row.title}</a>
            </li>
            {/if}
          {/foreach}
                </ul>
                </div>


          <div class="clear"></div>
        </div>
      </div>
    {/crmRegion}
  </div>
{literal}
{/literal}
