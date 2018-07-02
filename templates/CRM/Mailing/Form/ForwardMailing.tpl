{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
<div class="crm-block crm-mailing-forward-form-block">
<br />
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div><br />
<table class="form-layout">
<tr class="crm-mailing-forward-form-block-fromEmail"><td class="label">From</td><td>{$fromEmail}</td></tr>
<tr><td colspan="2">{ts}Please enter up to 5 email addresses to receive the mailing.{/ts}</td></tr>
<tr class="crm-mailing-forward-form-block-email_0"><td class="label" >{$form.email_0.label}</td><td>{$form.email_0.html}</td></tr>
<tr class="crm-mailing-forward-form-block-email_1"><td class="label" >{$form.email_1.label}</td><td>{$form.email_1.html}</td></tr>
<tr class="crm-mailing-forward-form-block-email_2"><td class="label" >{$form.email_2.label}</td><td>{$form.email_2.html}</td></tr>
<tr class="crm-mailing-forward-form-block-email_3"><td class="label" >{$form.email_3.label}</td><td>{$form.email_3.html}</td></tr>
<tr class="crm-mailing-forward-form-block-email_4"><td class="label" >{$form.email_4.label}</td><td>{$form.email_4.html}</td></tr>

</table>
<div id="comment_show">
    <a href="#" class="button" onclick="cj('#comment_show').hide(); cj('#comment').show(); document.getElementById('forward_comment').focus(); return false;"><span>&raquo; {ts}Add Comment{/ts}</span></a>
</div><div class="spacer"></div>
<div id="comment" style="display:none">
            <table class="form-layout">
            <tr class="crm-mailing-forward-form-block-forward_comment"><td><a href="#" onclick="cj('#comment').hide(); cj('#comment_show').show(); return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}close section{/ts}"/></a>
                <label>{$form.forward_comment.label}</label></td>
                <td>{$form.forward_comment.html}<br /><br />
              &nbsp;{$form.html_comment.html}<br /></td>
             </tr>
            </table>
</div>
<br />
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

