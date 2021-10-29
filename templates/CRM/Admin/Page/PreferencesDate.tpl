{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 1 or $action eq 2 or $action eq 8}
    {include file="CRM/Admin/Form/PreferencesDate.tpl"}
{else}
    <div class="help">
      {capture assign=crmURL}{crmURL p='civicrm/admin/setting/date' q='action=reset=1'}{/capture}
        {ts 1=$crmURL}Changing the parameters here affects the input and display for specific fields types. Setting the default date format for the entire site is a Localisation setting. See <a href="%1">Administer > Localization > Date Formats</a>{/ts}
    </div>
    <div class="form-item">
        <table cellpadding="0" cellspacing="0" border="0">
            <tr class="columnheader">
                <th >{ts}Date Class{/ts}</th>
                <th >{ts}Description{/ts}</th>
                <th >{ts}Date Format{/ts}</th>
                <th >{ts}Start Offset{/ts}</th>
                <th >{ts}End Offset{/ts}</th>
                <th ></th>
            </tr>
            {foreach from=$rows item=row}
            <tr class="{cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}">
                <td>{$row.name}</td>
                <td>{$row.description}</td>
                <td class="nowrap">{if !$row.date_format}{ts}Default{/ts}{else}{$row.date_format}{/if}</td>
                <td align="right">{$row.start}</td>
                <td align="right">{$row.end}</td>
                <td><span>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</span></td>
            </tr>
            {/foreach}
        </table>
    </div>
{/if}
