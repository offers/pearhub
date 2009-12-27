<?php

/**
 * Adds a snippet of javascript to be executed on page load.
 */
function add_onload($js) {
  $GLOBALS['k_current_context']->document()->addOnload($js);
}

/**
 */
function add_stylesheet($url) {
  $GLOBALS['k_current_context']->document()->addStyle($url);
}

/**
 */
function add_javascript($js) {
  $GLOBALS['k_current_context']->document()->addScript($url);
}

/**
 * Returns querystring value.
 */
function query($key, $default = null) {
  return $GLOBALS['k_current_context']->query($key, $default);
}

function html_link($url, $title = null, $options = array()) {
  if ($title === null) {
    $title = $url;
  }
  $options['href'] = $url;
  $html = "<a";
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . escape($k) . '="' . escape($v) . '"';
    }
  }
  $html .= ">".escape($title)."</a>";
  return $html;
}

/**
 * Generates an opening html `<form>` tag.
 */
function html_form_tag($method = 'post', $action = null, $options = array()) {
  $method = strtolower($method);
  $html = '<form';
  $options['action'] = $action ? $action : $GLOBALS['k_current_context']->url();
  $options['method'] = $method === 'get' ? 'get' : 'post';
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . escape($k) . '="' . escape($v) . '"';
    }
  }
  $html .= ">\n";
  if ($method !== 'get' && $method !== 'post') {
    $html .= '<input type="hidden" name="_method" value="' . escape($method) . '" />
';
  }
  return $html;
}

function html_text_field($name, $value = null, $options = array()) {
  $html = '<input type="text"';
  $options['name'] = $name;
  $options['value'] = $value;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . escape($k) . '="' . escape($v) . '"';
    }
  }
  return $html . " />\n";
}

function html_password_field($name, $options = array()) {
  $html = '<input type="password"';
  $options['name'] = $name;
  $options['value'] = null;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . escape($k) . '="' . escape($v) . '"';
    }
  }
  return $html . " />\n";
}

function html_hidden_field($name, $value = null, $options = array()) {
  $html = '<input type="hidden"';
  $options['name'] = $name;
  $options['value'] = $value;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . escape($k) . '="' . escape($v) . '"';
    }
  }
  return $html . " />\n";
}

function html_text_area($name, $value = null, $options = array()) {
  $html = '<textarea';
  $options['name'] = $name;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . escape($k) . '="' . escape($v) . '"';
    }
  }
  return $html . ">" . escape($value) . "</textarea>\n";
}

function html_radio($name, $checked = false, $options = array()) {
  $html = "";
  if (isset($options['label'])) {
    $label = $options['label'];
    $options['label'] = null;
    $html .= '<label>';
  }
  $html .= '<input type="radio"';
  $options['name'] = $name;
  $options['value'] = $value;
  $options['checked'] = $checked ? 'checked' : null;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . escape($k) . '="' . escape($v) . '"';
    }
  }
  $html .= ' />';
  if (isset($label)) {
    $html .= escape($label) . '</label>';
  }
  return $html . "\n";
}

function html_checkbox($name, $checked = false, $options = array()) {
  $html = "";
  if (isset($options['label'])) {
    $label = $options['label'];
    $options['label'] = null;
    $html .= '<label>';
  }
  $html .= '<input type="checkbox"';
  $options['name'] = $name;
  $options['value'] = 'on';
  $options['checked'] = $checked ? 'checked' : null;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . escape($k) . '="' . escape($v) . '"';
    }
  }
  $html .= ' />';
  if (isset($label)) {
    $html .= escape($label) . '</label>';
  }
  return $html . "\n";
}

function html_select($name, $values = array(), $value = null, $options = array()) {
  $html = '<select';
  $options['name'] = $name;
  foreach ($options as $k => $v) {
    if ($v !== null) {
      $html .= ' ' . escape($k) . '="' . escape($v) . '"';
    }
  }
  return $html . ">" . html_options($values, $value) . "</select>\n";
}

