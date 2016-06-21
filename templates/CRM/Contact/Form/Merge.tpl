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
    <a href="#" class="action-item crm-hover-button crm-notDuplicate" title={ts}Mark this pair as not a duplicate.{/ts} onClick="processDupes( {$main_cid}, {$other_cid}, 'dupe-nondupe', 'merge-contact', '{$browseUrl}' );return false;">
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
      <th><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$other_cid"}">{$other_name}</a> ({ts}duplicate{/ts})</th>
      <th>{ts}Mark All{/ts}<br />=={$form.toggleSelect.html} ==&gt;</th>
      <th><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$main_cid"}">{$main_name}</a></th>
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
            <strong>{$row.title}</strong>
          </td>
      {else}
        {if $row.main eq $row.other}
           <tr class="merge-row-equal crm-row-ok {cycle values="odd-row,even-row"}">
        {else}
           <tr class="crm-row-error {cycle values="odd-row,even-row"}">
        {/if}
          <td>
            {$row.title}
          </td>
        {/if}

          {assign var=position  value=$field|strrpos:'_'}
          {assign var=blockId   value=$field|substr:$position+1}
          {assign var=blockName value=$field|substr:14:$position-14}

          <td>
            {if $row.title|substr:0:7 == "Address"}<span style="white-space:pre">{else}<span>{/if}{if !is_array($row.other)}{$row.other}{elseif $row.other.fileName}{$row.other.fileName}{else}{', '|implode:$row.other}{/if}</span>
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
              {if $row.title|substr:0:7 == "Address"}<span id="main_{$blockName}_{$blockId}" style="white-space:pre">{else}<span id="main_{$blockName}_{$blockId}">{/if}{if !is_array($row.main)}{$row.main}{elseif $row.main.fileName}{$row.main.fileName}{else}{', '|implode:$row.main}{/if}</span>
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
              <span id="main_{$blockName}_{$blockId}_overwrite">
                {if $row.main}
                  <span class="action_label">({ts}overwrite{/ts})</span>&nbsp;
                   {if $blockName eq 'email' || $blockName eq 'phone' }
                     {$form.location_blocks.$blockName.$blockId.operation.html}&nbsp;
                   {/if}
                   <br />
                {else}
                  <span class="action_label">({ts}add{/ts})</span>&nbsp;
                {/if}
              </span>
            </td>

          {* For non-location blocks *}
          {else}

            <td>
              <span>
                {if !is_array($row.main)}
                  {$row.main}
                {elseif $row.main.fileName}
                  {$row.main.fileName}
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
  <div class='form-item'>
    <!--<p>{$form.moveBelongings.html} {$form.moveBelongings.label}</p>-->
    <!--<p>{$form.deleteOther.html} {$form.deleteOther.label}</p>-->
  </div>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>

