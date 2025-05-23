{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<details class="crm-accordion-bold crm-notesBlock-accordion">
 <summary>

    {$title}
  </summary>
  <div class="crm-accordion-body" id="notesBlock">
   <table class="form-layout-compressed">
     <tr>
       <td colspan=3>{$form.subject.label}<br  >
        {$form.subject.html}</td>
     </tr>
     <tr>
       <td colspan=3>{$form.note.label}<br />
        {$form.note.html}
       </td>
     </tr>
   </table>
 </div>
</details>
