{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-accordion-wrapper crm-notesBlock-accordion collapsed">
 <div class="crm-accordion-header">

    {$title}
  </div><!-- /.crm-accordion-header -->
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
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
