<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Ajax;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Finisher\AbstractFinisher;
use Typoheads\Formhandler\Validator\AjaxFormValidator;

/**
 * A class validated the form and process this. This class is called via AJAX.
 */
class FormSubmit extends AbstractAjax {
  /**
   * Array of configured translation files.
   *
   * @var string[]
   */
  protected array $langFiles = [];

  private string $formValuePrefix;

  /**
   * Main method of the class.
   */
  public function main(): ResponseInterface {
    $errors = [];

    // init flexform
    $this->pi_initPIflexForm();

    $this->formValuePrefix = $this->utilityFuncs->getSingle($this->settings, 'formValuesPrefix');
    $this->gp = (array) (GeneralUtility::_GP($this->formValuePrefix) ?? []);

    /** @var AjaxFormValidator $validator */
    $validator = GeneralUtility::makeInstance(AjaxFormValidator::class);
    $validator->validateAjaxForm($this->gp, $errors);
    if (!empty($errors)) {
      return new HtmlResponse(json_encode(['success' => false, 'errors' => $errors]) ?: '', 200);
    }

    $output = $this->runFinishers($errors);
    if (!empty($errors)) {
      return new HtmlResponse(json_encode(['success' => false, 'errors' => $errors]) ?: '', 200);
    }

    return new HtmlResponse(json_encode(['success' => true, 'data' => $output]) ?: '', 200);
  }

  /**
   * @param string $field Field name to convert
   */
  public function pi_initPIflexForm(string $field = 'pi_flexform'): void {
    // Converting flexform data into array:
    if (!is_array($this->cObj->data[$field]) && $this->cObj->data[$field]) {
      $this->cObj->data[$field] = GeneralUtility::xml2array($this->cObj->data[$field]);
      if (!is_array($this->cObj->data[$field])) {
        $this->cObj->data[$field] = [];
      }
    }
  }

  /**
   * Adds default configuration for every Formhandler component to the given configuration array.
   *
   * @param array<string, mixed> $conf The configuration of the component set in TS
   *
   * @return array<string, mixed> The initial configuration plus the default configuration
   */
  protected function addDefaultComponentConfig(array $conf): array {
    if (!isset($conf['langFiles'])) {
      $conf['langFiles'] = $this->langFiles;
    }
    $conf['formValuesPrefix'] = $this->settings['formValuesPrefix'] ?? '';
    $conf['templateSuffix'] = $this->settings['templateSuffix'] ?? '';

    return $conf;
  }

  /**
   * Process finishers.
   *
   * @param array<string, mixed> $errors
   *
   * @return mixed Output of a Finisher
   */
  protected function runFinishers(array &$errors): mixed {
    if (isset($this->settings['finishers.']) && is_array($this->settings['finishers.']) && 1 !== (int) $this->utilityFuncs->getSingle($this->settings['finishers.'], 'disable')) {
      ksort($this->settings['finishers.']);

      foreach ($this->settings['finishers.'] as $idx => $tsConfig) {
        if ('disabled' !== $idx) {
          $className = $this->utilityFuncs->getPreparedClassName($tsConfig);
          if (is_array($tsConfig) && !empty($className)) {
            if (1 !== (int) $this->utilityFuncs->getSingle($tsConfig, 'disable')) {
              /** @var AbstractFinisher $finisher */
              $finisher = GeneralUtility::makeInstance($className);
              $tsConfig['config.'] = $this->addDefaultComponentConfig($tsConfig['config.'] ?? []);
              $finisher->init($this->gp, $tsConfig['config.']);
              $finisher->validateConfig();

              $finisherError = null;

              // Process finisher
              $finisherReturn = $finisher->process($finisherError);

              // Check for error from finisher
              if (!empty($finisherError)) {
                $this->globals->getSession()?->set('finished', false);
                $errors[$className] = [];
                $errors[$className][] = $finisherError;

                return null;
              }

              // if the finisher returns HTML (e.g. Typoheads\Formhandler\Finisher\SubmittedOK)
              if (1 === (int) $this->utilityFuncs->getSingle($tsConfig['config.'], 'returns')) {
                $this->globals->getSession()?->set('finished', true);

                return $finisherReturn;
              }

              if (is_array($finisherReturn)) {
                $this->gp = $finisherReturn;
                $this->globals->setGP($this->gp);
              }
            }
          } else {
            $this->utilityFuncs->throwException('classesarray_error');
          }
        }
      }
      $this->globals->getSession()?->set('finished', true);
    }

    return null;
  }
}
