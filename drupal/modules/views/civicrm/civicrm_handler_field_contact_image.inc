<?php

class civicrm_handler_field_contact_image extends views_handler_field {
  function option_definition() {
    $options = parent::option_definition();
    $options['alt'] = array(
      'default' => '',
    );
    $options['title'] = array(
      'default' => '',
    ); 
    $options['width'] = array(
      'default' => '',
    );
    $options['height'] = array(
      'default' => '',
    );
    $options['url_only'] = array(
      'default' => FALSE,
      'boolean' => TRUE,
    );

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state); 
    $form['title'] = array(
      '#title' => t('Title attribute'),
      '#description' => t('The text to use as value for the <code>img</code> tag <code>title</code> attribute.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['title'],
    );
    $form['alt'] = array(
      '#title' => t('Alt attribute'),
      '#description' => t('The text to use as value for the <code>img</code> tag <code>alt</code> attribute.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['alt'],
    );
    $form['width'] = array(
      '#title' => t('Width'),
      '#description' => t('Resize the image in the browser to this width. If left empty, the width will scale proportionally with the height.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['width'],
      '#element_validate' => array('element_validate_integer_positive'),
    );
    $form['height'] = array(
      '#title' => t('Height'),
      '#description' => t('Resize the image in the browser to this height. If left empty, the height will scale proportionally with the width.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['height'],
      '#element_validate' => array('element_validate_integer_positive'),
    );
    $form['url_only'] = array(
      '#title' => t('Output the image URL only'),
      '#description' => t('This option stops the URL being wrapped in an html <code>img</code> tag and ignores the title, alt, width and height settings. This may be useful for rewriting the results.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['url_only'],
    );
  }

  function render($values) {
    $value = $this->get_value($values);

    if (empty($value)) {
      return;
    }
    if ($this->options['url_only']) {
      return $value;
    }
    $image = array(
      'path' => $value,
      'width' => empty($this->options['width']) ? 'auto' : $this->options['width'],
      'height' => empty($this->options['height']) ? 'auto' : $this->options['height'],
      'title' => $this->options['title'],
      'alt' => $this->options['alt'],
    );
    return theme('image', $image);
  }
}
