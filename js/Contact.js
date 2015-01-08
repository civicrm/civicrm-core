/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

/**
 * This function is used to set primary and billing status to a location block.  
 * 
 * Upon calling this function, the is primary or is billing checkbox within the target location block will be checked while the same checkbox
 * in all the other location blocks will be unchecked. This function is used to enforce the rule that at a time only one location
 * block can be considered primary or billing. 
 * 
 * @access public
 * @param formname Name of the form.
 * @param locid Serial number of the location block.
 * @param maxLocs How many location blocks are offered
 * @param type is_primary or is_billing
 * @return none
 */
function location_onclick(formname, locid, maxLocs, type) {
    /*
    if (locid == 1) {
	// don't need to confirm selecting 1st location as primary
        return;
    }
    */
    var changedKey = 'location[' + locid + '][' + type +']';
    
    var notSet = [];
    for (var j = 1; j <= maxLocs; j++) {
        if (j != locid) {
            notSet.push(j);
        }
    }

    if (document.forms[formname].elements[changedKey].checked) {
	var confirmText ;
	if ( type == 'is_primary' ) {
		confirmText = 'Do you want to make this the primary location?';
	} else {
		confirmText = 'Do you want to make this the billing location?';
	}
	
        if ( confirm( confirmText ) == true ) {
            for (var i = 0; i < notSet.length; i++) {
                otherKey = 'location[' + notSet[i] + '][' + type + ']';
                document.forms[formname].elements[otherKey].checked = null;
            }
        } else {
            document.forms[formname].elements[changedKey].checked = null;
        }
    } 	
    
}
