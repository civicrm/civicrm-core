// https://civicrm.org/licensing
(function($, _) {
  // This defines an interface which by default only handles plain textareas
  // A wysiwyg implementation can extend this by overriding as many of these functions as needed
  CRM.wysiwyg = {
    supportsFileUploads: !!CRM.config.wysisygScriptLocation,
    create: function(item) {
      var ret = $.Deferred();
      // Lazy-load the wysiwyg js
      if (CRM.config.wysisygScriptLocation) {
        CRM.loadScript(CRM.config.wysisygScriptLocation).done(function() {
          CRM.wysiwyg._create(item).done(function() {
            ret.resolve();
          });
        });
      } else {
        ret.resolve();
      }
      return ret;
    },
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
      var itemObj = $(item);
      var origVal = itemObj.val();
      var origStart = itemObj[0].selectionStart;
      var origEnd = itemObj[0].selectionEnd;
      var newVal = origVal.substring(0, origStart) + text + origVal.substring(origEnd);
      itemObj.val(newVal);
      var newPos = (origStart + text.length);
      itemObj[0].selectionStart = newPos;
      itemObj[0].selectionEnd = newPos;
      itemObj.triggerHandler('change');
      CRM.wysiwyg.focus(item);
    },
    // Create a "collapsed" textarea that expands into a wysiwyg when clicked
    createCollapsed: function(item) {
      $(item)
        .hide()
        .on('blur', function () {
          CRM.wysiwyg.destroy(item);
          $(item).hide().next('.replace-plain').show().html($(item).val());
        })
        .after('<div class="replace-plain" tabindex="0"></div>');
      $(item).next('.replace-plain')
        .attr('title', ts('Click to edit'))
        .html($(item).val())
        .on('click keypress', function (e) {
          // Stop browser from opening clicked links
          e.preventDefault();
          $(item).show().next('.replace-plain').hide();
          CRM.wysiwyg.create(item);
        });
    }
  };
})(CRM.$, CRM._);
