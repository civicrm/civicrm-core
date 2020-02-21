// https://civicrm.org/licensing
(function($, _) {

  var instances = {};

  function getInstance(item) {
    var name = $(item).attr("name"),
      id = $(item).attr("id");
    if (name && instances[name]) {
      return instances[name];
    }
    if (id && instances[id]) {
      return instances[id];
    }
  }

  CRM.wysiwyg.supportsFileUploads = true;

  CRM.wysiwyg._create = function(item) {
    var deferred = $.Deferred();

    function onReady(editor) {
      var debounce,
        name = $(editor.sourceElement).attr('name') || $(editor.sourceElement).attr('id');

      instances[name] = editor;

      editor.on('destroy', function(e) {
        var name = $(e.source.sourceElement).attr('name') || $(e.source.sourceElement).attr('id');
        delete instances[name];
      });

      // FIXME: Convert CKEditor4 events
      // editor.on('focus', function() {
      //   $(item).trigger('focus');
      // });
      // editor.on('blur', function() {
      //   editor.updateElement();
      //   $(item).trigger("blur");
      //   $(item).trigger("change");
      // });
      // editor.on('insertText', function() {
      //   $(item).trigger("keypress");
      // });
      // _.each(['key', 'pasteState'], function(evName) {
      //   editor.on(evName, function(evt) {
      //     if (debounce) clearTimeout(debounce);
      //     debounce = setTimeout(function() {
      //       editor.updateElement();
      //       $(item).trigger("change");
      //     }, 50);
      //   });
      // });
      // editor.on('pasteState', function() {
      //   $(item).trigger("paste");
      // });
      // // Hide CiviCRM menubar when editor is fullscreen
      // editor.on('maximize', function (e) {
      //   $('#civicrm-menu').toggle(e.data === 2);
      // });
      $(editor.sourceElement).trigger('crmWysiwygCreate', ['ckeditor', editor]);
      deferred.resolve();
    }

    function initialize() {
      $(item).addClass('crm-wysiwyg-enabled');

      ClassicEditor.create($(item)[0]).then(onReady);
    }

    if ($(item).hasClass('crm-wysiwyg-enabled')) {
      deferred.resolve();
    }
    else if ($(item).length) {
      // Lazy-load ckeditor.js
      if (window.ClassicEditor) {
        initialize();
      } else {
        CRM.loadScript(CRM.config.resourceBase + 'bower_components/ckeditor5/ckeditor.js').done(initialize);
      }
    } else {
      deferred.reject();
    }
    return deferred;
  };

  CRM.wysiwyg.destroy = function(item) {
    $(item).removeClass('crm-wysiwyg-enabled');
    var editor = getInstance(item);
    if (editor) {
      editor.destroy();
    }
  };

  CRM.wysiwyg.updateElement = function(item) {
    var editor = getInstance(item);
    if (editor) {
      editor.updateSourceElement();
    }
  };

  CRM.wysiwyg.getVal = function(item) {
    var editor = getInstance(item);
    if (editor) {
      return editor.getData();
    } else {
      return $(item).val();
    }
  };

  CRM.wysiwyg.setVal = function(item, val) {
    var editor = getInstance(item);
    if (editor) {
      return editor.setData(val);
    } else {
      return $(item).val(val);
    }
  };

  CRM.wysiwyg.insert = function(item, text) {
    var editor = getInstance(item);
    if (editor) {
      editor.insertText(text);
    } else {
      CRM.wysiwyg._insertIntoTextarea(item, text);
    }
  };

  CRM.wysiwyg.focus = function(item) {
    var editor = getInstance(item);
    if (editor) {
      editor.focus();
    } else {
      $(item).focus();
    }
  };

})(CRM.$, CRM._);