{literal}
<script type="text/javascript">

  var locationBlockInfo = {/literal}{$locationBlockInfo}{literal};
  var allBlock = {/literal}{$mainLocBlock}{literal};

  /**
   * Triggered when a 'location' or 'type' destination is changed.
   * Check to see if the 'main' contact record has a corresponding location
   * block when the destination of a field is changed. Allow existing location
   * fields to be overwritten with data from the 'other' contact.
   *
   * @param blockname string
   *   The name of the entity.
   * @param element object
   *   The element that was changed (location or type dropdown)
   * @param blockId int
   *   The block ID being affected
   * @param type string
   *   Location or type (locTypeId / typeTypeId)
   */
  function mergeBlock(blockname, element, blockId, type) {

    // Get type of select list that's been changed (location or type)
    var locTypeId = '';
    var typeTypeId = '';

    // If the location was changed, lookup the type if it exists
    if (type == 'locTypeId') {
      locTypeId = element.value;
      typeTypeId = CRM.$( 'select#location_blocks_' + blockname + '_' + blockId + '_typeTypeId' ).val();
    }

    // Otherwise the type was changed, lookup the location if it exists
    else {
      locTypeId = CRM.$( 'select#location_blocks_' + blockname + '_' + blockId + '_locTypeId' ).val();
      typeTypeId = element.value;
    }

    // @todo Fix this 'special handling' for websites (no location id)
    if (!locTypeId) { locTypeId = 0; }

    // Look for a matching block on the main contact
    var mainBlockId = 0;
    var mainBlockDisplay = '';
    var mainBlock = findBlock(allBlock, blockname, locTypeId, typeTypeId);

    // Create appropriate label / add new link after changing the block
    if (mainBlock == false) {
      label = '<span class="action_label">({/literal}{ts}add{/ts}{literal})</span>';
    }
    else {

      // Set display and ID
      mainBlockDisplay = mainBlock['display'];
      mainBlockId = mainBlock['id'];

      // Set label
      var label = '<span class="action_label">({/literal}{ts}overwrite{/ts}{literal})</span> ';
      if (blockname == 'email' || blockname == 'phone') {
        var opLabel = 'location_blocks[' + blockname + '][' + blockId + '][operation]';
        label += '<input id="' + opLabel + '" name="' + opLabel + '" type="checkbox" value="1" class="crm-form-checkbox"> <label for="' + opLabel + '">{/literal}{ts}add new{/ts}{literal}</label><br />';
      }
      label += '<br>';
    }

    // Update DOM
    CRM.$( "input[name='location_blocks[" + blockname + "][" + blockId + "][mainContactBlockId]']" ).val( mainBlockId );
    CRM.$( "#main_" + blockname + "_" + blockId ).html( mainBlockDisplay );
    CRM.$( "#main_" + blockname + "_" + blockId + "_overwrite" ).html( label );
  }

  /**
   * Look for a matching 'main' contact location block by entity, location and
   * type
   *
   * @param allBlock array
   *   All location blocks on the main contact record.
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
  function findBlock(allBlock, entName, locationID, typeID) {
    var entityArray = allBlock[entName];
    var result = false;
    for (var i = 0; i < entityArray.length; i++) {
      // Match based on location and type ID, depending on the entity info
      if (locationBlockInfo[entName]['hasLocation'] == false || locationID == entityArray[i]['location_type_id']) {
        if (locationBlockInfo[entName]['hasType'] == false || typeID == entityArray[i][locationBlockInfo[entName]['hasType']]) {
          result = {
            display: entityArray[i][locationBlockInfo[entName]['displayField']],
            id: entityArray[i]['id']
          };
          break;
        }
      }
    }
    return result;
  }

  CRM.$(function($) {

    $('body').on('change', "input[id*='[operation]']", function() {
      var originalHtml = $(this).prevAll('span.action_label').html();
      if ($(this).is(":checked")) {
        $(this).prevAll('span.action_label').html(originalHtml.replace('({/literal}{ts}overwrite{/ts}{literal})', '({/literal}{ts}add new{/ts}{literal})'));
      }
      else {
        $(this).prevAll('span.action_label').html(originalHtml.replace('({/literal}{ts}add new{/ts}{literal})', '({/literal}{ts}overwrite{/ts}{literal})'));
      }
    });

    $('table td input.form-checkbox').each(function() {
      var ele = null;
      var element = $(this).attr('id').split('_',3);

      switch ( element['1'] ) {
        case 'addressee':
          ele = '#' + element['0'] + '_' + element['1'];
          break;

         case 'email':
         case 'postal':
           ele = '#' + element['0'] + '_' + element['1'] + '_' + element['2'];
           break;
      }

      if( ele ) {
        $(this).on('click', function() {
          var val = $(this).prop('checked');
          $('input' + ele + ', input' + ele + '_custom').prop('checked', val);
        });
      }
    });

    // Show/hide matching data rows
    $('.toggle_equal_rows').click(function() {
      $('tr.merge-row-equal').toggle();
    });

  });

</script>
{/literal}

{* process the dupe contacts *}
{include file="CRM/common/dedupe.tpl"}
