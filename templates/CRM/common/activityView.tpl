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
{literal}
<script type="text/javascript">
function {/literal}{$list}{literal}viewActivity(activityID, contactID, list) {
  if (list) {
    list = "-" + list;
  }

  cj("#view-activity" + list ).show( );

  cj("#view-activity" + list ).dialog({
    title: {/literal}"{ts escape="js"}View Activity{/ts}"{literal},
    modal: true,
    width : "680px", // don't remove px
    height: "560",
    resizable: true,
    bgiframe: true,
    overlay: {
      opacity: 0.5,
      background: "black"
    },

    beforeclose: function(event, ui) {
      cj(this).dialog("destroy");
    },

    open:function() {
      cj("#activity-content" + list , this).html("");
      var viewUrl = {/literal}"{crmURL p='civicrm/case/activity/view' h=0 q="snippet=4" }"{literal};
      cj("#activity-content" + list , this).load( viewUrl + "&cid="+contactID + "&aid=" + activityID + "&type="+list);
    },

    buttons: {
      "{/literal}{ts escape="js"}Done{/ts}{literal}": function() {
        cj(this).dialog("destroy");
      }
    }
  });
}
</script>
{/literal}