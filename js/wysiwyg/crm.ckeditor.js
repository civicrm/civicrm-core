// https://civicrm.org/licensing
(function($, _) {
  function getInstance(item) {
    var name = $(item).attr("name");
    return CKEDITOR.instances[name];
  }

  CRM.wysiwyg = {
    supportsFileUploads: true,
    create: function(item) {
      var browseUrl = CRM.config.userFrameworkResourceUrl + "packages/kcfinder/browse.php";
      var uploadUrl = CRM.config.userFrameworkResourceUrl + "packages/kcfinder/upload.php";
      var editor = CKEDITOR.replace($(item)[0]);
      if (editor) {
        editor.config.filebrowserBrowseUrl = browseUrl+'?cms=civicrm&type=files';
        editor.config.filebrowserImageBrowseUrl = browseUrl+'?cms=civicrm&type=images';
        editor.config.filebrowserFlashBrowseUrl = browseUrl+'?cms=civicrm&type=flash';
        editor.config.filebrowserUploadUrl = uploadUrl+'?cms=civicrm&type=files';
        editor.config.filebrowserImageUploadUrl = uploadUrl+'?cms=civicrm&type=images';
        editor.config.filebrowserFlashUploadUrl = uploadUrl+'?cms=civicrm&type=flash';
      }
    },
    destroy: function(item) {
      var editor = getInstance(item);
      if (editor) {
        editor.destroy();
      }
    },
    updateElement: function(item) {
      var editor = getInstance(item);
      if (editor) {
        editor.updateElement();
      }
    },
    val: function(item) {
      var editor = getInstance(item);
      if (editor) {
        return editor.getData();
      } else {
        return $(item).val();
      }
    },
  };
})(CRM.$, CRM._);
