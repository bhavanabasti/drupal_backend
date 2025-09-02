<?php


namespace Drupal\vehicle_app_config\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class VehicleAppSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vehicle_app_config.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vehicle_app_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vehicle_app_config.settings');

    $form['energy_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Energy Rate (₹ per unit)'),
      '#default_value' => $config->get('energy_rate') ?? 9,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vehicle_app_config.settings')
      ->set('energy_rate', $form_state->getValue('energy_rate'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
