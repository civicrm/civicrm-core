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

{*
  Usage: CRM_Core_Region::instance('...')->add(array(
  'template' => 'CRM/common/LogButton.tpl',
  'instance_id' => CRM_Report_Utils_Report::getInstanceIDForValue('logging/contact/summary'),
  'css_class' => 'hrqual-revision-link',
  'table_name' => 'my_table',
  'contact_id' => 123,
  ));

  Note: This file is used by CivHR
*}

<a class="css_right {$snippet.css_class}" href="#" title="{ts}View Revisions{/ts}">View Revisions</a>
<div class="dialog-{$snippet.css_class}">
  <div class="revision-content"></div>
</div>

{literal}
<script type="text/javascript">
cj(document).on("click", ".{/literal}{$snippet.css_class}{literal}", function() {
  cj(".dialog-{/literal}{$snippet.css_class}{literal}").show( );
  cj(".dialog-{/literal}{$snippet.css_class}{literal}").dialog({
    title: "{/literal}{ts}Revisions{/ts}{literal}",
    modal: true,
    width: "680px",
    bgiframe: true,
    overlay: { opacity: 0.5, background: "black" },
    open:function() {
      var ajaxurl = {/literal}'{crmURL p="civicrm/report/instance/`$snippet.instance_id`" h=0 }'{literal};
      cj.ajax({
        data: "reset=1&snippet=4&section=2&altered_contact_id_op=eq&altered_contact_id_value={/literal}{$snippet.contact_id}{literal}&log_type_table_op=has&log_type_table_value={/literal}{$snippet.table_name}{literal}",
        url:  ajaxurl,
        success: function (data) {
          cj(".dialog-{/literal}{$snippet.css_class}{literal} .revision-content").html(data);
          if (!cj(".dialog-{/literal}{$snippet.css_class}{literal} .revision-content .report-layout").length) {
            cj(".dialog-{/literal}{$snippet.css_class}{literal} .revision-content").html("Sorry, couldn't find any revisions.");
          }
        }
      });
    },
    buttons: {
      "Done": function() {
        cj(this).dialog("destroy");
      }
    }
  });
});
</script>
{/literal}
