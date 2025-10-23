<?php

namespace Fineprint;

require_once __DIR__ . '/parser.php';
require_once __DIR__ . '/theme.php';

/**
 * Fineprint Runtime
 * Main rendering engine
 */

function render($themeName, $context = []) {
  $theme = load_theme($themeName);
  $parser = parse_template($theme->getTemplate());
  
  return [
    'html' => $parser->render($context),
    'css' => $theme->getCss()
  ];
}

function render_with_layout($themeName, $context = [], $layoutVars = []) {
  $result = render($themeName, $context);
  
  // Inject CSS into layout
  $html = str_replace('</head>', '<style>' . $result['css'] . '</style></head>', $result['html']);
  
  return $html;
}

function list_themes() {
  $themesDir = __DIR__ . '/../../themes';
  if(!is_dir($themesDir)) return [];
  
  $themes = [];
  foreach(scandir($themesDir) as $item) {
    if($item === '.' || $item === '..') continue;
    if(is_dir($themesDir . '/' . $item)) {
      $themes[] = $item;
    }
  }
  return $themes;
}

function load_theme($name) {
  $themePath = __DIR__ . '/../../themes/' . $name;
  if(!is_dir($themePath)) {
    throw new \Exception("Theme not found: $name");
  }
  
  $theme = new Theme($name, $themePath);
  $theme->load();
  return $theme;
}
