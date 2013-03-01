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
file contains function to add and remove overlay during ajax request,
this is defined in template because we want to localize the 
display message message.
*}
{literal}
<script type="text/javascript">
/**
 * function to add overlay during ajax action
 */
function addCiviOverlay( element ) {
  var message = {/literal}"{ts escape='js'}Please wait...{/ts}"{literal}; 
  cj( element ).block({
    message: message,
    theme: true,
    draggable: false
  });
}

/**
 * function to remove overlay after ajax action
 */
function removeCiviOverlay( element ) {
  cj( element ).unblock();
}

</script>
{/literal}
