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
<div class="crm-accordion-wrapper crm-demographics-accordion collapsed">
 <div class="crm-accordion-header">
    {$title}
  </div><!-- /.crm-accordion-header -->
  <div id="demographics" class="crm-accordion-body">
  <div class="form-item">
        <span class="label">{$form.gender_id.label}</span>

  <span class="value">
        {$form.gender_id.html}
        </span>
  </div>
  <div class="form-item">
        <span class="label">{$form.birth_date.label}</span>
        <span class="value">{include file="CRM/common/jcalendar.tpl" elementName=birth_date}</span>
  </div>
  <div class="form-item">
       {$form.is_deceased.html}
       {$form.is_deceased.label}
  </div>
  <div id="showDeceasedDate" class="form-item">
       <span class="label">{$form.deceased_date.label}</span>
       <span class="value">{include file="CRM/common/jcalendar.tpl" elementName=deceased_date}</span>
  </div>
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

{literal}
<script type="text/javascript">
    showDeceasedDate( );
    function showDeceasedDate( )
    {
        if ( cj("#is_deceased").is(':checked') ) {
            cj("#showDeceasedDate").show( );
        } else {
    cj("#showDeceasedDate").hide( );
         cj("#deceased_date").val('');
        }
    }
</script>
{/literal}
