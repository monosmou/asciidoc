<?php

/**
 * @file
 * Contains \Drupal\asciidoc\Plugin\Filter\AsciidocFilter.
 */

namespace Drupal\asciidoc\Plugin\Filter;

use Drupal\asciidoc\ExecutablesFinder;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\filter\Annotation\Filter;
use Symfony\Component\Process\Process;

/**
 * Provides a filter to use asciidoc syntax.
 *
 * @Filter(
 *   id = "asciidoc_filter",
 *   title = @Translation("Asciidoctor filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   description = @Translation("Allows users to use AsciiDoc syntax and process text with the Asciidoctor processor.")
 * )
 */
class FilterAsciidoctor extends AsciidocFilterBase {

  const EXECUTABLE_NAME = 'asciidoctor';

  function getEnvironment($command) {
    $env = parent::getEnvironment($command);
    $env['GEM_HOME'] = $this->settings['asciidoctor_gemhome'];
    // if $gem_home is not set, guess it from the command:
    // e.g. /var/lib/gems/2.1.0/bin/asciidoctor --> /var/lib/gems/2.1.0
    if (empty($env['GEM_HOME'])) {
      $env['GEM_HOME'] = dirname(dirname($command));
    }
    return $env;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExecutableFinder() {
    $finder = parent::getExecutableFinder();

    $asciidoc_path = Settings::get('asciidoc_path', NULL);
    if (is_null($asciidoc_path)) {
      $config = $this->getConfiguration();
      $asciidoc_path = $config['settings']['asciidoc_path'];
    }
    if (!is_null($asciidoc_path)) {
      $finder->setPaths(explode(PATH_SEPARATOR, $asciidoc_path));
    }
    return $finder;
  }


  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $asciidoc_gemhome = Settings::get('asciidoctor_gemhome', $this->settings['asciidoctor_gemhome']);
    if (is_null($asciidoc_gemhome)) {
      $asciidoc_gemhome = getenv('GEM_HOME');
    }
    $form['asciidoctor_gemhome'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GEM_HOME'),
      '#default_value' => $asciidoc_gemhome,
      '#description' => $this->t('Path to the Gem repository where asciidoctor is installed.'),
      '#disabled' => !is_null(Settings::get('asciidoctor_gemhome', NULL)),
      '#weight' => -10,
    ];

    return $form;
  }
}
