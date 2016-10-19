{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{* This form is for Contact Add/Edit interface *}
{if $addBlock}
  {include file="CRM/Contact/Form/Edit/$blockName.tpl"}
{else}
  {if $contactId}
    {include file="CRM/Contact/Form/Edit/Lock.tpl"}
  {/if}
  <div class="crm-form-block crm-search-form-block">
    {if call_user_func(array('CRM_Core_Permission','check'), 'administer CiviCRM') }
      <a href='{crmURL p="civicrm/admin/setting/preferences/display" q="reset=1"}' title="{ts}Click here to configure the panes.{/ts}"><i class="crm-i fa-wrench"></i></a>
    {/if}
    <span style="float:right;"><a href="#expand" id="expand">{ts}Expand all tabs{/ts}</a></span>
    <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
    </div>

    <div class="crm-accordion-wrapper crm-contactDetails-accordion">
      <div class="crm-accordion-header">
        {ts}Contact Details{/ts}
      </div><!-- /.crm-accordion-header -->
      <div class="crm-accordion-body" id="contactDetails">
        <div id="contactDetails">
          <div class="crm-section contact_basic_information-section">
          {include file="CRM/Contact/Form/Edit/$contactType.tpl"}
          </div>
          <table class="crm-section contact_information-section form-layout-compressed">
            {foreach from=$blocks item="label" key="block"}
              {include file="CRM/Contact/Form/Edit/$block.tpl"}
            {/foreach}
          </table>
          <table class="crm-section contact_source-section form-layout-compressed">
            <tr class="last-row">
              <td>{$form.contact_source.label} {help id="id-source"}<br />
                {$form.contact_source.html|crmAddClass:twenty}
              </td>
              <td>{$form.external_identifier.label}&nbsp;{help id="id-external-id"}<br />
                {$form.external_identifier.html}
              </td>
              {if $contactId}
                <td>
                  <label for="internal_identifier_display">{ts}Contact ID{/ts} {help id="id-internal-id"}</label><br />
                  <input id="internal_identifier_display" type="text" class="crm-form-text six" size="6" readonly="readonly" value="{$contactId}">
                </td>
              {/if}
            </tr>
          </table>
          <table class="image_URL-section form-layout-compressed">
            <tr>
              <td>
                {$form.image_URL.label}&nbsp;&nbsp;{help id="id-upload-image" file="CRM/Contact/Form/Contact.hlp"}<br />
                {$form.image_URL.html|crmAddClass:twenty}
                {if !empty($imageURL)}
                {include file="CRM/Contact/Page/ContactImage.tpl"}
                {/if}
              </td>
            </tr>
          </table>

          {*add dupe buttons *}
          <span class="crm-button crm-button_qf_Contact_refresh_dedupe">
            {$form._qf_Contact_refresh_dedupe.html}
          </span>
          {if $isDuplicate}
            &nbsp;&nbsp;
              <span class="crm-button crm-button_qf_Contact_upload_duplicate">
                {$form._qf_Contact_upload_duplicate.html}
              </span>
          {/if}
          <div class="spacer"></div>
        </div>
      </div><!-- /.crm-accordion-body -->
    </div><!-- /.crm-accordion-wrapper -->

    {foreach from = $editOptions item = "title" key="name"}
      {if $name eq 'CustomData' }
        <div id='customData'>{include file="CRM/Contact/Form/Edit/CustomData.tpl"}</div>
      {else}
        {include file="CRM/Contact/Form/Edit/$name.tpl"}
      {/if}
    {/foreach}
    <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
  </div>
  {literal}

  <script type="text/javascript" >
  CRM.$(function($) {
    var $form = $("form.{/literal}{$form.formClass}{literal}");
    var action = "{/literal}{$action}{literal}";

    $('.crm-accordion-body').each( function() {
      //remove tab which doesn't have any element
      if ( ! $.trim( $(this).text() ) ) {
        ele     = $(this);
        prevEle = $(this).prev();
        $(ele).remove();
        $(prevEle).remove();
      }
      //open tab if form rule throws error
      if ( $(this).children().find('span.crm-error').text().length > 0 ) {
        $(this).parents('.collapsed').crmAccordionToggle();
      }
    });
    if (action == '2') {
      $('.crm-accordion-wrapper').not('.crm-accordion-wrapper .crm-accordion-wrapper').each(function() {
        highlightTabs(this);
      });
      $('#crm-container').on('change click', '.crm-accordion-body :input, .crm-accordion-body a', function() {
        highlightTabs($(this).parents('.crm-accordion-wrapper'));
      });
    }
    function highlightTabs(tab) {
      //highlight the tab having data inside.
      $('.crm-accordion-body :input', tab).each( function() {
        var active = false;
          switch($(this).prop('type')) {
            case 'checkbox':
            case 'radio':
              if($(this).is(':checked') && !$(this).is('[id$=IsPrimary],[id$=IsBilling]')) {
                $('.crm-accordion-header:first', tab).addClass('active');
                return false;
              }
              break;

            case 'text':
            case 'textarea':
              if($(this).val()) {
                $('.crm-accordion-header:first', tab).addClass('active');
                return false;
              }
              break;

            case 'select-one':
            case 'select-multiple':
              if($(this).val() && $('option[value=""]', this).length > 0) {
                $('.crm-accordion-header:first', tab).addClass('active');
                return false;
              }
              break;

            case 'file':
              if($(this).next().html()) {
                $('.crm-accordion-header:first', tab).addClass('active');
                return false;
              }
              break;
          }
          $('.crm-accordion-header:first', tab).removeClass('active');
      });
    }

    $('a#expand').click( function() {
      if( $(this).attr('href') == '#expand') {
        var message = {/literal}"{ts escape='js'}Collapse all tabs{/ts}"{literal};
        $(this).attr('href', '#collapse');
        $('.crm-accordion-wrapper.collapsed').crmAccordionToggle();
      }
      else {
        var message = {/literal}"{ts escape='js'}Expand all tabs{/ts}"{literal};
        $('.crm-accordion-wrapper:not(.collapsed)').crmAccordionToggle();
        $(this).attr('href', '#expand');
      }
      $(this).html(message);
      return false;
    });

    $('.customDataPresent').change(function() {
      var values = $("#contact_sub_type").val();
      CRM.buildCustomData({/literal}"{$contactType}"{literal}, values).one('crmLoad', function() {
        highlightTabs(this);
        loadMultiRecordFields(values);
      });
    });

    function loadMultiRecordFields(subTypeValues) {
      if (subTypeValues === false) {
        subTypeValues = null;
      }
      else if (!subTypeValues) {
        subTypeValues = {/literal}"{$paramSubType}"{literal};
      }
      function loadNextRecord(i, groupValue, groupCount) {
        if (i < groupCount) {
          CRM.buildCustomData({/literal}"{$contactType}"{literal}, subTypeValues, null, i, groupValue, true).one('crmLoad', function() {
            highlightTabs(this);
            loadNextRecord(i+1, groupValue, groupCount);
          });
        }
      }
      {/literal}
      {foreach from=$customValueCount item="groupCount" key="groupValue"}
      {if $groupValue}{literal}
        loadNextRecord(1, {/literal}{$groupValue}{literal}, {/literal}{$groupCount}{literal});
      {/literal}
      {/if}
      {/foreach}
      {literal}
    }

    loadMultiRecordFields();

    {/literal}{if $oldSubtypes}{literal}
    $('input[name=_qf_Contact_upload_view], input[name=_qf_Contact_upload_new]').click(function() {
      var submittedSubtypes = $('#contact_sub_type').val();
      var oldSubtypes = {/literal}{$oldSubtypes}{literal};

      var warning = false;
      $.each(oldSubtypes, function(index, subtype) {
        if ( $.inArray(subtype, submittedSubtypes) < 0 ) {
          warning = true;
        }
      });
      if ( warning ) {
        return confirm({/literal}'{ts escape="js"}One or more contact subtypes have been de-selected from the list for this contact. Any custom data associated with de-selected subtype will be removed as long as the contact does not have a contact subtype still selected. Click OK to proceed, or Cancel to review your changes before saving.{/ts}'{literal});
      }
      return true;
    });
    {/literal}{/if}{literal}

    // Handle delete of multi-record custom data
    $form.on('click', '.crm-custom-value-del', function(e) {
      e.preventDefault();
      var $el = $(this),
        msg = '{/literal}{ts escape="js"}The record will be deleted immediately. This action cannot be undone.{/ts}{literal}';
      CRM.confirm({title: $el.attr('title'), message: msg})
        .on('crmConfirm:yes', function() {
          var url = CRM.url('civicrm/ajax/customvalue');
          var request = $.post(url, $el.data('post'));
          CRM.status({success: '{/literal}{ts escape="js"}Record Deleted{/ts}{literal}'}, request);
          var addClass = '.add-more-link-' + $el.data('post').groupID;
          $el.closest('div.crm-custom-accordion').remove();
          $('div' + addClass).last().show();
        });
    });
  });

</script>
{/literal}

{* jQuery validate *}
{include file="CRM/Form/validate.tpl"}

{* include common additional blocks tpl *}
{include file="CRM/common/additionalBlocks.tpl"}

{/if}
