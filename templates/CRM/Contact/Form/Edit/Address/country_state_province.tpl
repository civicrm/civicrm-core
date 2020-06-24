{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<tr><td colspan="3" style="padding:0;">
<table class="crm-address-element">
<tr>
   {if !empty($form.address.$blockId.country_id)}
     <td>
        {$form.address.$blockId.country_id.label}<br />
        {$form.address.$blockId.country_id.html}
     </td>
   {/if}
   {if !empty($form.address.$blockId.state_province_id)}
     <td>
        {$form.address.$blockId.state_province_id.label}<br />
        {$form.address.$blockId.state_province_id.html}
     </td>
   {/if}
   <td colspan="2">&nbsp;&nbsp;</td>
</tr>
</table>
</td></tr>
