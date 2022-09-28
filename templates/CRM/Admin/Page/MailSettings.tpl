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
   {include file="CRM/Admin/Form/MailSettings.tpl"}
{else}

<div class="crm-block crm-content-block">
{if $rows}
<div id="mSettings">
  <div class="form-item">
    {strip}
      <table cellpadding="0" cellspacing="0" border="0" class="row-highlight">
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
              <td class="crm-mailSettings-server">{if !empty($row.server)}{$row.server}{/if}</td>
              <td class="crm-mailSettings-username">{if !empty($row.username)}{$row.username}{/if}</td>
              <td class="crm-mailSettings-localpart">{if !empty($row.localpart)}{$row.localpart}{/if}</td>
              <td class="crm-mailSettings-domain">{if !empty($row.domain)}{$row.domain}{/if}</td>
              <td class="crm-mailSettings-return_path">{if !empty($row.return_path)}{$row.return_path}{/if}</td>
              <td class="crm-mailSettings-protocol">{if !empty($row.protocol)}{$row.protocol}{/if}</td>
              <td class="crm-mailSettings-source">{if !empty($row.source)}{$row.source}{/if}</td>
              <!--<td>{if !empty($row.port)}{$row.port}{/if}</td>-->
              <td class="crm-mailSettings-is_ssl">{if isset($row.is_ssl) and $row.is_ssl eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
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
    {if !empty($setupActions)}
        <form>
            <select id="crm-mail-setup" name="crm-mail-setup" class="crm-select2 crm-form-select" aria-label="{ts}Add Mail Account{/ts}">
                <option value="" aria-hidden="true">{ts}Add Mail Account{/ts}</option>
                {foreach from=$setupActions key=setupActionsName item=setupAction}
                    <option value="{$setupActionsName|escape}">{$setupAction.title|escape}</option>
                {/foreach}
            </select>
        </form>
    {else}
        <div class="action-link">
            {crmButton q="action=add&reset=1" id="newMailSettings"  icon="plus-circle"}{ts}Add Mail Account{/ts}{/crmButton}
            {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
        </div>
    {/if}

{/if}
</div>
{literal}
    <script type="text/javascript">
        cj('#crm-mail-setup').val('');
        cj('#crm-mail-setup').on('select2-selecting', function(event) {
            if (!event.val) {
                return;
            }
            event.stopPropagation();
            var url = CRM.url('civicrm/ajax/setupMailAccount', {type: event.val});
            window.location = url;
        });
    </script>
{/literal}
