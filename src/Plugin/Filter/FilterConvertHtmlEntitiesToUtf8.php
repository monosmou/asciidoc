<?php

/**
 * @file
 * Contains \Drupal\asciidoc\Plugin\Filter\ConvertHtmlEntitiesToUtf8.
 */

namespace Drupal\asciidoc\Plugin\Filter;

use DOMDocument;
use Drupal\Core\Annotation\Translation;
use Drupal\filter\Annotation\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter that converts HTML entities to UTF-8 characters.
 *
 * @Filter(
 *   id = "asciidoc_convert",
 *   title = @Translation("Convert HTML entities to UTF-8 characters."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   description = @Translation("Recommended for execution after an asciidoc filter.")
 * )
 */
class FilterConvertHtmlEntitiesToUtf8 extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $output = mb_convert_encoding($text, 'HTML-ENTITIES', "UTF-8");
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    @$dom->loadHTML( $output );
    $dom->normalizeDocument();
    $processedText = $dom->saveHTML();

    return new FilterProcessResult(strtr($processedText, [ PHP_EOL => ""]) );
  }
}
