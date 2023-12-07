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
  {if $pageViewType eq 'customDataView'}
     {assign var='dialogId' value='custom-record-dialog'}
  {else}
     {assign var='dialogId' value='profile-dialog'}
  {/if}
  {if ($records and $headers) or ($pageViewType eq 'customDataView')}
    {include file="CRM/common/jsortable.tpl"}
    <div id="custom-{$customGroupId}-table-wrapper" {if $pageViewType eq 'customDataView'}class="crm-entity" data-entity="contact" data-id="{$contactId}"{/if}>
      <div>
        {strip}
          <table id="records-{$customGroupId}" class={if $pageViewType eq 'customDataView'}"crm-multifield-selector crm-ajax-table"{else}'display'{/if}>
            <thead>
            {if $pageViewType eq 'customDataView'}
              {foreach from=$headers key=recId item=head}
                <th data-data={ts}'{$headerAttr.$recId.columnName}'{/ts}
                {if !empty($headerAttr.$recId.dataType)}cell-data-type="{$headerAttr.$recId.dataType}"{/if}
                {if !empty($headerAttr.$recId.dataEmptyOption)}cell-data-empty-option="{$headerAttr.$recId.dataEmptyOption}"{/if}>{ts}{$head}{/ts}
                </th>
              {/foreach}
              <th data-data="action" data-orderable="false">&nbsp;</th>
            </thead>
              {literal}
              <script type="text/javascript">
                (function($) {
                  var ZeroRecordText = {/literal}"{ts escape='js' 1=$customGroupTitle|smarty:nodefaults}No records of type '%1' found.{/ts}"{literal};
                  var $table = $('#records-' + {/literal}'{$customGroupId}'{literal});
                  $('table.crm-multifield-selector').data({
                    "ajax": {
                      "url": {/literal}'{crmURL p="civicrm/ajax/multirecordfieldlist" h=0 q="snippet=4&cid=$contactId&cgid=$customGroupId"}'{literal},
                    },
                    "language": {
                      "emptyTable": ZeroRecordText,
                    },
                    //Add class attributes to cells
                    "rowCallback": function(row, data) {
                      $('thead th', $table).each(function(index) {
                        var fName = $(this).attr('data-data');
                        var cell = $('td:eq(' + index + ')', row);
                        if (typeof data[fName] == 'object') {
                          if (typeof data[fName].data != 'undefined') {
                            $(cell).html(data[fName].data);
                          }
                          if (typeof data[fName].cellClass != 'undefined') {
                            $(cell).attr('class', data[fName].cellClass);
                          }
                        }
                      });
                    }
                  })
                })(CRM.$);
              </script>
              {/literal}

            {else}
              {foreach from=$headers key=recId item=head}
                <th>{ts}{$head}{/ts}</th>
              {/foreach}

              {foreach from=$dateFields key=fieldId item=v}
                <th class='hiddenElement'></th>
              {/foreach}
              <th>&nbsp;</th>
              </thead>
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
            {/if}
          </table>
        {/strip}
      </div>
    </div>
    <div id='{$dialogId}' class="hiddenElement"></div>
  {elseif !$records}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      &nbsp;
      {ts 1=$customGroupTitle}No records of type '%1' found.{/ts}
    </div>
    <div id='{$dialogId}' class="hiddenElement"></div>
  {/if}

  {if empty($reachedMax) && !empty($editPermission)}
    <div class="action-link">
      {if $pageViewType eq 'customDataView'}
        <br/><a accesskey="N" title="{ts 1=$customGroupTitle}Add %1 Record{/ts}" href="{crmURL p='civicrm/contact/view/cd/edit' q="reset=1&type=$ctype&groupID=$customGroupId&entityID=$contactId&cgcount=$newCgCount&multiRecordDisplay=single&mode=add"}"
         class="button action-item"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts 1=$customGroupTitle}Add %1 Record{/ts}</span></a>
      {else}
        <a accesskey="N" href="{crmURL p='civicrm/profile/edit' q="reset=1&id=`$contactId`&multiRecord=add&gid=`$gid`&context=multiProfileDialog"}"
         class="button action-item"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}Add New Record{/ts}</span></a>
      {/if}
    </div>
    <br />
  {/if}
{/if}
