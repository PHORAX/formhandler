<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Interceptor;

/**
 * This script is part of the TYPO3 project - inspiring people to share!
 *
 * TYPO3 is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2 as published by
 * the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
 * Public License for more details.
 */

/**
 * An interceptor running given fields through StdWrap or a cObject.
 *
 * If you don't specify a cObject, the field value is sent through stdWrap.
 * If you specify a cObject (like "[fieldname] = TEXT") [fieldname].value
 * will automatically be set to the field's GET/POST value.
 *
 * The current value is also set ("[fieldname].current = 1" in any stdWrap).
 *
 * If [fieldname] does not exist, it will be created.
 *
 * <pre>
 * plugin.tx_formhandler_pi1.settings.initInterceptors.1.class = Tx_Formhandler_Interceptor_StdWrap
 * plugin.tx_formhandler_pi1.settings.initInterceptors.1.config.fieldConf {
 *   name.wrap = before|after
 *   name.hash = md5
 *   email = TEXT
 *   #email.value is automatically set
 *   email.trim = 1
 *   company = USER
 *   company.userFunc = user_myClass->doSomething
 * }
 * </pre>
 *
 * @author Mathias Bolt Lesniak <mathias@lilio.com>
 */
class StdWrap extends AbstractInterceptor {
  /**
   * Process fields.
   */
  public function process(): array|string {
    if (is_array($this->settings['fieldConf.'])) {
      $fieldConf = $this->settings['fieldConf.'];

      foreach ($fieldConf as $key => $value) {
        if ('.' === substr($key, -1)) {
          $key = substr($key, 0, -1);

          $oldCurrentVal = $this->globals->getCObj()?->getCurrentVal();
          $fieldValue = $this->gp[$key];
          $this->globals->getCObj()?->setCurrentVal($fieldValue);

          if (!isset($fieldConf[$key]) && is_array($fieldConf[$key.'.'])) {
            if (!isset($value['sanitize'])) {
              $value['sanitize'] = 1;
            }
            $this->gp[$key] = $this->globals->getCObj()?->stdWrap(strval($fieldValue), $value);
          } else {
            if (!isset($fieldConf[$key.'.']['value'])) {
              $fieldConf[$key.'.']['value'] = $fieldValue;
            }
            $this->gp[$key] = $this->utilityFuncs->getSingle($fieldConf, $key);
          }

          $this->globals->getCObj()?->setCurrentVal($oldCurrentVal);
        }
      }
    }

    return $this->gp;
  }
}
