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
{* Highlight the required field during import (included within a <script>)*}
{literal}
CRM.$(function($) {
  var highlightedFields = ["{/literal}{'","'|implode:$highlightedFields}{literal}"];
  $.each(highlightedFields, function() {
    $('select[id^="mapper"][id$="_0"] option[value='+ this + ']').append(' *').css({"color":"#FF0000"});
  });
  {/literal}{if $relationship}{literal}
  var highlightedRelFields = {/literal}{$highlightedRelFields|@json_encode}{literal};
  function highlight() {
    var select, fields = highlightedRelFields[$(this).val()];
    if (fields) {
      select = $(this).next();
      $.each(fields, function() {
        $('option[value='+ this + ']', select).append(' *').css({"color":"#FF0000"});
      });
    }
  }
  $('select[id^="mapper"][id$="_0"]').each(highlight).click(highlight);
  {/literal}{/if}{literal}
});
{/literal}
