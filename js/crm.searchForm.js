// http://civicrm.org/licensing

function countSelectedCheckboxes(fldPrefix, form) {
  fieldCount = 0;
  for (i = 0; i < form.elements.length; i++) {
    fpLen = fldPrefix.length;
    if (form.elements[i].type == 'checkbox' && form.elements[i].name.slice(0, fpLen) == fldPrefix && form.elements[i].checked == true) {
      fieldCount++;
    }
  }
  return fieldCount;
}

/**
 * This function is used to check if any action is selected and also to check if any contacts are checked.
 *
 * @access public
 * @param fldPrefix - common string which precedes unique checkbox ID and identifies field as
 *                    belonging to the resultset's checkbox collection
 * @param form - name of form that checkboxes are part of
 * Sample usage: onClick="javascript:checkPerformAction('chk_', myForm );"
 *
 */
function checkPerformAction(fldPrefix, form, taskButton, selection) {
  var cnt;
  var gotTask = 0;

  // taskButton TRUE means we don't need to check the 'task' field - it's a button-driven task
  if (taskButton == 1) {
    gotTask = 1;
  }
  else {
    if (document.forms[form].task.selectedIndex) {
      //force user to select all search contacts, CRM-3711
      if (document.forms[form].task.value == 13 || document.forms[form].task.value == 14) {
        var toggleSelect = document.getElementsByName('toggleSelect');
        if (toggleSelect[0].checked || document.forms[form].radio_ts[0].checked) {
          return true;
        }
        else {
          alert("Please select all contacts for this action.\n\nTo use the entire set of search results, click the 'all records' radio button.");
          return false;
        }
      }
      gotTask = 1;
    }
  }

  if (gotTask == 1) {
    // If user wants to perform action on ALL records and we have a task, return (no need to check further)
    if (document.forms[form].radio_ts[0].checked) {
      return true;
    }

    cnt = (selection == 1) ? countSelections() : countSelectedCheckboxes(fldPrefix, document.forms[form]);
    if (!cnt) {
      alert("Please select one or more contacts for this action.\n\nTo use the entire set of search results, click the 'all records' radio button.");
      return false;
    }
  }
  else {
    alert("Please select an action from the drop-down menu.");
    return false;
  }
}

/**
 * Function to enable task action select
 */
function toggleTaskAction(status) {
  var radio_ts = document.getElementsByName('radio_ts');
  if (!radio_ts[1]) {
    radio_ts[0].checked = true;
  }
  if (radio_ts[0].checked || radio_ts[1].checked) {
    status = true;
  }

  var formElements = ['task', 'Go', 'Print'];
  for (var i = 0; i < formElements.length; i++) {
    var element = document.getElementById(formElements[i]);
    if (element) {
      if (status) {
        element.disabled = false;
      }
      else {
        element.disabled = true;
      }
    }
  }
}
