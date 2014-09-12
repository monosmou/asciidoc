<?php

/**
 * @file
 * Contains \Drupal\asciidoc\Plugin\Filter\Asciidoc.
 */

namespace Drupal\asciidoc\Plugin\Filter;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to use asciidoc syntax.
 *
 * @Filter(
 *   id = "asccidoc_simple",
 *   title = @Translation("Simple AsciiDoc filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   description = @Translation("Allows users to use AsciiDoc syntax.")
 * )
 */
class Asciidoc extends FilterBase {

  /**
   * Return asciidoc formatted text.
   */
  public static function getAsciidoc($text) {
    if (empty($text)) {
      return '';
    }

    // we use basically asciidoc defaults: --doctype article --backend xhtml11
    $command = sprintf('echo %s | asciidoc --no-header-footer -o - -', escapeshellarg($text));
    exec($command, $lines);
    $output = implode("\n", $lines);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(self::getAsciidoc($text));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('You can use <a href="@user_guide">AsciiDoc syntax</a> to format and style the text. For a quick reference see the <a href="@cheatsheet">cheatsheet</a>.', array('@user_guide' => 'http://www.methods.co.nz/asciidoc/userguide.html', '@cheatsheet' => 'http://powerman.name/doc/asciidoc'));
  }

}
