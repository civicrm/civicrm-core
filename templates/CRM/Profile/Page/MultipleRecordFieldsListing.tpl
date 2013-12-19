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
  <h1>{ts}{$customGroupTitle}{/ts}</h1>
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
            </tr>
            </thead>
            {foreach from=$records key=recId item=rows}
              <tr class="{cycle values="odd-row,even-row"}">
                {foreach from=$headers key=hrecId item=head}
                  <td>{$rows.$hrecId}</td>
                {/foreach}
                <td>{$rows.action}</td>
              </tr>
            {/foreach}
          </table>
        {/strip}
      </div>
    </div>
    <div id='profile-dialog' class="hiddenElement"></div>
  {elseif !$records}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      &nbsp;
      {ts 1=$customGroupTitle}No records of type '%1' found.{/ts}
    </div>
    <div id='profile-dialog' class="hiddenElement"></div>
  {/if}

  {if !$reachedMax}
    <a accesskey="N" href="{crmURL p='civicrm/profile/edit' q="reset=1&id=`$contactId`&multiRecord=add&gid=`$gid`&snippet=1&context=multiProfileDialog&onPopupClose=`$onPopupClose`"}"
       class="button action-item"><span><div class="icon add-icon"></div>{ts}Add New Record{/ts}</span></a>
  {/if}
{/if}
{literal}
  <script type='text/javascript'>
    cj(function () {
      // NOTE: Triggers two events, "profile-dialog:FOO:open" and "profile-dialog:FOO:close",
      // where "FOO" is the internal name of a profile form
      function formDialog(dialogName, dataURL, dialogTitle) {
        cj.ajax({
          url: dataURL,
          success: function (content) {
            cj('#profile-dialog').show().html(content).dialog({
              title: dialogTitle,
              modal: true,
              width: 680,
              overlay: {
                opacity: 0.5,
                background: "black"
              },
              open: function(event, ui) {
                cj('#profile-dialog').trigger({
                  type: "crmFormLoad",
                  profileName: dialogName
                });
              },
              close: function (event, ui) {
                cj('#profile-dialog').trigger({
                  type: "crmFormClose",
                  profileName: dialogName
                });
                cj('#profile-dialog').html('');
              }
            });
            cj('.action-link').hide();
            cj('#profile-dialog #crm-profile-block .edit-value label').css('display', 'inline');
          }
        });
      }

      var profileName = {/literal}"{$ufGroupName}"{literal};
      cj('.action-item').each(function () {
        if (!cj(this).attr('jshref')) {
          cj(this).attr('jshref', cj(this).attr('href'));
          cj(this).attr('href', '#browseValues');
        }
      });

      cj(".crm-profile-name-" + profileName + " .action-item").click(function () {
        dataURL = cj(this).attr('jshref');
        dialogTitle = cj(this).attr('title');
        formDialog(profileName, dataURL, dialogTitle);
      });
    });
    </script>
  {/literal}
