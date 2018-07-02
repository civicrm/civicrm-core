<?php

class civicrm_handler_field_contact_image extends views_handler_field {
  public function option_definition() {
    $options = parent::option_definition();
    $options['url_only'] = array(
      'default' => FALSE,
      'boolean' => TRUE,
    );
    $options['title'] = array(
      'default' => '',
    );
    $options['alt'] = array(
      'default' => '',
    );
    $directory = $this->get_civi_relative_upload_path();
    if (module_exists('image') && $directory !== FALSE) {
      $options['image_style'] = array(
        'default' => '',
      );
    }
    $options['width'] = array(
      'default' => '',
    );
    $options['height'] = array(
      'default' => '',
    );
    return $options;
  }

  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $directory = $this->get_civi_relative_upload_path();

    $form['url_only'] = array(
      '#title' => t('Output the image URL only'),
      '#description' => t('This option stops the URL being wrapped in an html <code>img</code> tag and ignores the title, alt, width and height settings. This may be useful for rewriting the results.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['url_only'],
    );

    $form['title'] = array(
      '#title' => t('Title attribute'),
      '#description' => t('The text to use as value for the <code>img</code> tag <code>title</code> attribute.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['title'],
      '#dependency' => array(
        'edit-options-url-only' => array(0),
      ),
    );

    $form['alt'] = array(
      '#title' => t('Alt attribute'),
      '#description' => t('The text to use as value for the <code>img</code> tag <code>alt</code> attribute.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['alt'],
      '#dependency' => array(
        'edit-options-url-only' => array(0),
      ),
    );

    if (module_exists('image') && $directory !== FALSE) {
      $styles = image_styles();
      $style_options = array('' => t('None (original image)'));
      foreach ($styles as $style) {
        $style_options[$style['name']] = $style['name'];
      }
      $form['image_style'] = array(
        '#title' => t('Image style'),
        '#description' => t('Display the contact image using an image style'),
        '#type' => 'select',
        '#options' => $style_options,
        '#default_value' => $this->options['image_style'],
        '#dependency' => array(
          'edit-options-url-only' => array(0),
        ),
      );
    }

    $form['width'] = array(
      '#title' => t('Width'),
      '#description' => t('Resize the image in the browser to this width. If left empty, the width will scale proportionally with the height.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['width'],
      '#element_validate' => array('element_validate_integer_positive'),
      '#dependency' => array(
        'edit-options-url-only' => array(0),
      ),
            '#prefix' => t('If you do not see Drupal image styles here, it may be worth checking CiviCRM "Custom files directory" <br> at <strong>"CiviCRM > Administer > System Settings > Directories" </strong> : it <strong>has to </strong> contain <italic>[civicrm.files]</italic> token'), 
    );

    $form['height'] = array(
      '#title' => t('Height'),
      '#description' => t('Resize the image in the browser to this height. If left empty, the height will scale proportionally with the width.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['height'],
      '#element_validate' => array('element_validate_integer_positive'),
      '#dependency' => array(
        'edit-options-url-only' => array(0),
      ),
    );

    if (module_exists('image') && $directory !== FALSE) {
      $form['width']['#dependency']['edit-options-image-style'] = array('');
      $form['width']['#dependency_count'] = 2;
      $form['height']['#dependency']['edit-options-image-style'] = array('');
      $form['height']['#dependency_count'] = 2;
    }
  }

  public function render($values) {
    $value = $this->get_value($values);
    if (empty($value)) {
      return;
    }
    if ($this->options['url_only']) {
      return $value;
    }
    $image = array(
      'path' => $value,
      'title' => $this->options['title'],
      'alt' => $this->options['alt'],
    );
    $directory = $this->get_civi_relative_upload_path();
    if ($this->options['image_style'] && module_exists('image') && $directory !== FALSE) {
      $image['style_name'] = empty($this->options['image_style']) ? '' : $this->options['image_style'];
      $image_path = $directory . substr(strstr($value, 'photo='), 6);
      $image['path'] = str_replace('\\', '/', $image_path);
      return theme('image_style', $image);
    }
    else {
      $image['width'] = empty($this->options['width']) ? 'auto' : $this->options['width'];
      $image['height'] = empty($this->options['height']) ? 'auto' : $this->options['height'];
      return theme('image', $image);
    }
  }

  /**
   * Get the CiviCRM upload directory relative to the Drupal public upload
   * directory so that we can support image styles.
   *
   * Returns the relative path if it is supported, otherwise returns FALSE.
   *
   * @return bool|string
   */
  public function get_civi_relative_upload_path() {
    civicrm_initialize();
    $wrapper = file_stream_wrapper_get_instance_by_uri('public://');
    $drupal_upload_path = $wrapper->getDirectoryPath();
    $civi_upload_path = Civi::paths()->getUrl(Civi::settings()->get('customFileUploadDir'));
    $relative_upload_path = explode($drupal_upload_path, $civi_upload_path);

    // If we couldn't explode() then Civi's upload path is not in the public://
    // directory, so we cannot support image styles.
    if (count($relative_upload_path) != 2) {
      return FALSE;
    }

    // Otherwise, return the relative path without the leading slash
    return substr($relative_upload_path[1], 1);
  }

}
