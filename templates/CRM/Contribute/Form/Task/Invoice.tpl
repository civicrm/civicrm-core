{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
      {include file="CRM/Contribute/Form/Task.tpl"}
</div>
<div id="help">
    {ts}You may choose to email invoice to contributors OR download a PDF file containing one invoice per page to your local computer by clicking <strong>Process Invoice(s)</strong>. Your browser may display the file for you automatically, or you may need to open it for printing using any PDF reader (such as Adobe&reg; Reader).{/ts}
</div>

<table class="form-layout-compressed">  
  {if $smarty.get.select == 'email'}
    {literal}
      <script type="text/javascript">
        cj(document).ready(function(){
        cj('#{/literal}{$form.output.pdf_invoice.id}{literal}').prop('disabled', true);
        cj('#{/literal}{$form.output.email_invoice.id}{literal}').attr('checked', true);
        cj('#comment').attr('style', '');    
        }) 
      </script>
    {/literal}
  {/if}

 {if $smarty.get.select == 'pdf'}
   {literal}
     <script type="text/javascript">
      cj(document).ready(function(){
       cj('#{/literal}{$form.output.email_invoice.id}{literal}').prop('disabled', true);
       cj('#{/literal}{$form.output.pdf_invoice.id}{literal}').attr('checked', true);
      })
     </script>
   {/literal}
 {/if}
  <tr>
    <td>{$form.output.email_invoice.html}</td>
  </tr>
  <tr id="comment" style="display:none;">
    <td>{$form.email_comment.label}{$form.email_comment.html} </td>
  </tr>
  <tr>
    <td>{$form.output.pdf_invoice.html}</td>
  </tr>
  <tr id="selectPdfFormat" style="display: none;">
    <td>{$form.pdf_format_id.html} {$form.pdf_format_id.label} </td>
  </tr>
</table>

<div class="spacer"></div>
<div class="form-item">
 {$form.buttons.html}
</div>
