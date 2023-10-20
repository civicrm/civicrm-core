{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="crm-block crm-content-block">
{if $rows}
<div id="mSettings">
  <div class="form-item">
    {strip}
    {include file="CRM/common/enableDisableApi.tpl"}
      <table cellpadding="0" cellspacing="0" border="0" class="selector row-highlight">
        <thead class="sticky">
            <th>{ts}Name{/ts}</th>
            <th>{ts}Server{/ts}</th>
            <th>{ts}Username{/ts}</th>
            <th>{ts}Localpart{/ts}</th>
            <th>{ts}Domain{/ts}</th>
            <th>{ts}Return-Path{/ts}</th>
            <th>{ts}Protocol{/ts}</th>
            <th>{ts}Mail Folder{/ts}</th>
            <!--<th>{ts}Port{/ts}</th>-->
            <th>{ts}Use SSL?{/ts}</th>
            <th>{ts}Used For{/ts}</th>
            <th></th>
        </thead>
        {foreach from=$rows item=row}
          <tr id='mail_settings-{$row.id}' class="crm-entity {cycle values="odd-row,even-row"} {if NOT $row.is_active} disabled{/if}">
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
              <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
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
                    <option data-url="{$setupAction.url|escape}" value="{$setupActionsName|escape}">{$setupAction.title|escape}</option>
                {/foreach}
            </select>
        </form>
    {else}
        <div class="action-link">
            {crmButton p="civicrm/admin/mailSettings/edit" q="action=add&reset=1" id="newMailSettings"  icon="plus-circle"}{ts}Add Mail Account{/ts}{/crmButton}
            {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
        </div>
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
            window.location = cj(event.choice.element).data('url');
        });
    </script>
{/literal}
