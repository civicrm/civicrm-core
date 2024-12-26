{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign=crmURL}{crmURL p='civicrm/admin/messageTemplates/add' q="action=add&reset=1"}{/capture}
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/MessageTemplates.tpl"}

{elseif $action eq 4}
  {* View a system default workflow template *}

  <div class="help">
    {ts}You are viewing the default message template for this system workflow.{/ts} {help id="id-view_system_default"}
  </div>

  <fieldset>
  <div class="crm-section msg_title-section">
    <div class="bold">{$form.msg_title.value}</div>
  </div>
  <div class="crm-section msg_subject-section">
  <h3 class="header-dark">{$form.msg_subject.label}</h3>
    <div class="text">
      <textarea name="msg-subject" id="msg_subject" style="height: 6em; width: 45em;">{$form.msg_subject.value}</textarea>
      <div class='spacer'></div>
      <div class="section">
        <a href='#' onclick='MessageTemplates.msg_subject.select(); return false;' class='button'><span>{ts}Select Subject{/ts}</span></a>
        <div class='spacer'></div>
      </div>
    </div>
  </div>

  <div class="crm-section msg_html-section">
    <h3 class="header-dark">{$form.msg_html.label}</h3>
    <div class='text'>
      <textarea class="huge" name='msg_html' id='msg_html'>{$form.msg_html.value|escape:'htmlall'}</textarea>
      <div class='spacer'></div>
      <div class="section">
        <a href='#' onclick='MessageTemplates.msg_html.select(); return false;' class='button'><span>{ts}Select HTML Message{/ts}</span></a>
        <div class='spacer'></div>
      </div>
    </div>
  </div>

  <div class="crm-section msg_txt-section">
  <h3 class="header-dark">{$form.msg_text.label}</h3>
    <div class="text">
      <textarea class="huge" name='msg_text' id='msg_text'>{$form.msg_text.value|escape:'htmlall'}</textarea>
      <div class='spacer'></div>
      <div class="section">
        <a href='#' onclick='MessageTemplates.msg_text.select(); return false;' class='button'><span>{ts}Select Text Message{/ts}</span></a>
        <div class='spacer'></div>
      </div>
    </div>
  </div>

  <div class="crm-section msg_html-section">
  <h3 class="header-dark">{$form.pdf_format_id.label}</h3>
    <div class='text'>
      {$form.pdf_format_id.html}
    </div>
  </div>

  <div id="crm-submit-buttons" class="crm-submit-buttons">{$form.buttons.html}</div>
  </fieldset>
{/if}

{if $rows and $action ne 2 and $action ne 4}
<div class="crm-content-block crm-block">
  <div id='mainTabContainer'>
    <ul>
      {if $canEditUserDrivenMessageTemplates or $canEditMessageTemplates}
        <li id='tab_user'><a href='#user' title='{ts}User-driven Messages{/ts}'>{ts}User-driven Messages{/ts}</a></li>
      {/if}
      {if $canEditSystemTemplates or $canEditMessageTemplates}
        <li id='tab_workflow'><a href='#workflow' title='{ts}System Workflow Messages{/ts}'>{ts}System Workflow Messages{/ts}</a></li>
      {/if}
    </ul>

    {* create two selector tabs, first being the ‘user’ one, the second being the ‘workflow’ one *}
    {include file="CRM/common/enableDisableApi.tpl"}
    {include file="CRM/common/jsortable.tpl"}
    {foreach from=$rows item=template_row key=type}
      {if (
        $type ne 'userTemplates' && ($canEditSystemTemplates or $canEditMessageTemplates)
      ) or (
        $type eq 'userTemplates' && ($canEditUserDrivenMessageTemplates or $canEditMessageTemplates)
      )}
      <div id="{if $type eq 'userTemplates'}user{else}workflow{/if}" class='ui-tabs-panel ui-widget-content ui-corner-bottom'>
          <div class="help">
          {if $type eq 'userTemplates'}
            {capture assign=schedRemURL}{crmURL p='civicrm/admin/scheduleReminders' q="reset=1"}{/capture}
            {ts 1=$schedRemURL}Message templates allow you to easily create similar emails or letters on a recurring basis. Messages used for membership renewal reminders, as well as event and activity related reminders should be created via <a href="%1">Schedule Reminders</a>.{/ts}
            {if $isCiviMailEnabled}
              {capture assign=automatedMsgURL}{crmURL p='civicrm/admin/component' q="reset=1"}{/capture}
              {ts 1=$automatedMsgURL}You can also use message templates for CiviMail (bulk email) content. However, subscribe, unsubscribe and opt-out messages are configured at <a href="%1">Administer > CiviMail > Headers, Footers and Automated Messages</a>.{/ts}
            {/if}
            {help id="id-intro"}
          {else}
            {ts}System workflow message templates are used to generate the emails sent to constituents and administrators for contribution receipts, event confirmations and many other workflows. You can customize the style and wording of these messages here.{/ts} {help id="id-system-workflow"}
          {/if}
          </div>
        <div>
          {if $action ne 1 and $action ne 2 and $type eq 'userTemplates'}
            <div class="action-link">
              {crmButton p='civicrm/admin/messageTemplates/add' q="action=add&reset=1" id="newMessageTemplates"  icon="plus-circle"}{ts}Add Message Template{/ts}{/crmButton}
            </div>
            <div class="spacer"></div>
          {/if}
            {if !empty( $template_row)}
              <table class="display">
                <thead>
                  <tr>
                    <th class="sortable">{if $type eq 'userTemplates'}{ts}Message Title{/ts}{else}{ts}Workflow{/ts}{/if}</th>
                    {if $type eq 'userTemplates'}
                      <th>{ts}Message Subject{/ts}</th>
                      <th>{ts}Enabled?{/ts}</th>
                    {/if}
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                {foreach from=$template_row item=row}
                    <tr id="message_template-{$row.id}" class="crm-entity {$row.class}{if NOT $row.is_active} disabled{/if}">
                      <td>{$row.msg_title}</td>
                      {if $type eq 'userTemplates'}
                        <td>{$row.msg_subject}</td>
                        <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                      {/if}
                      <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
                    </tr>
                {/foreach}
                </tbody>
              </table>
              {/if}

            {if $action ne 1 and $action ne 2 and $type eq 'userTemplates'}
              <div class="action-link">
                {crmButton p='civicrm/admin/messageTemplates/add' q="action=add&reset=1" id="newMessageTemplates"  icon="plus-circle"}{ts}Add Message Template{/ts}{/crmButton}
              </div>
              <div class="spacer"></div>
            {/if}

            {if empty( $template_row)}
                <div class="messages status no-popup">
                    {icon icon="fa-info-circle"}{/icon}
                    {ts 1=$crmURL}There are no User-driven Message Templates entered. You can <a href='%1'>add one</a>.{/ts}
                </div>
            {/if}
         </div>
      </div>
      {/if}
    {/foreach}
  </div>
</div>

{elseif $action ne 1 and $action ne 2 and $action ne 4 and $action ne 8}
  <div class="messages status no-popup">
      <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
      {ts 1=$crmURL}There are no Message Templates entered. You can <a href='%1'>add one</a>.{/ts}
  </div>
{/if}
