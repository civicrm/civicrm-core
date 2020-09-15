{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if !empty($form.address.$blockId.county_id)}
   <tr>
     <td colspan="2">
        {$form.address.$blockId.county_id.label}<br />
        {$form.address.$blockId.county_id.html}<br />
     </td>
   </tr>
{/if}
