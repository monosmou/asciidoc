<?php

namespace Drupal\asciidoc\Plugin\Filter;

use Drupal\asciidoc\Component\Process\ExecutableFinder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class AsciidocFilterBase extends FilterBase {
  use StringTranslationTrait;

  const VERSION_PARAMETERS = '--version';
  const PROCESS_PARAMETERS = '--no-header-footer -o - -';
  const EXECUTABLE_NAME = NULL;

  function getExecutableName() {
    return static::EXECUTABLE_NAME;
  }

  /**
   * @return \Drupal\asciidoc\Component\Process\ExecutableFinder
   */
  protected function getExecutableFinder() {
    $finder = new ExecutableFinder();
    $finder->addSuffix('-safe');
    return $finder;
  }

  public function getVersion($command = NULL) {
    $process = $this->getVersionCommand($command);
    $process->run();

    // executes after the command finishes
    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    return $process->getOutput();
  }

  /**
   * @param string $command
   *     The absolute path to the asciidoc command
   * @return string[]
   *     Environment variables for the subprocess
   */
  function getEnvironment($command) {
    return ['LANG' => getenv('LANG')];
  }

  /**
   * @return Process
   */
  protected function getProcessCommand() {
    $command = $this->settings['asciidoc_command'];
    $process = new Process(
      $command . ' ' . static::PROCESS_PARAMETERS
    );
    $process->setEnv($this->getEnvironment($command));
    $process->setWorkingDirectory(file_directory_temp());
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  protected function getVersionCommand($command) {
    if (is_null($command)) {
      $command = $this->settings['asciidoc_command'];
    }
    $process = new Process($command . ' ' . static::VERSION_PARAMETERS);
    $process->setEnv($this->getEnvironment($command));
    return $process;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (empty($text)) {
      return '';
    }

    $process = $this->getProcessCommand();
    $process->setInput($text);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    return new FilterProcessResult($process->getOutput());
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('You can use <a href="@user_guide">AsciiDoc syntax</a> to format and style the text. For a quick reference see the <a href="@cheatsheet">cheatsheet</a>.', array('@user_guide' => 'http://www.methods.co.nz/asciidoc/userguide.html', '@cheatsheet' => 'http://powerman.name/doc/asciidoc'));
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $id = $this->getPluginId();
    $options = [];

    $finder = $this->getExecutableFinder();
    $executables = $finder->findAll($this->getExecutableName());
    $config = $this->getConfiguration();

    foreach ($executables as $command) {
      $options[$command] = $command;
      try {
        $output = $this->getVersion($command);
      } catch (\Exception $e) {
        $output = $e->getMessage();
      } finally {
        $form['asciidoc_version_'.md5($command)] = [
          '#type' => 'textarea',
          '#title' => $this->t('Output of %executable', ['%executable' => $command]),
          '#default_value' => $output,
          '#disabled' => TRUE,
          '#rows' => 3,
          '#states' => [
            'visible' => [
              ':input[name="filters['.$id.'][settings][asciidoc_command]"]' => array('value' => $command),
            ]
          ],
        ];
      }
    }

    $form['asciidoc_path'] = [
      '#type' => 'textfield',
      '#title' => 'PATH',
      '#default_value' => $config['settings']['asciidoc_path'],
      '#description' => $this->t('A list of directories where the executable is searched.'),
      '#disabled' => !is_null(Settings::get('asciidoc_path', NULL)),
      '#weight' => -10,
    ];

    // Get the current value and make sure it is still selectable
    // even if the command disappeared or the search path changed!
    $settings = $form_state->getValues();
    $asciidoc_command = $settings['filters'][$id]['settings']['asciidoc_command'];
    if (empty($asciidoc_command)) {
      $asciidoc_command = $this->settings['asciidoc_command'];
    }
    if (!array_key_exists($asciidoc_command, $options)) {
      $options[$asciidoc_command] = $this->t('@executable (Current value)', ['@executable'=>$asciidoc_command]);
    }

    $form['asciidoc_command'] = [
      '#type' => 'select',
      '#title' => $this->t('Command'),
      '#default_value' => $asciidoc_command,
      '#required' => FALSE,
      '#options' => $options,
      '#description' => $this->t('The executable used for processing Asciidoc-formatted text.'),
      '#weight' => -5,
    ];

    $form['filter_html_help'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display basic HTML help in long filter tips'),
      '#default_value' => $this->settings['filter_html_help'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['settings']['asciidoc_path']) && $configuration['status']) {
      $command = $configuration['settings']['asciidoc_command'];
      try {
        $version = $this->getVersion($command);
      } catch (\Exception $e) {
        $version = NULL;
        drupal_set_message('%executable failed to provide version information', ['%executable'=>$command]);
      }
    }
    parent::setConfiguration($configuration);
    // Force restrictions to be calculated again.
    //$this->restrictions = NULL;
  }
}
