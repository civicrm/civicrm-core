/*
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
*/

function selectActivity(i)
{
  // deselect current selection
  j = document.forms["Report"].currentSelection.value;
  ele = document.getElementById("civicase-audit-activity-" + j);
  ele.className = "activity";
  ele = document.getElementById("civicase-audit-header-" + j);
  ele.style.display = "none";
  ele = document.getElementById("civicase-audit-body-" + j);
  ele.style.display = "none";

  if ( i != 1 ) {
      cj('#civicase-audit-activity-1').removeClass('activity selected');
      cj('#civicase-audit-activity-1').addClass('activity');
      cj('#civicase-audit-header-1').css({"display":"none"});
      cj('#civicase-audit-body-1').css({"display":"none"});
        }

  // select selected one
  ele = document.getElementById("civicase-audit-activity-" + i);
  ele.className = "activity selected";
  ele = document.getElementById("civicase-audit-header-" + i);
  ele.style.display = "block";
  ele = document.getElementById("civicase-audit-body-" + i);
  ele.style.display = "block";
  document.forms["Report"].currentSelection.value = i;
}
