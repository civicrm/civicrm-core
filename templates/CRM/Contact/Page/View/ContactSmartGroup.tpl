{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="section-shown">
  {if !$groupSmart AND !$groupParent}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      &nbsp;{ts}This contact does not currently belong to any smart groups.{/ts}
    </div>
  {/if}

  {if $groupSmart}
    <div class="ht-one"></div>
    <div class="description">
      {ts 1=$displayName}%1 is currently included in these Smart group(s) (e.g. saved searches).{/ts}
    </div>
    {strip}
      <table id="smart_group" class="display">
        <thead>
        <tr>
          <th>{ts}Group{/ts}</th>
          <th>{ts}Description{/ts}</th>
        </tr>
        </thead>
        {foreach from=$groupSmart item=row}
          <tr id="grp_{$row.id}" class="{cycle values="odd-row,even-row"}">
            <td class="bold">
              <a href="{crmURL p='civicrm/group/search' q="reset=1&force=1&context=smog&gid=`$row.id`"}">
                {$row.title}
              </a>
            </td>
            <td>{$row.description}</td>
          </tr>
        {/foreach}
      </table>
    {/strip}
  {/if}
  {if $groupParent}
    <div class="ht-one"></div>
    <h3>{ts}Parent Groups{/ts}</h3>
    <div class="description">
      {ts 1=$displayName}%1 is included in these Parent group(s) based on belonging to group(s) which are their
        children.{/ts}
    </div>
    {strip}
      <table id="parent_group" class="display">
        <thead>
        <tr>
          <th>{ts}Group{/ts}</th>
        </tr>
        </thead>
        {foreach from=$groupParent item=row}
          <tr id="grp_{$row.id}" class="{cycle values="odd-row,even-row"}">
            <td class="bold">
              <a href="{crmURL p='civicrm/group/search' q="reset=1&force=1&context=smog&gid=`$row.id`"}">
                {$row.title}
              </a>
            </td>
          </tr>
        {/foreach}
      </table>
    {/strip}
  {/if}
</div>
