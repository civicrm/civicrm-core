{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing badge layouts *}
<h3>{if $action eq 1}{ts}New Badge Layout{/ts}{elseif $action eq 2}{ts}Edit Badge Layout{/ts}{else}{ts}Delete Badge Layout{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-badge-layout-form-block">
  {if $action eq 8}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
    </div>
  {else}
    <table class="form-layout-compressed">
      <tr class="crm-badge-layout-form-block-title">
        <td class="label">{$form.title.label}</td>
        <td>{$form.title.html}</td>
      </tr>
      <tr class="crm-badge-layout-form-block-label_format_name">
        <td class="label">{$form.label_format_name.label}</td>
        <td>{$form.label_format_name.html} {help id="id-label_format" file="CRM/Badge/Form/Layout.hlp"}</td>
      </tr>
      <tr class="crm-badge-layout-form-block-description">
        <td class="label">{$form.description.label}</td>
        <td>{$form.description.html}</td>
      </tr>
      <tr class="crm-badge-layout-form-block-image_1">
        <td class="label">{$form.image_1.label}</td>
        <td>
         <table>
           <tr>
            <td>{$form.image_1.html}
               <a href="#" class="crm-hover-button clear-image" title="{ts}Clear{/ts}"><i class="crm-i fa-times" aria-hidden="true"></i></a>
             <br/>
             <span class="description">{ts}Click above and select a file by double clicking on it.{/ts}</span>
            </td>
            <td>
             {$form.width_image_1.html}<br/>{$form.width_image_1.label}
            </td>
           <td>
              {$form.height_image_1.html}<br/>{$form.height_image_1.label}
            </td>
           </tr>
         </table>
        </td>
      </tr>
      <tr class="crm-badge-layout-form-block-image_2">
        <td class="label">{$form.image_2.label}</td>
        <td>
         <table>
          <tr>
           <td>{$form.image_2.html}
              <a href="#" class="crm-hover-button clear-image" title="{ts}Clear{/ts}"><i class="crm-i fa-times" aria-hidden="true"></i></a>
            <br/>
            <span class="description">{ts}Click above and select a file by double clicking on it.{/ts}</span>
           </td>
           <td>
            {$form.width_image_2.html}<br/>{$form.width_image_2.label}
           </td>
           <td>
            {$form.height_image_2.html}<br/>{$form.height_image_2.label}
           </td>
          </tr>
         </table>
        </td>
      </tr>
      <tr class="crm-badge-layout-form-block-participant_image">
        <td class="label">{$form.show_participant_image.label}</td>
        <td>
         <table>
           <tr>
            <td>{$form.show_participant_image.html}
             <br/>
             <span class="description">{ts}Select this option if you want to use a contact's image on their name badge.{/ts}</span>
            </td>
            <td>
             {$form.width_participant_image.html}<br/>{$form.width_participant_image.label}
            </td>
           <td>
              {$form.height_participant_image.html}<br/>{$form.height_participant_image.label}
            </td>
           <td>
              {$form.alignment_participant_image.html}<br/>{$form.alignment_participant_image.label}
            </td>
           </tr>
         </table>
        </td>
      </tr>
      <tr class="crm-badge-layout-form-block-elements">
        <td class="label"><label>{ts}Elements{/ts}</label></td>
        <td>
          <table class="form-layout-compressed">
            <tr>
              <td>{ts}Row{/ts}</td>
              <td>{ts}Label{/ts}</td>
              <td>{ts}Font{/ts}</td>
              <td>{ts}Size{/ts}</td>
              <td>{ts}Style{/ts}</td>
              <td>{ts}Alignment{/ts}</td>
            </tr>
            {section name='i' start=1 loop=$rowCount}
              {assign var='rowNumber' value=$smarty.section.i.index}
              <tr>
                <td>#{$rowNumber}</td>
                <td>{$form.token.$rowNumber.html|crmAddClass:twenty}</td>
                <td>{$form.font_name.$rowNumber.html}</td>
                <td>{$form.font_size.$rowNumber.html}</td>
                <td>{$form.font_style.$rowNumber.html}</td>
                <td>{$form.text_alignment.$rowNumber.html}</td>
              </tr>
            {/section}
          </table>
        </td>
      </tr>
      <tr class="crm-badge-layout-form-block-add_barcode">
        <td class="label">{$form.add_barcode.label}</td>
        <td>{$form.add_barcode.html}&nbsp;&nbsp;&nbsp;{ts}of type{/ts}&nbsp;&nbsp;&nbsp;
          {$form.barcode_type.html}&nbsp;&nbsp;&nbsp;{ts}on{/ts}&nbsp;&nbsp;&nbsp;{$form.barcode_alignment.html}
        </td>
      </tr>
      <tr class="crm-badge-layout-form-block-is_active">
        <td class="label">{$form.is_active.label}</td>
        <td>{$form.is_active.html}</td>
      </tr>
      <tr class="crm-badge-layout-form-block-is_default">
        <td class="label">{$form.is_default.label}</td>
        <td>{$form.is_default.html}</td>
      </tr>
      <tr class="crm-badge-layout-form-block-is_reserved">
        <td class="label">{$form.is_reserved.label}</td>
        <td>{$form.is_reserved.html}</td>
      </tr>
    </table>
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
