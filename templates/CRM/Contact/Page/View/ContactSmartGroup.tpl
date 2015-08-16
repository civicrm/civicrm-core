{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
<div class="section-shown">
  {if !$groupSmart AND !$groupParent}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
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
