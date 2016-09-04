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
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/MailSettings.tpl"}
{else}

{if $rows}
<div id="mSettings">
  <p></p>
  <div class="form-item">
    {strip}
      <table cellpadding="0" cellspacing="0" border="0">
        <thead class="sticky">
            <th>{ts}Name{/ts}</th>
            <th>{ts}Server{/ts}</th>
            <th>{ts}Username{/ts}</th>
            <th>{ts}Localpart{/ts}</th>
            <th>{ts}Domain{/ts}</th>
            <th>{ts}Return-Path{/ts}</th>
            <th>{ts}Protocol{/ts}</th>
            <th>{ts}Source{/ts}</th>
            <!--<th>{ts}Port{/ts}</th>-->
            <th>{ts}Use SSL?{/ts}</th>
            <th>{ts}Used For{/ts}</th>
            <th></th>
        </thead>
        {foreach from=$rows item=row}
          <tr id='rowid{$row.id}' class="crm-mailSettings {cycle values="odd-row,even-row"}">
              <td class="crm-mailSettings-name">{$row.name}</td>
              <td class="crm-mailSettings-server">{$row.server}</td>
              <td class="crm-mailSettings-username">{$row.username}</td>
              <td class="crm-mailSettings-localpart">{$row.localpart}</td>
              <td class="crm-mailSettings-domain">{$row.domain}</td>
              <td class="crm-mailSettings-return_path">{$row.return_path}</td>
              <td class="crm-mailSettings-protocol">{$row.protocol}</td>
              <td class="crm-mailSettings-source">{$row.source}</td>
              <!--<td>{$row.port}</td>-->
              <td class="crm-mailSettings-is_ssl">{if $row.is_ssl eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
              <td class="crm-mailSettings-is_default">{if $row.is_default eq 1}{ts}Bounce Processing <strong>(Default)</strong>{/ts}{else}{ts}Email-to-Activity{/ts}{/if}&nbsp;</td>
              <td>{$row.action|replace:'xx':$row.id}</td>
          </tr>
        {/foreach}
      </table>
    {/strip}

  </div>
</div>
{else}
    <div class="messages status no-popup">
      <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
      {ts}None found.{/ts}
    </div>
{/if}
  <div class="action-link">
    {crmButton q="action=add&reset=1" id="newMailSettings"  icon="plus-circle"}{ts}Add Mail Account{/ts}{/crmButton}
    {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
  </div>
{/if}
