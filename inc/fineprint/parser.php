<?php

namespace Fineprint;

/**
 * Fineprint Template Parser
 * Tumblr-style [block] syntax for HTML templates
 */

class TemplateParser {
  private $blocks = [];
  private $variables = [];
  private $template = '';
  
  public function parse($template) {
    // Parse [block:name] ... [/block:name] syntax
    $pattern = '/\[block:(\w+)\](.*?)\[\/block:\1\]/s';
    preg_match_all($pattern, $template, $matches, PREG_SET_ORDER);
    
    foreach($matches as $match) {
      $this->blocks[$match[1]] = $match[2];
    }
    
    // Parse {variable} syntax
    $this->template = $template;
    return $this;
  }
  
  public function render($context = []) {
    $output = $this->template;
    
    // Handle Fineprint blocks {block:Name} ... {/block:Name}
    $output = $this->processFineprintBlocks($output, $context);
    
    // Handle conditional blocks [if:variable] ... [/if:variable]
    $output = $this->processConditionals($output, $context);
    
    // Handle loops [each:items] ... [/each:items]
    $output = $this->processLoops($output, $context);
    
    // Replace variables {variable}
    $output = $this->replaceVariables($output, $context);
    
    return $output;
  }
  
  private function processFineprintBlocks($template, $context) {
    // Process [block:Name]...[/block:Name] syntax
    // Support nested blocks by processing recursively
    $maxIterations = 10;
    $iteration = 0;
    
    while($iteration < $maxIterations) {
      // Match both [block:Name]content[/block:Name] and self-closing [block:Name]
      $pattern = '/\[block:(\w+)\](?:(.*?)\[\/block:\1\])?/s';
      $newTemplate = preg_replace_callback($pattern, function($matches) use ($context) {
        $blockName = $matches[1];
        $blockContent = $matches[2] ?? ''; // Empty for self-closing blocks
        
        // Check if block is registered
        if(!\Fineprint\has_block($blockName)) {
          // Unknown block, leave it as-is or remove it
          return '';
        }
        
        $block = \Fineprint\get_block($blockName);
        $result = $block['fetch']($context);
        
        // Handle different block types
        if($block['type'] === 'iterator') {
          // Loop over items
          if(!is_array($result)) {
            return '';
          }
          
          $output = '';
          foreach($result as $item) {
            // Merge item data with context
            $itemContext = array_merge($context, is_array($item) ? $item : []);
            // Create a new parser for the nested content
            $nestedParser = new TemplateParser();
            $nestedParser->parse($blockContent);
            $output .= $nestedParser->render($itemContext);
          }
          return $output;
        }
        
        if($block['type'] === 'conditional') {
          // Show content if condition is true
          return $result ? $blockContent : '';
        }
        
        if($block['type'] === 'variable') {
          // Replace with variable value
          // Check if result is marked as safe HTML
          if(is_array($result) && isset($result['__html'])) {
            return $result['__html']; // Don't escape safe HTML
          }
          return htmlspecialchars((string)$result, ENT_QUOTES, 'UTF-8');
        }
        
        return '';
      }, $template);
      
      // If nothing changed, we're done
      if($newTemplate === $template) {
        break;
      }
      
      $template = $newTemplate;
      $iteration++;
    }
    
    return $template;
  }
  
  private function processConditionals($template, $context) {
    // Process conditionals recursively to handle nested conditionals
    $maxIterations = 10; // Prevent infinite loops
    $iteration = 0;
    
    while($iteration < $maxIterations) {
      $pattern = '/\[if:(\w+)\](.*?)\[\/if:\1\]/s';
      $newTemplate = preg_replace_callback($pattern, function($matches) use ($context) {
        $var = $matches[1];
        $content = $matches[2];
        // Check if variable exists and is truthy (handles booleans properly)
        $show = isset($context[$var]) && $context[$var];
        return $show ? $content : '';
      }, $template);
      
      // If nothing changed, we're done
      if($newTemplate === $template) {
        break;
      }
      
      $template = $newTemplate;
      $iteration++;
    }
    
    return $template;
  }
  
  private function processLoops($template, $context) {
    $pattern = '/\[each:(\w+)\](.*?)\[\/each:\1\]/s';
    return preg_replace_callback($pattern, function($matches) use ($context) {
      $var = $matches[1];
      $content = $matches[2];
      $output = '';
      
      if(isset($context[$var]) && is_array($context[$var])) {
        foreach($context[$var] as $item) {
          $itemOutput = $content;
          // Replace {item.property} with actual values
          $itemOutput = $this->replaceVariables($itemOutput, $item);
          $output .= $itemOutput;
        }
      }
      
      return $output;
    }, $template);
  }
  
  private function replaceVariables($template, $context) {
    return preg_replace_callback('/\{(\w+(?:\.\w+)*)\}/', function($matches) use ($context) {
      $path = explode('.', $matches[1]);
      $value = $context;
      
      foreach($path as $key) {
        if(is_array($value) && isset($value[$key])) {
          $value = $value[$key];
        } else {
          return '';
        }
      }
      
      // Check if value is marked as safe HTML
      if(is_array($value) && isset($value['__html'])) {
        return $value['__html']; // Don't escape
      }
      
      return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }, $template);
  }
}

function parse_template($template) {
  $parser = new TemplateParser();
  return $parser->parse($template);
}
