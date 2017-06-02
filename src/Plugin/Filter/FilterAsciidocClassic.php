<?php

/**
 * @file
 * Contains \Drupal\asciidoc\Plugin\Filter\Asciidoc.
 */

namespace Drupal\asciidoc\Plugin\Filter;

use Drupal\asciidoc\Component\Process\ExecutableFinder;
use Drupal\Core\Annotation\Translation;
use Drupal\filter\Annotation\Filter;
use Symfony\Component\Process\Process;

/**
 * Provides a filter to use asciidoc syntax.
 *
 * @Filter(
 *   id = "asccidoc_simple",
 *   title = @Translation("Classic AsciiDoc filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   description = @Translation("Allows users to use AsciiDoc syntax.")
 * )
 */
class FilterAsciidocClassic extends AsciidocFilterBase {

  const EXECUTABLE_NAME = 'asciidoc';
  const VERSION_PARAMETERS = '--version';
  const PROCESS_PARAMETERS = '--no-header-footer -o - -';

}