/**
 * Renders html `<option>` elements from an array.
 */
function html_options($values = array(), $value = null) {
  $html = "";
  foreach ($values as $key => $v) {
    $html .= '<option';
    if (!is_integer($key)) {
      $html .= ' value="' . escape($v) . '"';
    }
    if ($v == $value) {
      $html .= ' selected="selected"';
    }
    $html .= '>';
    if (is_integer($key)) {
      $html .= escape($v);
    } else {
      $html .= escape($key);
    }
    $html .= "</option>\n";
  }
  return $html;
}

/////////////////////////////////////////////////////////////////////////////////////////

/**
 * Renders global errors for an entity.
 */
function krudt_errors($entity) {
  $html = array();
  foreach ($entity->errors as $field => $error) {
    if (!is_string($field)) {
      $html[] = '<p style="color:red">' . escape($error) . '</p>';
    }
  }
  return implode("\n", $html);
}

/**
 * Renders errors for a single of an entity field,
 */
function krudt_errors_for($entity, $field) {
  if (isset($entity->errors[$field])) {
    return
      '<span style="display:block;color:red">' . "\n"
      . e(implode(', ', $entity->errors[$field])) . "\n"
      . '</span>' . "\n";
  }
}

/**
 * Creates a `<input type="text" />` for a record.
 */
function krudt_text_field($entry, $field, $label = null) {
  $label || $label = ucfirst(str_replace('_', ' ', $field));
  $html = '  <p class="krudt-form">
    <label for="field-' . escape($field) . '">' . escape($label) . '</label>
    <input type="text" id="field-' . escape($field) . '" name="' . escape($field) . '" value="' . escape($entry->{$field}()) . '" />
';
  if (isset($entry->errors[$field])) {
    $html .= '    <span style="display:block;color:red">' . escape($entry->errors[$field]) . ' </span>
';
  }
  $html .= "  </p>\n";
  return $html;
}

/**
 * Creates a `<textarea />` for a record.
 */
function krudt_text_area($entry, $field, $label = null) {
  $label || $label = ucfirst(str_replace('_', ' ', $field));
  $html = '  <p class="krudt-form">
    <label for="field-' . escape($field) . '">' . escape($label) . '</label>
    <textarea id="field-' . escape($field) . '" name="' . escape($field) . '">' . escape($entry->{$field}()) . '</textarea>
';
  if (isset($entry->errors[$field])) {
    $html .= '    <span style="display:block;color:red">' . escape($entry->errors[$field]) . ' </span>
';
  }
  $html .= "  </p>\n";
  return $html;
}

/**
 * Just a very simple pagination widget.
 * You might want to have a look at PEAR::Pager for some more elaborate alternatives.
 */
function krudt_paginate($collection, $sticky_parameters = array(), $page_size = 10) {
  $page_size = (integer) $page_size;
  if ($page_size < 1) {
    throw new Exception("Can't paginate with size < 1");
  }
  $count = count($collection);
  $last_page = (integer) ceil($count / $page_size);
  if ($last_page === 1) {
    return "";
  }
  $page = query('page', 1);
  if ($page > $last_page) {
    $page = $last_page;
  }
  if ($page < 1) {
    $page = 1;
  }
  $html = "\n" . '<div class="pagination">';
  $params = array();
  foreach ($sticky_parameters as $key) {
    $params[$key] = query($key);
  }
  array_filter($params);
  for ($ii = 1; $ii <= $last_page; ++$ii) {
    if ($ii == $page) {
      $html .= "\n" . '  <span class="current">' . $ii . '</span>';
    } else {
      $params['page'] = $ii;
      $html .= "\n" . '  <a href="' . escape(url($params)) . '">' . $ii . '</a>';
    }
  }
  $html .= "\n" . '</div>';
  return $html;
}


