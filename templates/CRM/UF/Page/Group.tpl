{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 1 or $action eq 2 or $action eq 4 or $action eq 8 or $action eq 64 or $action eq 16384}
  {* Add or edit Profile Group form *}
  {include file="CRM/UF/Form/Group.tpl"}
{elseif $action eq 1024}
  {* Preview Profile Group form *}
  {include file="CRM/UF/Form/Preview.tpl"}
{elseif $action eq 8192}
  {* Display HTML Form Snippet Code *}
  <div class="help">
      {ts}The HTML code below will display a form consisting of the active fields in this Profile. You can copy this HTML code and paste it into any block or page on your website where you want to collect contact information.{/ts} {help id='standalone'}
  </div>
  <br />
  <form name="html_code" action="{crmURL p='civicrm/admin/uf/group' q="action=profile&gid=$gid"}">
    <div id="standalone-form">
      <textarea rows="20" cols="80" name="profile" id="profile">{$profile}</textarea>
      <div class="spacer"></div>
      <a href="#" onclick="html_code.profile.select(); return false;" class="button"><span>{ts}Select HTML Code{/ts}</span></a>
    </div>
    <div class="action-link">
      &nbsp; <a href="{crmURL p='civicrm/admin/uf/group' q="reset=1"}"><i class="crm-i fa-chevron-left" role="img" aria-hidden="true"></i>  {ts}Back to Profile Listings{/ts}</a>
    </div>
  </form>

{else}
  <div class="help">
    {ts}Profiles allow you to aggregate groups of fields and include them in your site as input forms, contact display pages, and search and listings features. They provide a powerful set of tools for you to collect information from constituents and selectively share contact information.{/ts} {help id='profile_overview'}
  </div>

  <div class="crm-content-block crm-block">
    {if NOT ($action eq 1 or $action eq 2)}
      <div class="crm-submit-buttons">
          <a href="{crmURL p='civicrm/admin/uf/group/add' q="action=add&reset=1"}" id="newCiviCRMProfile-top" class="button"><span><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}Add Profile{/ts}</span></a>
      </div>
    {/if}
    {if $rows}
      <div id='mainTabContainer'>
        <ul role="tablist">
          <li id='tab_user-profiles' role="tab">
            <a href='#user-profiles' title='{ts escape='htmlattribute'}User-defined Profile{/ts}'>{ts}User-defined Profiles{/ts}</a>
          </li>
          <li id='tab_reserved-profiles' role="tab">
            <a href='#reserved-profiles' title='{ts escape='htmlattribute'}Reserved Profiles{/ts}'>{ts}Reserved Profiles{/ts}</a>
          </li>
        </ul>

        {* handle enable/disable actions*}
        {include file="CRM/common/enableDisableApi.tpl"}
        {include file="CRM/common/jsortable.tpl"}
        <div id="user-profiles" role="tabpanel">
          <div class="crm-content-block">
            <table class="display">
              <thead>
                <tr>
                  <th id="sortable">{ts}Profile Title{/ts}</th>
                  <th>{ts}Public Title{/ts}</th>
                  <th>{ts}Created By{/ts}</th>
                  <th>{ts}Description{/ts}</th>
                  <th>{ts}Type{/ts}</th>
                  <th>{ts}ID{/ts}</th>
                  <th id="nosort">{ts}Exposed To{/ts}</th>
                  <th><span class="sr-only">{ts}Actions{/ts}</span></th>
                </tr>
              </thead>
              <tbody>
                {foreach from=$rows item=row}
                {if !$row.is_reserved}
                  <tr id="UFGroup-{$row.id}" data-action="setvalue" class="crm-entity {$row.class}{if NOT $row.is_active} disabled{/if}">
                    <td class="crmf-title crm-editable">{$row.title}</td>
                    <td class="crmf-frontend_title crm-editable">{$row.frontend_title}</td>
                    <td>
                      {if $row.created_id && $row.created_by}
                        <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.created_id`"}">{ts}{$row.created_by}{/ts}</a>
                      {/if}
                    </td>
                    <td class="crmf-description crm-editable" data-type="textarea">{$row.description|escape}</td>
                    <td>{$row.group_type}</td>
                    <td>{$row.id}</td>
                    <td>{$row.module}</td>
                    <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
                  </tr>
                {/if}
                {/foreach}
              </tbody>
            </table>
          </div>
        </div>{* user profile*}

        <div id="reserved-profiles" role="tabpanel">
          <div class="crm-content-block">
            <table class="display">
              <thead>
                <tr>
                  <th id="sortable">{ts}Profile Title{/ts}</th>
                  <th>{ts}Public Title{/ts}</th>
                  <th>{ts}Created By{/ts}</th>
                  <th>{ts}Description{/ts}</th>
                  <th>{ts}Type{/ts}</th>
                  <th>{ts}ID{/ts}</th>
                  <th id="nosort">{ts}Exposed To{/ts}</th>
                  <th><span class="sr-only">{ts}Actions{/ts}</span></th>
                </tr>
              </thead>
              <tbody>
                {foreach from=$rows item=row}
                  {if $row.is_reserved}
                    <tr id="UFGroup-{$row.id}" class="crm-entity{if !empty($row.class)} {$row.class}{/if}{if NOT $row.is_active} disabled{/if}">
                      <td>{$row.title}</td>
                      <td>{$row.frontend_title}</td>
                      <td>
                        {if $row.created_id && $row.created_by}
                          <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.created_id`"}">{ts}{$row.created_by}{/ts}</a>
                        {/if}
                      </td>
                      <td>{$row.description|escape}</td>
                      <td>{$row.group_type}</td>
                      <td>{$row.id}</td>
                      <td>{$row.module}</td>
                      <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
                    </tr>
                  {/if}
                {/foreach}
              </tbody>
            </table>
          </div>
        </div>{* reserved profile*}
      </div>
      {include file="CRM/common/TabHeader.tpl"}
    {else}
      {if $action ne 1} {* When we are adding an item, we should not display this message *}
        <div class="messages status no-popup">
          {icon icon="fa-info-circle"}{/icon}
          {capture assign=crmURL}{crmURL p='civicrm/admin/uf/group/add' q='action=add&reset=1'}{/capture}{ts 1=$crmURL}No CiviCRM Profiles have been created yet. You can <a href='%1'>add one now</a>.{/ts}
        </div>
      {/if}
    {/if}
{/if}
