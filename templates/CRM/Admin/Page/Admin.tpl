{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Displays Administer CiviCRM Control Panel *}
<div class="crm-content-block">
{foreach from=$adminPanel key=groupName item=group}
<div id="admin-section-{$groupName}">
  <h3>{$group.title}</h3>
  <div class="admin-section-items">
    {foreach from=$group.fields item=panelItem  key=panelName}
    <dl>
      <dt><a href="{$panelItem.url}"{if $panelItem.extra} {$panelItem.extra}{/if} id="id_{$panelItem.id}">{$panelItem.title}</a></dt>
      <dd>{$panelItem.desc}</dd>
    </dl>
    {/foreach}
  </div>
</div>
{/foreach}
</div>
