<?php

namespace Drupal\site_alerts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AlertStylesForm
 */
class AlertStylesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'site_alerts.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_alerts_styles_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('site_alerts.settings');

    $form['text'] = [
      '#markup' => '<p>' . $this->t('Configure styles for site alerts.') . '</p>',
    ];

    if (!empty($config->get('styles'))) {
      $stylesValue = explode(",", $config->get('styles'));
      $styles = implode("\n", $stylesValue);
    }

    $form['txtStyles'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Styles'),
      '#description' => $this->t('Specify styles for use in alerts in the following format (one per line): Name|Background color in hex|Text color in hex. For example, Promo|#000000|#ffffff.'),
      '#rows' => 5,
      '#resizable' => 'both',
      '#default_value' => $styles ?? '',
      '#required' => TRUE
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $styleOptions = [];
    $styleArray = explode("\n", $form_state->getValue('txtStyles'));
    foreach ($styleArray as $sKey => $sValue) {
      $styleOptions[$sKey] = trim(str_replace('\r', '', $sValue));
    }

    $this->configFactory->getEditable('site_alerts.settings')
      ->set('styles', implode(',', $styleOptions))
      ->save();
  }
}