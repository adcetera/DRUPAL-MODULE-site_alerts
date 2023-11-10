<?php

namespace Drupal\site_alerts\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm
 */
class AlertSettingsForm extends ConfigFormBase {

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
    return 'site_alerts_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('site_alerts.settings');

    $form['text'] = [
      '#markup' => '<p>' . $this->t('Configure site wide alert settings.') . '</p>',
    ];

    if (!empty($config->get('styles'))) {
      $stylesValue = explode(",", $config->get('styles'));
      $styles = [];
      foreach ($stylesValue as $sKey => $sValue) {
        $style = explode('|', $sValue);
        $styles[$style[0]] = $style[0];
      }
    } else {
      $form['text_error'] = [
        '#markup' => '<p><strong>' . $this->t('You must add an alert style before configuring an alert.') . '</strong></p>',
      ];
      return parent::buildForm($form, $form_state);
    }

    $form['chkEnabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $config->get('enabled')
    ];

    $form['txtName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('Name this alert. Used for administrative reference only.'),
      '#default_value' => !empty($config->get('name')) ? $config->get('name') : '',
      '#required' => [
        ':input[name="chkEnabled"]' => array('checked' => TRUE),
      ],
      '#states' => [
        'visible' => [
          ':input[name="chkEnabled"]' => array('checked' => TRUE),
        ],
      ]
    ];

    $form['selStyle'] = [
      '#type' => 'select',
      '#title' => $this->t('Alert style'),
      '#description' => $this->t('Select the style of the alert displayed.'),
      '#default_value' => !empty($config->get('style')) ? $config->get('style') : '',
      '#options' => $styles,
      '#required' => [
        ':input[name="chkEnabled"]' => array('checked' => TRUE),
      ],
      '#states' => [
        'visible' => [
          ':input[name="chkEnabled"]' => array('checked' => TRUE),
        ],
      ]
    ];

    $form['txtMessage'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#wysiwyg' => TRUE,
      '#title' => $this->t('Alert message'),
      '#default_value' => !empty($config->get('message')) ? $config->get('message') : '',
      '#required' => [
        ':input[name="chkEnabled"]' => array('checked' => TRUE),
      ],
      '#states' => [
        'visible' => [
          ':input[name="chkEnabled"]' => array('checked' => TRUE),
        ],
      ]
    ];

    $form['chkDismissable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Alert is dismissable'),
      '#default_value' => $config->get('dismissable'),
      '#states' => [
        'visible' => [
          ':input[name="chkEnabled"]' => array('checked' => TRUE),
        ],
      ]
    ];

    $form['chkScheduled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Schedule alert'),
      '#default_value' => $config->get('scheduled'),
      '#states' => [
        'visible' => [
          ':input[name="chkEnabled"]' => array('checked' => TRUE),
        ],
      ]
    ];

    $form['scheduled'] = array(
      '#type' => 'details',
      '#title' => $this->t('Scheduling'),
      '#description' => $this->t('Configure when the alert should be displayed.'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="chkEnabled"]' => array('checked' => TRUE),
          ':input[name="chkScheduled"]' => array('checked' => TRUE),
        ],
      ]
    );

    $form['scheduled']['startDate'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Start date/time'),
      '#default_value' => $config->get('start_date') ? new DrupalDateTime($config->get('start_date')) : '',
    ];

    $form['scheduled']['endDate'] = [
      '#type' => 'datetime',
      '#title' => $this->t('End date/time'),
      '#default_value' => $config->get('end_date') ? new DrupalDateTime($config->get('end_date')): '',
    ];

    $form['chkPageLimit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit to certain pages'),
      '#default_value' => $config->get('limit_by_page'),
      '#states' => [
        'visible' => [
          ':input[name="chkEnabled"]' => array('checked' => TRUE),
        ],
      ]
    ];

    $form['pageLimit'] = array(
      '#type' => 'details',
      '#title' => $this->t('Page configuration'),
      '#description' => $this->t('Configure which pages should display the alert (or not).'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="chkPageLimit"]' => array('checked' => TRUE),
        ],
      ]
    );

    if (!empty($config->get('pages'))) {
      $pagesValue = explode(",", $config->get('pages'));
      $pages = implode("\n", $pagesValue);
    }

    $form['pageLimit']['txtPages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#description' => $this->t('Specify pages by using their paths. Enter one path per line. The "*" character is a wildcard. An example path is /user/* for every user page. / is the front page.'),
      '#rows' => 5,
      '#resizable' => 'both',
      '#default_value' => $pages ?? ''
    ];

    $form['pageLimit']['rdShowHidePages'] = [
      '#type' => 'radios',
      '#title' => $this->t('Page visibility'),
      '#options' => [
        'show' => $this->t('Show for listed pages'),
        'hide' => $this->t('Hide for listed pages')
      ],
      '#default_value' => !empty($config->get('show_hide_pages')) ? $config->get('show_hide_pages') : 'show',
    ];

    $form['text_footer'] = [
      '#markup' => '<p><i>' . $this->t('Note: submitting this form will result in a clearing of Drupal caches.') . '</i></p>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $startDate = !empty($form_state->getValue('startDate')) ? $form_state->getValue('startDate')->__toString() : '';
    $endDate = !empty($form_state->getValue('endDate')) ? $form_state->getValue('endDate')->__toString() : '';
    $pageRestrictions = [];
    $pageArray = explode("\n", $form_state->getValue('txtPages'));
    foreach ($pageArray as $pKey => $pValue) {
      $pageRestrictions[$pKey] = trim(str_replace('\r', '', $pValue));
    }

    $this->configFactory->getEditable('site_alerts.settings')
      ->set('enabled', $form_state->getValue('chkEnabled'))
      ->set('name', $form_state->getValue('chkEnabled') ? $form_state->getValue('txtName') : '')
      ->set('style', $form_state->getValue('chkEnabled') ? $form_state->getValue('selStyle') : '')
      ->set('message', $form_state->getValue('chkEnabled') ? $form_state->getValue('txtMessage')['value'] : '')
      ->set('dismissable', $form_state->getValue('chkDismissable') ? $form_state->getValue('chkDismissable') : false)
      ->set('scheduled', $form_state->getValue('chkEnabled') ? $form_state->getValue('chkScheduled') : false)
      ->set('start_date', $form_state->getValue('chkEnabled') && $form_state->getValue('chkScheduled') ? $startDate : '')
      ->set('end_date', $form_state->getValue('chkEnabled') && $form_state->getValue('chkScheduled') ? $endDate : '')
      ->set('limit_by_page', $form_state->getValue('chkEnabled') ? $form_state->getValue('chkPageLimit') : false)
      ->set('pages', $form_state->getValue('chkEnabled') && $form_state->getValue('chkPageLimit') ? implode(',', $pageRestrictions) : '')
      ->set('show_hide_pages', $form_state->getValue('chkEnabled') && $form_state->getValue('chkPageLimit') ? $form_state->getValue('rdShowHidePages') : '')
      ->save();

    drupal_flush_all_caches();
  }
}
