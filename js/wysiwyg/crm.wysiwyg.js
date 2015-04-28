// https://civicrm.org/licensing
(function($, _) {
  // This defines an interface which by default only handles plain textareas
  // A wysiwyg implementation can extend this by overriding as many of these functions as needed
  CRM.wysiwyg = {
    supportsFileUploads: false,
    create: _.noop,
    destroy: _.noop,
    updateElement: _.noop,
    getVal: function(item) {
      return $(item).val();
    },
    setVal: function(item, val) {
      return $(item).val(val);
    },
    insert: function(item, text) {
      CRM.wysiwyg._insertIntoTextarea(item, text);
    },
    focus: function(item) {
      $(item).focus();
    },
    // Fallback function to use when a wysiwyg has not been initialized
    _insertIntoTextarea: function(item, text) {
      var origVal = $(item).val();
      var origPos = item[0].selectionStart;
      var newVal = origVal + text;
      $(item).val(newVal);
      var newPos = (origPos + text.length);
      item[0].selectionStart = newPos;
      item[0].selectionEnd = newPos;
      $(item).triggerHandler('change');
      CRM.wysiwyg.focus(item);
    },
    createCollapsed: function(item) {
      $(item)
        .hide()
        .after('<div class="replace-plain" tabindex="0"></div>')
        .on('blur', function () {
          CRM.wysiwyg.destroy(item);
          $(item).hide().next('.replace-plain').show().html($(item).val());
        });
      $(item).next('.replace-plain').attr('title', ts('Click to edit')).on('click keypress', function () {
        $(item).show().next('.replace-plain').hide();
        CRM.wysiwyg.create(item);
      });
    }
  };
})(CRM.$, CRM._);
