{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
    <div id="custom-{$customGroupId}-table-wrapper">
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
                    <td class='crm-field-{$fid}_date hiddenElement'>{$rec.$recId}</td>
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
      <br/><a accesskey="N" title="{ts 1=$customGroupTitle}Add %1 Record{/ts}" href="{crmURL p='civicrm/contact/view/cd/edit' q="reset=1&type=$ctype&groupID=$customGroupId&entityID=$contactId&cgcount=$cgcount&multiRecordDisplay=single&mode=add"}" 
       class="button action-item"><span><div class="icon add-icon"></div>{ts 1=$customGroupTitle}Add %1 Record{/ts}</span></a>
    {else}
      <a accesskey="N" href="{crmURL p='civicrm/profile/edit' q="reset=1&id=`$contactId`&multiRecord=add&gid=`$gid`&context=multiProfileDialog&onPopupClose=`$onPopupClose`"}"
       class="button action-item"><span><div class="icon add-icon"></div>{ts}Add New Record{/ts}</span></a>
    {/if}
  {/if}
{/if}
