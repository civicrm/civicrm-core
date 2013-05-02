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
{if $config->languageLimit|@count >= 2 and $translatePermission }
<a href="#" onclick="loadDialog('{crmURL p='civicrm/i18n' q="reset=1&table=$table&field=$field&id=$id&snippet=1&context=dialog" h=0}', '{$field}'); return false;"><img src="{$config->resourceBase}i/langs.png" /></a><div id="locale-dialog_{$field}" style="display:none"></div>

{literal}
<script type="text/javascript">
function loadDialog( url, fieldName ) {
 cj.ajax({
         url: url,
         success: function( content ) {
             cj("#locale-dialog_" +fieldName ).show( ).html( content ).dialog({
                 modal       : true,
      width       : 290,
      height      : 290,
      resizable   : true,
      bgiframe    : true,
      overlay     : { opacity: 0.5, background: "black" },
      beforeclose : function(event, ui) {
                     cj(this).dialog("destroy");
                       }
             });
         }
      });
}
</script>
{/literal}
{/if}
