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
{* this template is used for adding/editing/viewing relationships  *}

  {if $action eq 4 } {* action = view *}
    <div class="crm-block crm-content-block crm-relationship-view-block">
      <table class="crm-info-panel">
        {foreach from=$viewRelationship item="row"}
          <tr>
            <td class="label">{$row.relation}</td>
            <td><a class="no-popup" href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.cid`"}">{$row.name}</a></td>
          </tr>
          {if $isCurrentEmployer}
            <tr><td class="label">{ts}Current Employee?{/ts}</td><td>{ts}Yes{/ts}</td></tr>
          {/if}
          {if $row.start_date}
            <tr><td class="label">{ts}Start Date{/ts}</td><td>{$row.start_date|crmDate}</td></tr>
          {/if}
          {if $row.end_date}
            <tr><td class="label">{ts}End Date{/ts}</td><td>{$row.end_date|crmDate}</td></tr>
          {/if}
          {if $row.description}
            <tr><td class="label">{ts}Description{/ts}</td><td>{$row.description}</td></tr>
          {/if}
          {foreach from=$viewNote item="rec"}
            {if $rec }
              <tr><td class="label">{ts}Note{/ts}</td><td>{$rec}</td></tr>
            {/if}
          {/foreach}
          <tr>
            <td class="label"><label>{ts}Permissions{/ts}</label></td>
            <td>
              {if $row.is_permission_a_b or $row.is_permission_b_a}
                {if $row.is_permission_a_b}
                  <div>
                  {if $row.rtype EQ 'a_b' AND $is_contact_id_a}
                    {ts 1=$displayName 2=$row.display_name}<strong>%1</strong> can view and update information about %2.{/ts}
                  {else}
                    {ts 1=$row.display_name 2=$displayName}<strong>%1</strong> can view and update information about %2.{/ts}
                  {/if}
                  </div>
                {/if}
                {if $row.is_permission_b_a}
                  <div>
                  {if $row.rtype EQ 'a_b' AND $is_contact_id_a}
                    {ts 1=$row.display_name 2=$displayName}<strong>%1</strong> can view and update information about %2.{/ts}
                  {else}
                    {ts 1=$displayName 2=$row.display_name}<strong>%1</strong> can view and update information about %2.{/ts}
                  {/if}
                  </div>
                {/if}
              {else}
                {ts}None{/ts}
              {/if}
            </td>
          </tr>
          <tr><td class="label">{ts}Status{/ts}</td><td>{if $row.is_active}{ts}Enabled{/ts}{else}{ts}Disabled{/ts}{/if}</td></tr>
        {/foreach}
      </table>
      {include file="CRM/Custom/Page/CustomDataView.tpl"}
    </div>
  {/if}

  {if $action eq 2 or $action eq 1} {* add and update actions *}
    <div class="crm-block crm-form-block crm-relationship-form-block">
      <table class="form-layout-compressed">
        <tr class="crm-relationship-form-block-relationship_type_id">
          <td class="label">{$form.relationship_type_id.label}</td>
          <td>{$form.relationship_type_id.html}</td>
        </tr>
        <tr class="crm-relationship-form-block-related_contact_id">
          <td class="label">{$form.related_contact_id.label}</td>
          <td>{$form.related_contact_id.html}</td>
        </tr>
        <tr class="crm-relationship-form-block-is_current_employer" style="display:none;">
          <td class="label">{$form.is_current_employer.label}</td>
          <td>{$form.is_current_employer.html}</td>
        </tr>
        <tr class="crm-relationship-form-block-start_date">
          <td class="label">{$form.start_date.label}</td>
          <td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}<span>{$form.end_date.label} {include file="CRM/common/jcalendar.tpl" elementName=end_date}</span><br />
            <span class="description">{ts}If this relationship has start and/or end dates, specify them here.{/ts}</span></td>
        </tr>
        <tr class="crm-relationship-form-block-description">
          <td class="label">{$form.description.label}</td>
          <td>{$form.description.html}</td>
        </tr>
        <tr class="crm-relationship-form-block-note">
          <td class="label">{$form.note.label}</td>
          <td>{$form.note.html}</td>
        </tr>
        <tr class="crm-relationship-form-block-is_permission_a_b">
          {capture assign="contact_b"}{if $action eq 1}{ts}selected contact(s){/ts}{else}{$display_name_b}{/if}{/capture}
          <td class="label"><label>{ts}Permissions{/ts}</label></td>
          <td>
            {$form.is_permission_a_b.html}
            {ts 1=$display_name_a 2=$contact_b}<strong>%1</strong> can view and update information about %2.{/ts}
          </td>
        </tr>
        <tr class="crm-relationship-form-block-is_permission_b_a">
          <td class="label"></td>
          <td>
            {$form.is_permission_b_a.html}
            {ts 1=$contact_b|ucfirst 2=$display_name_a}<strong>%1</strong> can view and update information about %2.{/ts}
          </td>
        </tr>
        <tr class="crm-relationship-form-block-is_active">
          <td class="label">{$form.is_active.label}</td>
          <td>{$form.is_active.html}</td>
        </tr>
      </table>
      <div id="customData"></div>
      <div class="spacer"></div>
    </div>
  {/if}
  {if ($action EQ 1) OR ($action EQ 2) }
    {*include custom data js file *}
    {include file="CRM/common/customData.tpl"}
    <script type="text/javascript">
      {literal}
      CRM.$(function($) {
        var
          $form = $("form.{/literal}{$form.formClass}{literal}"),
          relationshipData = {/literal}{$relationshipData|@json_encode}{literal};
        $('[name=relationship_type_id]', $form).change(function() {
          var
            val = $(this).val(),
            $contactField = $('#related_contact_id[type=text]', $form);
          if (!val && $contactField.length) {
            $contactField
              .prop('disabled', true)
              .attr('placeholder', {/literal}'{ts escape='js'}- first select relationship type -{/ts}'{literal})
              .change();
          }
          else if (val) {
            var
              pieces = val.split('_'),
              rType = pieces[0],
              source = pieces[1], // a or b
              target = pieces[2], // b or a
              contact_type = relationshipData[rType]['contact_type_' + target],
              contact_sub_type = relationshipData[rType]['contact_sub_type_' + target];
            // ContactField only exists for ADD action, not update
            if ($contactField.length) {
              var api = {params: {}};
              if (contact_type) {
                api.params.contact_type = contact_type;
              }
              if (contact_sub_type) {
                api.params.contact_sub_type = contact_sub_type;
              }
              $contactField
                .val('')
                .prop('disabled', false)
                .data('api-params', api)
                .data('user-filter', {})
                .attr('placeholder', relationshipData[rType]['placeholder_' + target])
                .change();
            }

            // Show/hide employer field
            $('.crm-relationship-form-block-is_current_employer', $form).toggle(rType === {/literal}'{$employmentRelationship}'{literal});

            // Swap the permission checkboxes to match selected relationship direction
            $('#is_permission_a_b', $form).attr('name', 'is_permission_' + source + '_' + target);
            $('#is_permission_b_a', $form).attr('name', 'is_permission_' + target + '_' + source);

            CRM.buildCustomData('Relationship', rType);
          }
        }).change();
      });
      {/literal}
    </script>
  {/if}

  {if $action eq 8}
    <div class="status">
      {ts}Are you sure you want to delete this Relationship?{/ts}
    </div>
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
