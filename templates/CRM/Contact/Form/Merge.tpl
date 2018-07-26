{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
<div class="crm-block crm-form-block crm-contact-merge-form-block">
  <div class="help">
  {ts}Click <strong>Merge</strong> to move data from the Duplicate Contact on the left into the Main Contact. In addition to the contact data (address, phone, email...), you may choose to move all or some of the related activity records (groups, contributions, memberships, etc.).{/ts} {help id="intro"}
  </div>

  <div class="message status">
    <div class="icon inform-icon"></div>
    <strong>{ts}WARNING: The duplicate contact record WILL BE DELETED after the merge is complete.{/ts}</strong>
  </div>

  {if $user}
    <div class="message status">
      <div class="icon inform-icon"></div>
      <strong>{ts 1=$config->userFramework}WARNING: There are %1 user accounts associated with both the original and duplicate contacts. Ensure that the %1 user you want to retain is on the right - if necessary use the 'Flip between original and duplicate contacts.' option at top to swap the positions of the two records before doing the merge.
  The user record associated with the duplicate contact will not be deleted, but will be unlinked from the associated contact record (which will be deleted).
  You will need to manually delete that user (click on the link to open the %1 user account in new screen). You may need to give thought to how you handle any content or contents associated with that user.{/ts}</strong>
    </div>
  {/if}

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

  <div class="action-link">
    {if $prev}<a href="{$prev}" class="crm-hover-button action-item"><i class="crm-i fa-chevron-left"></i> {ts}Previous{/ts}</a>{/if}
    {if $next}<a href="{$next}" class="crm-hover-button action-item">{ts}Next{/ts} <i class="crm-i fa-chevron-right"></i></a>{/if}
    <a href="{$flip}" class="action-item crm-hover-button">
      <i class="crm-i fa-random"></i>
      {ts}Flip between original and duplicate contacts.{/ts}
    </a>
  </div>

  <div class="action-link">
    <a href="#" class="action-item crm-hover-button crm-notDuplicate" title={ts}Mark this pair as not a duplicate.{/ts} onClick="processDupes( {$main_cid|escape}, {$other_cid|escape}, 'dupe-nondupe', 'merge-contact', '{$browseUrl}' );return false;">
      <i class="crm-i fa-times-circle"></i>
      {ts}Mark this pair as not a duplicate.{/ts}
    </a>
  </div>

  <div class="action-link">
    <a href="javascript:void(0);" class="action-item crm-hover-button toggle_equal_rows">
      <i class="crm-i fa-eye-slash"></i>
      {ts}Show/hide rows with the same data on each contact record.{/ts}
    </a>
  </div>

  <table class="row-highlight">
    <tr class="columnheader">
      <th>&nbsp;</th>
      <th><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$other_cid"}">{$other_name|escape}</a> ({ts}duplicate{/ts})</th>
      <th>{ts}Mark All{/ts}<br />=={$form.toggleSelect.html} ==&gt;</th>
      <th><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$main_cid"}">{$main_name|escape}</a></th>
      <th width="300">Add/overwrite?</th>
    </tr>

    {crmAPI var='other_result' entity='Contact' action='get' return="modified_date" id=$other_cid}

    {crmAPI var='main_result' entity='Contact' action='get' return="modified_date" id=$main_cid}

    <tr>
      <td>Last modified</td>
      <td>{$other_result.values.0.modified_date|crmDate} {if $other_result.values.0.modified_date gt $main_result.values.0.modified_date} (Most recent) {/if}</td>
      <td></td>
      <td>{$main_result.values.0.modified_date|crmDate} {if $main_result.values.0.modified_date gt $other_result.values.0.modified_date} (Most recent) {/if}</td>
      <td></td>
    </tr>

    {foreach from=$rows item=row key=field}

      {if !isset($row.main) && !isset($row.other)}
        <tr style="background-color: #fff !important; border-bottom:1px solid #ccc !important;" class="no-data">
          <td>
            <strong>{$row.title|escape}</strong>
          </td>
      {else}
        {if $row.main eq $row.other}
           <tr class="merge-row-equal crm-row-ok {cycle values="odd-row,even-row"}">
        {else}
           <tr class="crm-row-error {cycle values="odd-row,even-row"}">
        {/if}
          <td>
            {$row.title|escape}
          </td>
        {/if}

          {assign var=position  value=$field|strrpos:'_'}
          {assign var=blockId   value=$field|substr:$position+1}
          {assign var=blockName value=$field|substr:14:$position-14}

          <td>
            {* @TODO check if this is ever an array or a fileName? *}
            {if $row.title|substr:0:5 == "Email"   OR
                $row.title|substr:0:7 == "Address"}
              <span style="white-space: pre">
            {else}
              <span>
            {/if}
            {if !is_array($row.other)}
              {$row.other|escape}
            {elseif $row.other.fileName}
              {$row.other.fileName|escape}
            {else}
              {', '|implode:$row.other}
            {/if}
            </span>
          </td>

          <td style='white-space: nowrap'>
             {if $form.$field}=={$form.$field.html|crmAddClass:"select-row"}==&gt;{/if}
          </td>

          {* For location blocks *}
          {if $row.title|substr:0:5 == "Email"   OR
              $row.title|substr:0:7 == "Address" OR
              $row.title|substr:0:2 == "IM"      OR
              $row.title|substr:0:7 == "Website" OR
              $row.title|substr:0:5 == "Phone"}

            <td>
              {strip}
                {if $row.title|substr:0:5 == "Email"   OR
                    $row.title|substr:0:7 == "Address"}
                  <span style="white-space: pre" id="main_{$blockName|escape}_{$blockId|escape}">
                {else}
                  <span id="main_{$blockName|escape}_{$blockId|escape}">
                {/if}
                {* @TODO check if this is ever an array or a fileName? *}
                {if !is_array($row.main)}
                  {$row.main|escape}
                {elseif $row.main.fileName}
                  {$row.main.fileName|escape}
                {else}
                  {', '|implode:$row.main}
                {/if}
                </span>
              {/strip}
            </td>

            <td>
              {* Display location for fields with locations *}
              {if $blockName eq 'email' || $blockName eq 'phone' || $blockName eq 'address' || $blockName eq 'im' }
                {$form.location_blocks.$blockName.$blockId.locTypeId.html}&nbsp;
              {/if}

              {* Display other_type_id for websites, ims and phones *}
              {if $blockName eq 'website' || $blockName eq 'im' || $blockName eq 'phone' }
                {$form.location_blocks.$blockName.$blockId.typeTypeId.html}&nbsp;
              {/if}

              {* Display the overwrite/add/add new label *}
              <span id="main_{$blockName}_{$blockId}_overwrite" class="location_block_controls">

                <span class="location_primary">
                  {if $row.main && $row.main_is_primary == "1"}Primary{/if}
                </span>

                <span class="location_block_controls_options">
                  <span class="location_operation_description">
                    {if $row.main}({ts}overwrite{/ts}){else}({ts}add{/ts}){/if}
                  </span>
                  <span style="display: block" class="location_operation_checkbox">
                    {if $row.main && ($blockName eq 'email' || $blockName eq 'phone')}
                      {$form.location_blocks.$blockName.$blockId.operation.html}
                    {/if}
                  </span>
                  <span style="display: block"  class="location_set_other_primary">
                    {if $blockName neq 'website' && (($row.main && $row.main_is_primary != "1") || !$row.main)}
                      {$form.location_blocks.$blockName.$blockId.set_other_primary.html}
                    {/if}
                  </span>
                </span>
              </span>

            </td>

          {* For non-location blocks *}
          {else}

            <td>
              <span>
                {if !is_array($row.main)}
                  {$row.main|escape}
                {elseif $row.main.fileName}
                  {$row.main.fileName|escape}
                {else}
                  {', '|implode:$row.main}
                {/if}
              </span>
            </td>

            <td>
              {if isset($row.main) || isset($row.other)}
                <span>
                  {if $row.main == $row.other}
                    <span class="action_label">({ts}match{/ts})</span><br />
                  {elseif $row.main}
                    <span class="action_label">({ts}overwrite{/ts})</span><br />
                   {else}
                     <span class="action_label">({ts}add{/ts})</span>
                  {/if}
                </span>
              {/if}
            </td>

          {/if}

       </tr>
    {/foreach}

    {foreach from=$rel_tables item=params key=paramName}
      {if $paramName eq 'move_rel_table_users'}
        <tr class="{cycle values="even-row,odd-row"}">
        <td><strong>{ts}Move related...{/ts}</strong></td><td>{if $otherUfId}<a target="_blank" href="{$params.other_url}">{$otherUfName}</a></td><td style='white-space: nowrap'>=={$form.$paramName.html|crmAddClass:"select-row"}==&gt;{else}<td style='white-space: nowrap'></td>{/if}</td><td>{if $mainUfId}<a target="_blank" href="{$params.main_url}">{$mainUfName}</a>{/if}</td>
        <td>({ts}migrate{/ts})</td>
      </tr>
      {else}
      <tr class="{cycle values="even-row,odd-row"}">
        <td><strong>{ts}Move related...{/ts}</strong></td><td><a href="{$params.other_url}">{$params.title}</a></td><td style='white-space: nowrap'>=={$form.$paramName.html|crmAddClass:"select-row"}==&gt;</td><td><a href="{$params.main_url}">{$params.title}</a>{if $form.operation.$paramName.add.html}&nbsp;{$form.operation.$paramName.add.html}{/if}</td>
         <td>({ts}migrate{/ts})</td>
      </tr>
      {/if}
    {/foreach}
  </table>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>

{literal}
<script type="text/javascript">

  var locationBlockInfo = {/literal}{$locationBlockInfo}{literal};
  var allBlock = {/literal}{$mainLocBlock}{literal};

  /**
   * Triggered when a 'location' or 'type' destination is changed, and when
   * the operation or 'set primary' checkboxes are changed.
   *
   * Check to see if the 'main' contact record has a corresponding location
   * block when the destination of a field is changed. Allow existing location
   * fields to be overwritten with data from the 'other' contact.
   *
   * @param blockName string
   *   The name of the entity.
   * @param blockId int
   *   The block ID being affected.
   * @param event object
   *   The event that triggered the update.
   */
  function updateMainLocationBlock(blockName, blockId, event) {

    // Get type of select list that's been changed (location or type)
    var locTypeId = CRM.$('select#location_blocks_' + blockName + '_' + blockId + '_locTypeId').val();
    var typeTypeId = CRM.$('select#location_blocks_' + blockName + '_' + blockId + '_typeTypeId').val();

    // @todo Fix this 'special handling' for websites (no location id)
    if (!locTypeId) {
      locTypeId = 0;
    }

    // Look for a matching block on the main contact
    var mainBlockId = 0;
    var mainBlockDisplay = '';
    var mainBlock = findBlock(blockName, locTypeId, typeTypeId);
    if (mainBlock != false) {
      mainBlockDisplay = mainBlock['display'];
      mainBlockId = mainBlock['id'];
    }

    // Update main location display and id
    CRM.$("input[name='location_blocks[" + blockName + "][" + blockId + "][mainContactBlockId]']").val(mainBlockId);
    CRM.$("#main_" + blockName + "_" + blockId).html(mainBlockDisplay);

    // Update controls area

    // Get the parent block once for speed
    var this_controls = CRM.$("#main_" + blockName + "_" + blockId + "_overwrite");

    // Update primary label
    if (mainBlock != false && mainBlock['is_primary'] == '1') {
      this_controls.find(".location_primary").text('Primary');
    }
    else {
      this_controls.find(".location_primary").text('');
    }

    // Update operation description
    var operation_description = "{/literal}{ts escape='js'}add{/ts}{literal}";
    var add_new_check_length = this_controls.find(".location_operation_checkbox input:checked").length;
    if (mainBlock != false) {
      if (add_new_check_length > 0) {
        operation_description = "{/literal}{ts}add new{/ts}{literal}";
      }
      else {
        operation_description = "{/literal}{ts}overwrite{/ts}{literal}";
      }
    }
    this_controls.find(".location_operation_description").text("(" + operation_description + ")");

    // Skip if the 'add new' or 'set primary' checkboxes were clicked
    if (event.target.id.match(/(operation|set_other_primary)/) === null) {
      // Display 'Add new' checkbox if there is a main block, and this is an
      // email or phone type.
      if (mainBlock != false && (blockName == 'email' || blockName == 'phone')) {
        var op_id = 'location_blocks[' + blockName + '][' + blockId + '][operation]';
        this_controls.find(".location_operation_checkbox").html(
                '<input id="' + op_id + '" name="' + op_id + '" type="checkbox" value="1" class="crm-form-checkbox"><label for="' + op_id + '">{/literal}{ts}Add new{/ts}{literal}</label>'
        );
      }
      else {
        this_controls.find(".location_operation_checkbox").html('');
      }
    }

    // Skip if 'set primary' was clicked
    if (event.target.id.match(/(set_other_primary)/) === null) {
      // Display 'Set primary' checkbox if applicable
      if (blockName != 'website' && (mainBlock == false || mainBlock['is_primary'] != "1" || add_new_check_length > 0)) {
        var prim_id = 'location_blocks[' + blockName + '][' + blockId + '][set_other_primary]';
        this_controls.find(".location_set_other_primary").html(
                '<input id="' + prim_id + '" name="' + prim_id + '" type="checkbox" value="1" class="crm-form-checkbox"><label for="' + prim_id + '">{/literal}{ts}Set as primary{/ts}{literal}</label>'
        );
      }
      else {
        this_controls.find(".location_set_other_primary").html('');
      }
    }

  }

  /**
   * Look for a matching 'main' contact location block by entity, location and
   * type
   *
   * @param entName string
   *   The entity name to lookup.
   * @param locationID int
   *   The location ID to lookup.
   * @param typeID int
   *   The type ID to lookup.
   *
   * @returns boolean|object
   *   Returns false if no match, otherwise an object with the location ID and
   *   display value.
   */
  function findBlock(entName, locationID, typeID) {
    var entityArray = allBlock[entName];
    var result = false;
    for (var i = 0; i < entityArray.length; i++) {
      // Match based on location and type ID, depending on the entity info
      if (locationBlockInfo[entName]['hasLocation'] == false || locationID == entityArray[i]['location_type_id']) {
        if (locationBlockInfo[entName]['hasType'] == false || typeID == entityArray[i][locationBlockInfo[entName]['hasType']]) {
          result = {
            display: entityArray[i][locationBlockInfo[entName]['displayField']],
            id: entityArray[i]['id'],
            is_primary: entityArray[i]['is_primary']
          };
          break;
        }
      }
    }
    return result;
  }

  /**
   * Called when a 'set primary' checkbox is clicked in order to disable any
   * other 'set primary' checkboxes for blocks of the same entity. So don't let
   * users try to set two different phone numbers as primary on the form.
   *
   * @param event object
   *   The event that triggered the update
   */
  function updateSetPrimaries(event) {
    var nameSplit = event.target.name.split('[');
    var blockName = nameSplit[1].slice(0, -1);
    var controls = CRM.$('span.location_block_controls[id^="main_' + blockName + '"]');

    // Enable everything
    controls.find('input[id$="[set_other_primary]"]:not(:checked)').removeAttr("disabled");

    // If one is checked, disable the others
    if (controls.find('input[id$="[set_other_primary]"]:checked').length > 0) {
      controls.find('input[id$="[set_other_primary]"]:not(:checked)').attr("disabled", "disabled");
    }
  }

  /**
   * Toggle the location type and the is_primary on & off depending on whether the merge box is ticked.
   *
   * @param element
   */
  function toggleRelatedLocationFields(element) {
    relatedElements = CRM.$(element).parent().siblings('td').find('input,select,label,hidden');
    if (CRM.$(element).is(':checked')) {
      relatedElements.removeClass('disabled').attr('disabled', false);

    }
    else {
      relatedElements.addClass('disabled').attr('disabled', true);
    }

  }

  CRM.$(function($) {
    $('input.crm-form-checkbox[data-is_location]').on('click', function(){
      toggleRelatedLocationFields(this)
    });

    // Show/hide matching data rows
    $('.toggle_equal_rows').click(function() {
      $('tr.merge-row-equal').toggle();
    });

    // Call mergeBlock whenever a location type is changed
    // (This is applied to the body because the inputs can be added dynamically
    // to the form, and we need to catch when they change.)
    $('body').on('change', 'select[id$="locTypeId"],select[id$="typeTypeId"],input[id$="[operation]"],input[id$="[set_other_primary]"]', function(event){

      // All the information we need is held in the id, separated by underscores
      var nameSplit = this.name.split('[');

      // Lookup the main value, if any are available
      if (allBlock[nameSplit[1].slice(0, -1)] != undefined) {
        updateMainLocationBlock(nameSplit[1].slice(0, -1), nameSplit[2].slice(0, -1), event);
      }

      // Update all 'set primary' checkboxes
      updateSetPrimaries(event);

    });

  });

</script>
{/literal}

{* process the dupe contacts *}
{include file="CRM/common/dedupe.tpl"}
