{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $showListing}
  {if $dontShowTitle neq 1}<h1>{ts}{$customGroupTitle}{/ts}</h1>{/if}
  {if $records and $headers}
    {include file="CRM/common/jsortable.tpl"}
    <div id="custom-{$gid}-table-wrapper">
      <div>
        {strip}
          <table class="display">
            <thead>
              {foreach from=$headers key=recId item=head}
                <th>{ts}{$head}{/ts}</th>
              {/foreach}

              {foreach from=$dateFields key=fieldId item=v}
                <th class='hiddenElement'></th>
              {/foreach}
              <th>&nbsp;</th>
            </thead>
            <tbody>
              {foreach from=$records key=recId item=rows}
                <tr class="{cycle values="odd-row,even-row"}">
                  {foreach from=$headers key=hrecId item=head}
                    <td {if !empty($dateFieldsVals.$hrecId)}data-order="{$dateFieldsVals.$hrecId.$recId|crmDate:'%Y-%m-%d'}"{/if} {crmAttributes a=$attributes.$hrecId.$recId}>{$rows.$hrecId}</td>
                  {/foreach}
                  <td>{$rows.action}</td>
                  {foreach from=$dateFieldsVals key=fid item=rec}
                    <td class='crm-field-{$fid}_date hiddenElement'>{$rec.$recId}</td>
                  {/foreach}
                </tr>
              {/foreach}
            </tbody>
          </table>
        {/strip}
      </div>
    </div>
    <div id="profile-dialog" class="hiddenElement"></div>
  {elseif !$records}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      &nbsp;
      {ts 1=$customGroupTitle}No records of type '%1' found.{/ts}
    </div>
    <div id="profile-dialog" class="hiddenElement"></div>
  {/if}

  {if empty($reachedMax) && !empty($editPermission)}
    <div class="action-link">
      <a accesskey="N" href="{crmURL p='civicrm/profile/edit' q="reset=1&id=`$contactId`&multiRecord=add&gid=`$gid`&context=multiProfileDialog"}"
       class="button action-item"><span><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}Add New Record{/ts}</span></a>
    </div>
    <br />
  {/if}
{/if}
