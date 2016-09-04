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
{* this template is used for adding/editing badge layouts *}
<h3>{if $action eq 1}{ts}New Badge Layout{/ts}{elseif $action eq 2}{ts}Edit Badge Layout{/ts}{else}{ts}Delete Badge Layout{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-badge-layout-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  {if $action eq 8}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
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
        <td>{$form.label_format_name.html} {help id="id-label_format"}</td>
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
               <a href="#" class="crm-hover-button clear-image" title="{ts}Clear{/ts}"><i class="crm-i fa-times"></i></a>
             <br/>
             <span class="description">{ts}Click above and select a file by double clicking on it.{/ts}</span>
            </td>
            <td>
             {$form.width_image_1.html}<br/>{$form.width_image_1.label}
            </td>
           <td>
              {$form.height_image_1.html}</br>{$form.height_image_1.label}
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
              <a href="#" class="crm-hover-button clear-image" title="{ts}Clear{/ts}"><i class="crm-i fa-times"></i></a>
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
              {$form.height_participant_image.html}</br>{$form.height_participant_image.label}
            </td>
           <td>
              {$form.alignment_participant_image.html}</br>{$form.alignment_participant_image.label}
            </td>
           </tr>
         </table>
        </td>
      </tr>
      <tr class="crm-badge-layout-form-block-elements">
        <td class="label">{ts}Elements{/ts}</td>
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
