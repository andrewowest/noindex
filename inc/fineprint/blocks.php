<?php

namespace Fineprint;

/**
 * Fineprint Block Registry
 * Tumblr-style block system for semantic templating
 */

// Global block registry
$_FINEPRINT_BLOCKS = [];

/**
 * Register a block with the Fineprint engine
 * 
 * @param string $name Block name (e.g., 'Threads', 'IfAdmin')
 * @param array $definition Block definition with keys:
 *   - type: 'iterator', 'conditional', 'variable', 'include'
 *   - fetch: callable that returns data for the block
 *   - variables: array of variable names exposed inside the block (for docs)
 *   - description: string describing what the block does (for docs)
 */
function register_block($name, $definition) {
  global $_FINEPRINT_BLOCKS;
  
  // Validate definition
  if(!isset($definition['type'])) {
    throw new \Exception("Block '$name' must have a 'type' field");
  }
  
  if(!isset($definition['fetch']) || !is_callable($definition['fetch'])) {
    throw new \Exception("Block '$name' must have a callable 'fetch' field");
  }
  
  $_FINEPRINT_BLOCKS[$name] = $definition;
}

/**
 * Get a registered block definition
 */
function get_block($name) {
  global $_FINEPRINT_BLOCKS;
  return $_FINEPRINT_BLOCKS[$name] ?? null;
}

/**
 * Check if a block is registered
 */
function has_block($name) {
  global $_FINEPRINT_BLOCKS;
  return isset($_FINEPRINT_BLOCKS[$name]);
}

/**
 * List all registered blocks (for documentation)
 */
function list_blocks() {
  global $_FINEPRINT_BLOCKS;
  return $_FINEPRINT_BLOCKS;
}

/**
 * Helper: Register an iterator block (loops over items)
 */
function register_iterator_block($name, $fetch, $variables = [], $description = '') {
  register_block($name, [
    'type' => 'iterator',
    'fetch' => $fetch,
    'variables' => $variables,
    'description' => $description
  ]);
}

/**
 * Helper: Register a conditional block (show/hide based on condition)
 */
function register_conditional_block($name, $check, $description = '') {
  register_block($name, [
    'type' => 'conditional',
    'fetch' => $check,
    'description' => $description
  ]);
}

/**
 * Helper: Register a variable block (single value)
 */
function register_variable_block($name, $fetch, $description = '') {
  register_block($name, [
    'type' => 'variable',
    'fetch' => $fetch,
    'description' => $description
  ]);
}

/**
 * Generate documentation for all registered blocks
 */
function generate_block_docs() {
  $blocks = list_blocks();
  $output = "# Fineprint Blocks\n\n";
  
  foreach($blocks as $name => $def) {
    $output .= "## `[block:$name]`\n\n";
    $output .= "**Type:** " . ucfirst($def['type']) . "\n\n";
    
    if(!empty($def['description'])) {
      $output .= "**Description:** " . $def['description'] . "\n\n";
    }
    
    if(!empty($def['variables'])) {
      $output .= "**Variables:**\n";
      foreach($def['variables'] as $var) {
        $output .= "- `{" . $var . "}`\n";
      }
      $output .= "\n";
    }
    
    // Example usage
    if($def['type'] === 'iterator') {
      $output .= "**Example:**\n```html\n[block:$name]\n  <div>{" . ($def['variables'][0] ?? 'variable') . "}</div>\n[/block:$name]\n```\n\n";
    } elseif($def['type'] === 'conditional') {
      $output .= "**Example:**\n```html\n[block:$name]\n  <p>Content shown when condition is true</p>\n[/block:$name]\n```\n\n";
    }
    
    $output .= "---\n\n";
  }
  
  return $output;
}
