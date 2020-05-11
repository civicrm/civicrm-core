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
{if $newer_civicrm_version}
    <div class="messages status no-popup">
      <table>
        <tr><td class="tasklist">
          {ts 1=$registerSite}Have you registered this site at CiviCRM.org? If not, please help strengthen the CiviCRM ecosystem by taking a few minutes to <a href="%1" target="_blank">fill out the site registration form</a>. The information collected will help us prioritize improvements, target our communications and build the community. If you have a technical role for this site, be sure to check "Keep in Touch" to receive technical updates (a low volume mailing list).{/ts}</td>
        </tr>
      </table>
    </div>
{/if}

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
