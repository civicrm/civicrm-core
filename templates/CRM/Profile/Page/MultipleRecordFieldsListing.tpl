{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{if $showListing}
  {if $dontShowTitle neq 1}<h1>{ts}{$customGroupTitle}{/ts}</h1>{/if}
  {if $pageViewType eq 'customDataView'}
     {assign var='dialogId' value='custom-record-dialog'}
  {else}
     {assign var='dialogId' value='profile-dialog'}
  {/if}
  {if $records and $headers}
    {include file="CRM/common/jsortable.tpl"}
    <div id="browseValues">
      <div>
        {strip}
          <table id="records" class="display">
            <thead>
            <tr>
              {foreach from=$headers key=recId item=head}
                <th>{ts}{$head}{/ts}</th>
              {/foreach}
              <th></th>
              {foreach from=$dateFields key=fieldId item=v}
                <th class='hiddenElement'></th>
              {/foreach}
            </tr>
            </thead>
            {foreach from=$records key=recId item=rows}
              <tr class="{cycle values="odd-row,even-row"}">
                {foreach from=$headers key=hrecId item=head}
                  {if $dateFieldsVals.$hrecId.$recId}
                    <td>{$rows.$hrecId|crmDate:"%b %d, %Y %l:%M %P"}</td>
                  {else}
                    <td>{$rows.$hrecId}</td>
                  {/if}
                {/foreach}
                <td>{$rows.action}</td>
                {foreach from=$dateFieldsVals key=fid item=rec}
                  {if $rec.$recId}
                    <td class='crm-field-{$fid}_date hiddenElement'>{$rec.$recId}</td>
                  {/if}
                {/foreach}
              </tr>
            {/foreach}
          </table>
        {/strip}
      </div>
    </div>
    <div id='{$dialogId}' class="hiddenElement"></div>
  {elseif !$records}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      &nbsp;
      {ts 1=$customGroupTitle}No records of type '%1' found.{/ts}
    </div>
    <div id='{$dialogId}' class="hiddenElement"></div>
  {/if}

  {if !$reachedMax}
    {if $pageViewType eq 'customDataView'}
      <br/><a accesskey="N" title="{ts 1=$customGroupTitle}Add %1 Record{/ts}" href="{crmURL p='civicrm/contact/view/cd/edit' q="reset=1&snippet=1&type=$ctype&groupID=$customGroupId&entityID=$contactId&cgcount=$cgcount&multiRecordDisplay=single"}" 
       class="button action-item"><span><div class="icon add-icon"></div>{ts 1=$customGroupTitle}Add %1 Record{/ts}</span></a>
    {else}
      <a accesskey="N" href="{crmURL p='civicrm/profile/edit' q="reset=1&id=`$contactId`&multiRecord=add&gid=`$gid`&snippet=1&context=multiProfileDialog&onPopupClose=`$onPopupClose`"}"
       class="button action-item"><span><div class="icon add-icon"></div>{ts}Add New Record{/ts}</span></a>
    {/if}
  {/if}
{/if}
{literal}
  <script type='text/javascript'>
    cj(function () {
      var dialogId = '{/literal}{$dialogId}{literal}';
      var pageViewType = '{/literal}{$pageViewType}{literal}';
      // NOTE: Triggers two events, "profile-dialog:FOO:open" and "profile-dialog:FOO:close",
      // where "FOO" is the internal name of a profile form
      function formDialog(dialogName, dataURL, dialogTitle) {
        cj.ajax({
          url: dataURL,
          success: function (content) {
            cj('#' + dialogId).show().html(content).dialog({
              title: dialogTitle,
              modal: true,
              width: 750,
              overlay: {
                opacity: 0.5,
                background: "black"
              },
              open: function(event, ui) {
                cj('#' + dialogId).trigger({
                  type: "crmFormLoad",
                  profileName: dialogName
                });
              },
              close: function (event, ui) {
                cj('#' + dialogId).trigger({
                  type: "crmFormClose",
                  profileName: dialogName
                });
                cj('#' + dialogId).html('');
              }
            });
            cj('.action-link').hide();
            if (pageViewType == 'customDataView') {
              var labelElement = cj('#custom-record-dialog .html-adjust label').css('display', 'inline');
            }
            else {
              var labelElement = cj('#profile-dialog #crm-profile-block .edit-value label').css('display', 'inline');
            }
          }
        });
      }

      var profileName = {/literal}"{$ufGroupName}"{literal};
      cj('.action-item').each(function () {
        if (!cj(this).attr('jshref') && !cj(this).hasClass('ignore-jshref')) {
          cj(this).attr('jshref', cj(this).attr('href'));
          cj(this).attr('href', '#browseValues');
        }
      });

      if (pageViewType == 'customDataView') {
        var actionItemHeirarchy = '.action-item';
        profileName = 'customRecordView';
      }
      else {
        var actionItemHeirarchy = '.crm-profile-name-' + profileName + ' .action-item';
      }

      cj(actionItemHeirarchy).click(function () {
        dataURL = cj(this).attr('jshref');
        dialogTitle = cj(this).attr('title');
        if (!cj(this).hasClass('ignore-jshref')) {
          formDialog(profileName, dataURL, dialogTitle);
        }
      });
    });
    </script>
  {/literal}
