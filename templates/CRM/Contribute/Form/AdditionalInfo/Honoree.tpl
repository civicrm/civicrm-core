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
{* this template is used for adding/editing Honoree Information *}
<div id="id-honoree" class="section-shown crm-contribution-additionalinfo-honoree-form-block">
      <table class="form-layout-compressed">
         {if $form.honor_type_id.html}
      <tr class="crm-contribution-form-block-honor_type_id">
         <td colspan="3">
      {$form.honor_type_id.html}
      <span class="crm-clear-link">(<a href="#" title="unselect" onclick="unselectRadio('honor_type_id', '{$form.formName}'); enableHonorType(); return false;">{ts}clear{/ts}</a>)</span><br />
      <span class="description">{ts}Please include the name, and / or email address of the person you are honoring.{/ts}</span>
         </td>
      </tr>
         {/if}
   <tr id="honorType">
      <td>{$form.honor_prefix_id.html}<br />
         <span class="description">{$form.honor_prefix_id.label}</span></td>
      <td>{$form.honor_first_name.html}<br />
         <span class="description">{$form.honor_first_name.label}</span></td>
      <td>{$form.honor_last_name.html}<br />
         <span class="description">{$form.honor_last_name.label}</span></td>
   </tr>
   <tr id="honorTypeEmail">
      <td></td>
      <td colspan="2">{$form.honor_email.html}<br />
                <span class="description">{$form.honor_email.label}</td>
         </tr>
      </table>
</div>
{if $form.honor_type_id.html}
{literal}
<script type="text/javascript">
   enableHonorType();
   function enableHonorType( ) {
      var element = document.getElementsByName("honor_type_id");
      for (var i = 0; i < element.length; i++ ) {
  var isHonor = false;
  if ( element[i].checked == true ) {
      var isHonor = true;
      break;
  }
      }
      if ( isHonor ) {
   cj('#honorType').show();
   cj('#honorTypeEmail').show();
      } else {
   cj('#honor_first_name').val('');
   cj('#honor_last_name').val('');
   cj('#honor_email').val('');
   cj('#honor_prefix_id').val('');
   cj('#honorType').hide();
   cj('#honorTypeEmail').hide();
      }
   }
</script>
{/literal}
{/if}
