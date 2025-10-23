<?php

namespace Fineprint;

/**
 * Theme Manager
 * Handles loading and saving themes
 */

class Theme {
  private $name;
  private $path;
  private $template;
  private $css;
  
  public function __construct($name, $path) {
    $this->name = $name;
    $this->path = $path;
  }
  
  public function load() {
    $templateFile = $this->path . '/template.html';
    $cssFile = $this->path . '/style.css';
    
    if(file_exists($templateFile)) {
      $this->template = file_get_contents($templateFile);
    }
    
    if(file_exists($cssFile)) {
      $this->css = file_get_contents($cssFile);
    }
    
    return $this;
  }
  
  public function save($template, $css) {
    if(!is_dir($this->path)) {
      mkdir($this->path, 0755, true);
    }
    
    file_put_contents($this->path . '/template.html', $template);
    file_put_contents($this->path . '/style.css', $css);
    
    $this->template = $template;
    $this->css = $css;
    
    return $this;
  }
  
  public function getTemplate() {
    return $this->template;
  }
  
  public function getCss() {
    return $this->css;
  }
  
  public function getName() {
    return $this->name;
  }
}

function save_theme($name, $template, $css) {
  $themePath = __DIR__ . '/../../themes/' . $name;
  $theme = new Theme($name, $themePath);
  return $theme->save($template, $css);
}

function upload_theme($zipFile) {
  $themesDir = __DIR__ . '/../../themes';
  $zip = new \ZipArchive();
  
  if($zip->open($zipFile) === TRUE) {
    // Get theme name from first directory in zip
    $themeName = null;
    for($i = 0; $i < $zip->numFiles; $i++) {
      $filename = $zip->getNameIndex($i);
      $parts = explode('/', $filename);
      if(count($parts) > 1) {
        $themeName = $parts[0];
        break;
      }
    }
    
    if($themeName) {
      $extractPath = $themesDir . '/' . $themeName;
      $zip->extractTo($themesDir);
      $zip->close();
      return $themeName;
    }
  }
  
  return false;
}
