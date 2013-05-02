{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
{* this template is used for adding/editing other (custom) activities. *}
{if $cdType }
  {include file="CRM/Custom/Form/CustomData.tpl"}
{else}
  {if $action eq 4}
    <div class="crm-block crm-content-block crm-activity-view-block">
  {else}
    {if $context NEQ 'standalone'}
    <h3>{if $action eq 1 or $action eq 1024}{ts 1=$activityTypeName}New activity: %1{/ts}{elseif $action eq 8}{ts 1=$activityTypeName}Delete %1{/ts}{else}{ts 1=$activityTypeName}Edit %1{/ts}{/if}</h3>
    {/if}
    {if $activityTypeDescription }
      <div class="help">{$activityTypeDescription}</div>
    {/if}
    <div class="crm-block crm-form-block crm-activity-form-block">
  {/if}
  {* added onload javascript for source contact*}
  {literal}
  <script type="text/javascript">
  var assignee_contact = '';

  {/literal}
  {if $assignee_contact}
    var assignee_contact = {$assignee_contact};
  {/if}
  {literal}

  //loop to set the value of cc and bcc if form rule.
  var assignee_contact_id = null;
  var toDataUrl = "{/literal}{crmURL p='civicrm/ajax/checkemail' q='id=1&noemail=1' h=0 }{literal}"; {/literal}
  {foreach from=","|explode:"assignee" key=key item=element}
    {assign var=currentElement value=`$element`_contact_id}
    {if $form.$currentElement.value }
      {literal} var {/literal}{$currentElement}{literal} = cj.ajax({ url: toDataUrl + "&cid={/literal}{$form.$currentElement.value}{literal}", async: false }).responseText;{/literal}
    {/if}
  {/foreach}
  {literal}

  if ( assignee_contact_id ) {
    eval( 'assignee_contact = ' + assignee_contact_id );
  }

  cj(function( ) {
    {/literal}
    {if $source_contact and $admin and $action neq 4}
      {literal} cj( '#source_contact_id' ).val( "{/literal}{$source_contact}{literal}");{/literal}
    {/if}
    {literal}

    var sourceDataUrl = "{/literal}{$dataUrl}{literal}";
    var tokenDataUrl_assignee  = "{/literal}{$tokenUrl}&context=activity_assignee{literal}";
    var hintText = "{/literal}{ts escape='js'}Type in a partial or complete name of an existing contact.{/ts}{literal}";
    cj( "#assignee_contact_id").tokenInput( tokenDataUrl_assignee, { prePopulate: assignee_contact, theme: 'facebook', hintText: hintText });
    cj( 'ul.token-input-list-facebook, div.token-input-dropdown-facebook' ).css( 'width', '450px' );
    cj('#source_contact_id').autocomplete( sourceDataUrl, { width : 180, selectFirst : false, hintText: hintText, matchContains: true, minChars: 1
    }).result( function(event, data, formatted) { cj( "#source_contact_qid" ).val( data[1] );
      }).bind( 'click', function( ) { cj( "#source_contact_qid" ).val(''); });
  });
  </script>

  {/literal}
  {if !$action or ( $action eq 1 ) or ( $action eq 2 ) }
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  {/if}

  {if $action eq 8} {* Delete action. *}
  <table class="form-layout">
  <tr>
    <td colspan="2">
      <div class="status">{ts 1=$delName}Are you sure you want to delete '%1'?{/ts}</div>
    </td>
  </tr>
  {elseif $action eq 1 or $action eq 2  or $action eq 4 or $context eq 'search' or $context eq 'smog'}

  <table class="{if $action eq 4}crm-info-panel{else}form-layout{/if}">

  {if $action eq 4}
  <h3>{$activityTypeName}</h3>
    {if $activityTypeDescription }
    <div class="help">{$activityTypeDescription}</div>
    {/if}  
  {else}
    {if $context eq 'standalone' or $context eq 'search' or $context eq 'smog'}
    <tr class="crm-activity-form-block-activity_type_id">
      <td class="label">{$form.activity_type_id.label}</td><td class="view-value">{$form.activity_type_id.html}</td>
    </tr>
    {/if}
  {/if}

  {if $surveyActivity}
  <tr class="crm-activity-form-block-survey">
    <td class="label">{ts}Survey Title{/ts}</td><td class="view-value">{$surveyTitle}</td>
  </tr>
  {/if}

  <tr class="crm-activity-form-block-source_contact_id">
    <td class="label">{$form.source_contact_id.label}</td>
    <td class="view-value">
      {if $admin and $action neq 4}{$form.source_contact_id.html} {else} {$source_contact_value} {/if}
    </td>
  </tr>

  <tr class="crm-activity-form-block-target_contact_id">
    {if $single eq false}
      <td class="label">{ts}With Contact(s){/ts}</td>
      <td class="view-value" style="white-space: normal">
        {$with|escape}
        <br/>
        {$form.is_multi_activity.html}&nbsp;{$form.is_multi_activity.label} {help id="id-is_multi_activity"}
      </td>
      {elseif $action neq 4}
      <td class="label">{ts}With Contact{/ts}</td>
      <td class="view-value">
        {include file="CRM/Contact/Form/NewContact.tpl" noLabel=true skipBreak=true multiClient=true}
        {if $action eq 1}
        <br/>
        {$form.is_multi_activity.html}&nbsp;{$form.is_multi_activity.label} {help id="id-is_multi_activity"}
        {/if}
      </td>
      {else}
      <td class="label">{ts}With Contact{/ts}</td>
      <td class="view-value" style="white-space: normal">
        {foreach from=$target_contact key=id item=name}
          <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$id"}">{$name}</a>;&nbsp;
        {/foreach}
      </td>
    {/if}
  </tr>

  <tr class="crm-activity-form-block-assignee_contact_id">
    {if $action eq 4}
      <td class="label">{ts}Assigned To{/ts}</td><td class="view-value">
      {foreach from=$assignee_contact key=id item=name}
        <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$id"}">{$name}</a>;&nbsp;
      {/foreach}
    </td>
      {else}
      <td class="label">{ts}Assigned To{/ts}</td>
      <td>{$form.assignee_contact_id.html}
        {edit}
          <span class="description">{ts}You can optionally assign this activity to someone. Assigned activities will appear in their Activities listing at CiviCRM Home.{/ts}
          {if $activityAssigneeNotification}
            <br />{ts}A copy of this activity will be emailed to each Assignee.{/ts}
          {/if}
          </span>
        {/edit}
      </td>
    {/if}
  </tr>

  {if $activityTypeFile}
  {include file="CRM/$crmDir/Form/Activity/$activityTypeFile.tpl"}
  {/if}

  <tr class="crm-activity-form-block-subject">
    <td class="label">{$form.subject.label}</td><td class="view-value">{$form.subject.html|crmAddClass:huge}</td>
  </tr>

  {* CRM-7362 --add campaign to activities *}
  {include file="CRM/Campaign/Form/addCampaignToComponent.tpl"
  campaignTrClass="crm-activity-form-block-campaign_id"}

  {* build engagement level CRM-7775 *}
  {if $buildEngagementLevel}
  <tr class="crm-activity-form-block-engagement_level">
    <td class="label">{$form.engagement_level.label}</td>
    <td class="view-value">{$form.engagement_level.html}</td>
  </tr>
  {/if}

  <tr class="crm-activity-form-block-location">
    <td class="label">{$form.location.label}</td><td class="view-value">{$form.location.html|crmAddClass:huge}</td>
  </tr>
  <tr class="crm-activity-form-block-activity_date_time">
    <td class="label">{$form.activity_date_time.label}</td>
    {if $action neq 4}
      <td class="view-value">{include file="CRM/common/jcalendar.tpl" elementName=activity_date_time}</td>
      {else}
      <td class="view-value">{$form.activity_date_time.html|crmDate}</td>
    {/if}
  </tr>
  <tr class="crm-activity-form-block-duration">
    <td class="label">{$form.duration.label}</td>
    <td class="view-value">
      {$form.duration.html}
      {if $action neq 4}<span class="description">{ts}Total time spent on this activity (in minutes).{/ts}{/if}
    </td>
  </tr>
  <tr class="crm-activity-form-block-status_id">
    <td class="label">{$form.status_id.label}</td><td class="view-value">{$form.status_id.html}</td>
  </tr>
  <tr class="crm-activity-form-block-details">
    <td class="label">{$form.details.label}</td>
    {if $activityTypeName eq "Print PDF Letter"}
      <td class="view-value">
      {* If using plain textarea, assign class=huge to make input large enough. *}
      {if $defaultWysiwygEditor eq 0}{$form.details.html|crmAddClass:huge}{else}{$form.details.html}{/if}
      </td>
      {else}
      <td class="view-value">
      {* If using plain textarea, assign class=huge to make input large enough. *}
       {if $defaultWysiwygEditor eq 0}{$form.details.html|crmStripAlternatives|crmAddClass:huge}{else}{$form.details.html|crmStripAlternatives}{/if}
      </td>
    {/if}
  </tr>
  <tr class="crm-activity-form-block-priority_id">
    <td class="label">{$form.priority_id.label}</td><td class="view-value">{$form.priority_id.html}</td>
  </tr>
  {if $surveyActivity }
  <tr class="crm-activity-form-block-result">
    <td class="label">{$form.result.label}</td><td class="view-value">{$form.result.html}</td>
  </tr>
  {/if}
  {if $form.tag.html}
  <tr class="crm-activity-form-block-tag">
    <td class="label">{$form.tag.label}</td>
    <td class="view-value"><div class="crm-select-container">{$form.tag.html}</div>
      {literal}
        <script type="text/javascript">
          cj(".crm-activity-form-block-tag select[multiple]").crmasmSelect({
            addItemTarget: 'bottom',
            animate: true,
            highlight: true,
            sortable: true,
            respectParents: true
          });
        </script>
      {/literal}
    </td>
  </tr>
  {/if}

  {if $tagsetInfo_activity}
  <tr class="crm-activity-form-block-tag_set"><td colspan="2">{include file="CRM/common/Tag.tpl" tagsetType='activity'}</td></tr>
  {/if}

  {if $action neq 4 OR $viewCustomData}
  <tr class="crm-activity-form-block-custom_data">
    <td colspan="2">
      {if $action eq 4}
      {include file="CRM/Custom/Page/CustomDataView.tpl"}
        {else}
        <div id="customData"></div>
      {/if}
    </td>
  </tr>
  {/if}

  {if $action eq 4 AND $currentAttachmentInfo}
    {include file="CRM/Form/attachment.tpl"}{* For view action the include provides the row and cells. *}
  {elseif $action eq 1 OR $action eq 2}
    <tr class="crm-activity-form-block-attachment">
      <td colspan="2">
      {include file="CRM/Form/attachment.tpl"}
      </td>
    </tr>
  {/if}

  {if $action neq 4} {* Don't include "Schedule Follow-up" section in View mode. *}
  <tr class="crm-activity-form-block-schedule_followup">
    <td colspan="2">
      <div class="crm-accordion-wrapper collapsed">
        <div class="crm-accordion-header">
          {ts}Schedule Follow-up{/ts}
        </div><!-- /.crm-accordion-header -->
        <div class="crm-accordion-body">
          <table class="form-layout-compressed">
            <tr><td class="label">{ts}Schedule Follow-up Activity{/ts}</td>
              <td>{$form.followup_activity_type_id.html}&nbsp;&nbsp;{ts}on{/ts}
              {include file="CRM/common/jcalendar.tpl" elementName=followup_date}
              </td>
            </tr>
            <tr>
              <td class="label">{$form.followup_activity_subject.label}</td>
              <td>{$form.followup_activity_subject.html|crmAddClass:huge}</td>
            </tr>
          </table>
        </div><!-- /.crm-accordion-body -->
      </div><!-- /.crm-accordion-wrapper -->
      {literal}
        <script type="text/javascript">
          cj(function() {
            cj().crmAccordions();
            cj('.crm-accordion-body').each( function() {
              //open tab if form rule throws error
              if ( cj(this).children( ).find('span.crm-error').text( ).length > 0 ) {
                cj(this).parent('.collapsed').crmAccordionToggle();
              }
            });
          });
        </script>
      {/literal}
    </td>
  </tr>
  {/if}
  {/if} {* End Delete vs. Add / Edit action *}
  </table>
  <div class="crm-submit-buttons">
  {if $action eq 4 && $activityTName neq 'Inbound Email'}
    {if !$context }
      {assign var="context" value='activity'}
    {/if}
    {if $permission EQ 'edit'}
      {assign var='urlParams' value="reset=1&atype=$atype&action=update&reset=1&id=$entityID&cid=$contactId&context=$context"}
      {if ($context eq 'fulltext' || $context eq 'search') && $searchKey}
        {assign var='urlParams' value="reset=1&atype=$atype&action=update&reset=1&id=$entityID&cid=$contactId&context=$context&key=$searchKey"}
      {/if}
      <a href="{crmURL p='civicrm/activity/add' q=$urlParams}" class="edit button" title="{ts}Edit{/ts}"><span><div class="icon edit-icon"></div>{ts}Edit{/ts}</span></a>
    {/if}

    {if call_user_func(array('CRM_Core_Permission','check'), 'delete activities')}
      {assign var='urlParams' value="reset=1&atype=$atype&action=delete&reset=1&id=$entityID&cid=$contactId&context=$context"}
      {if ($context eq 'fulltext' || $context eq 'search') && $searchKey}
        {assign var='urlParams' value="reset=1&atype=$atype&action=delete&reset=1&id=$entityID&cid=$contactId&context=$context&key=$searchKey"}
      {/if}
      <a href="{crmURL p='civicrm/contact/view/activity' q=$urlParams}" class="delete button" title="{ts}Delete{/ts}"><span><div class="icon delete-icon"></div>{ts}Delete{/ts}</span></a>
    {/if}
  {/if}
  {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

  {include file="CRM/Case/Form/ActivityToCase.tpl"}

  {if $action eq 1 or $action eq 2 or $context eq 'search' or $context eq 'smog'}
  {*include custom data js file*}
  {include file="CRM/common/customData.tpl"}
    {literal}
    <script type="text/javascript">
    cj(function() {
    {/literal}
    {if $customDataSubType}
      CRM.buildCustomData( '{$customDataType}', {$customDataSubType} );
      {else}
      CRM.buildCustomData( '{$customDataType}' );
    {/if}
    {literal}
    });
    </script>
    {/literal}
  {/if}
  {if ! $form.case_select}
  {include file="CRM/common/formNavigate.tpl"}
  {/if}
  </div>{* end of form block*}
{/if} {* end of snippet if*}
