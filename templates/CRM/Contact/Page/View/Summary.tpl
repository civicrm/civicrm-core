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
{* Contact Summary template for new tabbed interface. Replaces Basic.tpl *}
{if $action eq 2}
    {include file="CRM/Contact/Form/Contact.tpl"}
{else}

  {include file="CRM/common/wysiwyg.tpl" includeWysiwygEditor=true}

  {* include overlay js *}
  {include file="CRM/common/overlay.tpl"}

  <div class="crm-summary-contactname-block crm-inline-edit-container">
    <div class="crm-summary-block" id="contactname-block">
    {include file="CRM/Contact/Page/Inline/ContactName.tpl"}
    </div>
  </div>

  <div class="crm-actions-ribbon">
      <ul id="actions">
          {assign var='urlParams' value="reset=1"}
          {if $searchKey}
              {assign var='urlParams' value=$urlParams|cat:"&key=$searchKey"}
              {/if}
          {if $context}
              {assign var='urlParams' value=$urlParams|cat:"&context=$context"}
          {/if}

        {* Include the Actions and Edit buttons if user has 'edit' permission and contact is NOT in trash. *}
          {if $permission EQ 'edit' and !$isDeleted}
              <li class="crm-contact-activity crm-summary-block">
                  {include file="CRM/Contact/Page/Inline/Actions.tpl"}
              </li>
              <li>
                  {assign var='editParams' value=$urlParams|cat:"&action=update&cid=$contactId"}
                  <a href="{crmURL p='civicrm/contact/add' q=$editParams}" class="edit button" title="{ts}Edit{/ts}">
                  <span><div class="icon edit-icon"></div>{ts}Edit{/ts}</span>
                  </a>
              </li>
          {/if}

          {* Check for permissions to provide Restore and Delete Permanently buttons for contacts that are in the trash. *}
          {if (call_user_func(array('CRM_Core_Permission','check'), 'access deleted contacts') and
          $is_deleted)}
              <li class="crm-contact-restore">
                  <a href="{crmURL p='civicrm/contact/view/delete' q="reset=1&cid=$contactId&restore=1"}" class="delete button" title="{ts}Restore{/ts}">
                  <span><div class="icon restore-icon"></div>{ts}Restore from Trash{/ts}</span>
                  </a>
              </li>

              {if call_user_func(array('CRM_Core_Permission','check'), 'delete contacts')}
                  <li class="crm-contact-permanently-delete">
                      <a href="{crmURL p='civicrm/contact/view/delete' q="reset=1&delete=1&cid=$contactId&skip_undelete=1"}" class="delete button" title="{ts}Delete Permanently{/ts}">
                      <span><div class="icon delete-icon"></div>{ts}Delete Permanently{/ts}</span>
                      </a>
                  </li>
              {/if}

          {elseif call_user_func(array('CRM_Core_Permission','check'), 'delete contacts')}
              {assign var='deleteParams' value="&reset=1&delete=1&cid=$contactId"}
              <li class="crm-delete-action crm-contact-delete">
                  <a href="{crmURL p='civicrm/contact/view/delete' q=$deleteParams}" class="delete button" title="{ts}Delete{/ts}">
                  <span><div class="icon delete-icon"></div>{ts}Delete Contact{/ts}</span>
                  </a>
              </li>
          {/if}

          {* Previous and Next contact navigation when accessing contact summary from search results. *}
          {if $nextPrevError}
             <li class="crm-next-action">
               {help id="id-next-prev-buttons"}&nbsp;
             </li>
          {else}
            {if $nextContactID}
             {assign var='viewParams' value=$urlParams|cat:"&cid=$nextContactID"}
             <li class="crm-next-action">
               <a href="{crmURL p='civicrm/contact/view' q=$viewParams}" class="view button" title="{$nextContactName}">
               <span title="{$nextContactName}"><div class="icon next-icon"></div>{ts}Next{/ts}</span>
               </a>
             </li>
            {/if}
            {if $prevContactID}
             {assign var='viewParams' value=$urlParams|cat:"&cid=$prevContactID"}
             <li class="crm-previous-action">
               <a href="{crmURL p='civicrm/contact/view' q=$viewParams}" class="view button" title="{$prevContactName}">
               <span title="{$prevContactName}"><div class="icon previous-icon"></div>{ts}Previous{/ts}</span>
               </a>
             </li>
            {/if}
          {/if}


          {if !empty($groupOrganizationUrl)}
          <li class="crm-contact-associated-groups">
              <a href="{$groupOrganizationUrl}" class="associated-groups button" title="{ts}Associated Multi-Org Group{/ts}">
              <span><div class="icon associated-groups-icon"></div>{ts}Associated Multi-Org Group{/ts}</span>
              </a>
          </li>
          {/if}
      </ul>
      <div class="clear"></div>
  </div><!-- .crm-actions-ribbon -->

  <div class="crm-block crm-content-block crm-contact-page crm-inline-edit-container">
      <div id="mainTabContainer" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
          <ul class="crm-contact-tabs-list">
              <li id="tab_summary" class="crm-tab-button">
                <a href="#contact-summary" title="{ts}Summary{/ts}">
                <span> </span> {ts}Summary{/ts}
                <em>&nbsp;</em>
                </a>
              </li>
              {foreach from=$allTabs key=tabName item=tabValue}
              <li id="tab_{$tabValue.id}" class="crm-tab-button crm-count-{$tabValue.count}">
                <a href="{$tabValue.url}" title="{$tabValue.title}">
                  <span> </span> {$tabValue.title}
                  <em>{$tabValue.count}</em>
                </a>
              </li>
              {/foreach}
          </ul>

          <div id="contact-summary" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
              {if (isset($hookContentPlacement) and ($hookContentPlacement neq 3)) or empty($hookContentPlacement)}

                  {if !empty($hookContent) and isset($hookContentPlacement) and $hookContentPlacement eq 2}
                      {include file="CRM/Contact/Page/View/SummaryHook.tpl"}
                  {/if}

                  <div class="contactTopBar contact_panel">
                    <div class="contactCardLeft">
                      <div class="crm-summary-contactinfo-block">
                        <div class="crm-summary-block" id="contactinfo-block">
                        {include file="CRM/Contact/Page/Inline/ContactInfo.tpl"}
                        </div>
                      </div>
                    </div> <!-- end of left side -->
                    <div class="contactCardRight">
                      {if !empty($imageURL)}
                      <div id="crm-contact-thumbnail">
                        {include file="CRM/Contact/Page/ContactImage.tpl"}
                      </div>
                      {/if}
                      <div class="{if !empty($imageURL)} float-left{/if}">
                        <div class="crm-clear crm-summary-block">
                          <div class="crm-summary-row">
                            <div class="crm-label" id="tagLink">
                              <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId&selectedChild=tag"}" title="{ts}Edit Tags{/ts}">{ts}Tags{/ts}</a>
                            </div>
                            <div class="crm-content" id="tags">{$contactTag}</div>
                          </div>
                          <div class="crm-summary-row">
                            <div class="crm-label">{ts}Contact Type{/ts}</div>
                            <div class="crm-content crm-contact_type_label">
                              {if isset($contact_type_label)}{$contact_type_label}{/if}
                            </div>
                          </div>

                          <div class="crm-summary-row">
                            <div class="crm-label">
                              {ts}CiviCRM ID{/ts}{if !empty($userRecordUrl)} / {ts}User ID{/ts}{/if}
                            </div>
                            <div class="crm-content">
                              <span class="crm-contact-contact_id">{$contactId}</span>
                              {if !empty($userRecordUrl)}
                              <span class="crm-contact-user_record_id">
                                &nbsp;/&nbsp;<a title="View user record" class="user-record-link" href="{$userRecordUrl}">{$userRecordId}</a>
                              </span>
                              {/if}
                            </div>
                          </div>
                        </div>
                      </div>

                    </div> <!-- end of right side -->
                  </div>
                  <div class="contact_details">
                      <div class="contact_panel">
                        <div class="contactCardLeft">
                         <div >
                          {if $showEmail}
                          <div class="crm-summary-email-block crm-summary-block" id="email-block">
                              {include file="CRM/Contact/Page/Inline/Email.tpl"}
                          </div>
                          {/if}
                          {if $showWebsite}
                          <div class="crm-summary-website-block crm-summary-block" id="website-block">
                              {include file="CRM/Contact/Page/Inline/Website.tpl"}
                          </div>
                          {/if}
                         </div>
                        </div><!-- #contactCardLeft -->

                        <div class="contactCardRight">
                          <div >
                            {if $showPhone}
                            <div class="crm-summary-phone-block crm-summary-block" id="phone-block">
                                {include file="CRM/Contact/Page/Inline/Phone.tpl"}
                            </div>
                            {/if}
                            {if $showIM}
                            <div class="crm-summary-im-block crm-summary-block" id="im-block">
                                {include file="CRM/Contact/Page/Inline/IM.tpl"}
                            </div>
                            {/if}
                            {if $showOpenID}
                            <div class="crm-summary-openid-block crm-summary-block" id="openid-block">
                                {include file="CRM/Contact/Page/Inline/OpenID.tpl"}
                            </div>
                            {/if}
                          </div>
                        </div><!-- #contactCardRight -->

                        <div class="clear"></div>
                      </div><!-- #contact_panel -->
                      {if $showAddress}
                      <div class="contact_panel">
                        {assign var='locationIndex' value=1}
                        {if $address}
                          {foreach from=$address item=add key=locationIndex}
                            <div class="{if $locationIndex is odd}contactCardLeft{else}contactCardRight{/if} crm-address_{$locationIndex} crm-address-block crm-summary-block">
                              {include file="CRM/Contact/Page/Inline/Address.tpl"}
                            </div>
                          {/foreach}
                          {assign var='locationIndex' value=$locationIndex+1}
                        {/if}
                        {* add new link *}
                        {if $permission EQ 'edit'}
                          {assign var='add' value=0}
                            <div class="{if $locationIndex is odd}contactCardLeft{else}contactCardRight{/if} crm-address-block crm-summary-block">
                              {include file="CRM/Contact/Page/Inline/Address.tpl"}
                            </div>
                      {/if}

                    </div> <!-- end of contact panel -->
                    {/if}
                    <div class="contact_panel">
                      {if $showCommunicationPreferences}
                      <div class="contactCardLeft">
                        <div class="crm-summary-comm-pref-block">
                        <div class="crm-summary-block" id="communication-pref-block" >
                          {include file="CRM/Contact/Page/Inline/CommunicationPreferences.tpl"} 
                        </div>
                        </div>
                      </div> <!-- contactCardLeft -->
                      {/if}
                      {if $contact_type eq 'Individual' AND $showDemographics}
                        <div class="contactCardRight">
                          <div class="crm-summary-demographic-block">
                          <div class="crm-summary-block" id="demographic-block">
                            {include file="CRM/Contact/Page/Inline/Demographics.tpl"} 
                          </div>
                          </div>
                        </div> <!-- contactCardRight -->
                      {/if}
                      <div class="clear"></div>
                      <div class="separator"></div>
                    </div> <!-- contact panel -->
                </div><!--contact_details-->
       
                {if $showCustomData}
                  <div id="customFields">
                    <div class="contact_panel">
                      <div class="contactCardLeft">
                        {include file="CRM/Contact/Page/View/CustomDataView.tpl" side='1'}
                      </div><!--contactCardLeft-->

                      <div class="contactCardRight">
                        {include file="CRM/Contact/Page/View/CustomDataView.tpl" side='0'}
                      </div>

                      <div class="clear"></div>
                    </div>
                  </div>
                {/if}         
       
                {if !empty($hookContent) and isset($hookContentPlacement) and $hookContentPlacement eq 1}
                  {include file="CRM/Contact/Page/View/SummaryHook.tpl"}
                {/if}
               {else}
                  {include file="CRM/Contact/Page/View/SummaryHook.tpl"}
               {/if}
        </div>
        <div class="clear"></div>
    </div>
    <div class="clear"></div>
  </div><!-- /.crm-content-block -->

   <script type="text/javascript">
    {literal}
    cj(function($) {
      //explicitly stop spinner
      function stopSpinner( ) {
        $('li.crm-tab-button span').text(' ');
      }
      {/literal}
      var selectedTab = '{if !empty($selectedChild)}{$selectedChild}{else}summary{/if}';
      var tabIndex = $('#tab_' + selectedTab).prevAll().length;
      var spinnerImage = '<img src="{$config->resourceBase}i/loading.gif" style="width:10px;height:10px"/>';
      {literal}
      $("#mainTabContainer").tabs({ selected: tabIndex, spinner: spinnerImage, cache: true, load: stopSpinner});
      $(".crm-tab-button").addClass("ui-corner-bottom");
      $().crmAccordions();

      $('body').click(function() {
        cj('#crm-contact-actions-list').hide();
      });
    });
    {/literal}
   </script>
{/if}

{* CRM-10560 *}
{literal}
<script type="text/javascript">
cj(document).ready(function($) {
  $('.crm-inline-edit-container').crmFormContactLock({
    ignoreLabel: "{/literal}{ts escape='js'}Ignore{/ts}{literal}",
    saveAnywayLabel: "{/literal}{ts escape='js'}Save Anyway{/ts}{literal}",
    reloadLabel: "{/literal}{ts escape='js'}Reload Page{/ts}{literal}"
  });
});
</script>
{/literal}

{* jQuery validate *}
{include file="CRM/Form/validate.tpl" form=0}
