<?php

namespace Drupal\qrcode_fields\Plugin\Field\FieldFormatter;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\qrcode_fields\Service\QRImageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for QRFieldFormatter plugins.
 */
abstract class QRFieldFormatterBase extends FormatterBase {

  use StringTranslationTrait;

  /**
   * QR image service.
   *
   * @var \Drupal\qrcode_fields\Service\QRImageInterface
   */
  protected $qrImage;

  /**
   * Plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\qrcode_fields\Service\QRImageInterface $qrImage
   *   QR image service.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   Plugin manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    QRImageInterface $qrImage,
    PluginManagerInterface $pluginManager,
  ) {
    parent::__construct(
    $plugin_id,
    $plugin_definition,
    $field_definition,
    $settings,
    $label,
    $view_mode,
    $third_party_settings
    );
    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->label = $label;
    $this->viewMode = $view_mode;
    $this->thirdPartySettings = $third_party_settings;
    $this->qrImage = $qrImage;
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
    $plugin_id,
    $plugin_definition,
    $configuration['field_definition'],
    $configuration['settings'],
    $configuration['label'],
    $configuration['view_mode'],
    $configuration['third_party_settings'],
    $container->get('qrcode_fields.qrimage'),
    $container->get('plugin.manager.qrcode_fields')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image' => [
        'width' => 200,
        'height' => 200,
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $plugin_id = $this->getPluginId();

    switch ($plugin_id) {
      case 'qrcode_fields_formatter':
        $elements['image']['width'] = [
          '#title' => $this->t('Width'),
          '#type' => 'number',
          '#default_value' => $this->getSetting('image')['width'],
        ];
        $elements['image']['height'] = [
          '#title' => $this->t('Height'),
          '#type' => 'number',
          '#default_value' => $this->getSetting('image')['height'],
        ];
        $activePluginDefinition = $this->pluginManager->getDefinition($this->getFieldSetting('qrcode_plugin'));
        $elements['plugin'] = [
          '#type' => 'select',
          '#title' => $this->t('QR code service plugin'),
          '#description' => $this->t('Plugin can be changed at field settings form'),
          '#options' => [
            $activePluginDefinition['label'],
          ],
          '#disabled' => TRUE,
        ];
        break;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $activePluginDefinition = $this->pluginManager->getDefinition($this->getFieldSetting('qrcode_plugin'));
    $summary['plugin'] = $this->t('QR image service: @name', [
      '@name' => $activePluginDefinition['label'],
    ]);

    $plugin_id = $this->getPluginId();

    switch ($plugin_id) {
      case 'qrcode_fields_formatter':
        $summary['dimensions'] = $this->t('QR image dimension: @widthx@height', [
          '@width' => $this->getSetting('image')['width'],
          '@height' => $this->getSetting('image')['height'],
        ]);
        break;
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $imageWidth = $this->getSetting('image')['width'];
    $imageHeight = $this->getSetting('image')['width'];
    $qrImageActivePlugin = $this->getFieldSetting('qrcode_plugin');
    $targetEntity = $items->getEntity();
    $targetEntityType = $items->getFieldDefinition()->getTargetEntityTypeId();

    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $delta => $item) {

      $field_definition = $item->getFieldDefinition();

      // Get the field type.
      $field_type = $field_definition->getType();

      $build_array = [];
      $build_array['field_type'] = $field_type;
      $build_array['objects'] = [$targetEntityType => $targetEntity];
      $plugin_id = $this->getPluginId();
      $build_array['plugin_id'] = $plugin_id;

      switch ($field_type) {
        case 'qrcode_email':

          if ($item->get('email')) {
            $build_array['email'] = $item->get('email')->getValue();
          }

          if ($item->get('subject')) {
            $build_array['subject'] = $item->get('subject')->getValue();
          }

          if ($item->get('message')) {
            $build_array['message'] = $item->get('message')->getValue();
          }

          break;

        case 'qrcode_mecard':

          if ($item->get('fname')) {
            $build_array['fname'] = $item->get('fname')->getValue();
          }
          if ($item->get('lname')) {
            $build_array['lname'] = $item->get('lname')->getValue();
          }
          if ($item->get('email')) {
            $build_array['email'] = $item->get('email')->getValue();
          }
          if ($item->get('phone')) {
            $build_array['phone'] = $item->get('phone')->getValue();
          }
          if ($item->get('address')) {
            $build_array['address'] = $item->get('address')->getValue();
          }
          if ($item->get('url')) {
            $build_array['url'] = $item->get('url')->getValue();
          }
          if ($item->get('note')) {
            $build_array['note'] = $item->get('note')->getValue();
          }
          if ($item->get('organization')) {
            $build_array['organization'] = $item->get('organization')->getValue();
          }
          if ($item->get('birthday')) {
            $build_array['birthday'] = $item->get('birthday')->getValue();
          }

          break;

        case 'qrcode_phone':
          $build_array['phone'] = $item->get('phone')->getValue();
          break;

        case 'qrcode_sms':
          $build_array['phone'] = $item->get('phone')->getValue();
          $build_array['message'] = $item->get('message')->getValue();
          break;

        case 'qrcode_text':
          $build_array['text'] = $item->get('text')->getValue();
          break;

        case 'qrcode_url':
          $build_array['url'] = $item->get('url')->getValue();
          break;

        case 'qrcode_vcard':
          if ($item->get('fname')) {
            $build_array['fname'] = $item->get('fname')->getValue();
          }
          if ($item->get('lname')) {
            $build_array['lname'] = $item->get('lname')->getValue();
          }
          if ($item->get('email')) {
            $build_array['email'] = $item->get('email')->getValue();
          }
          if ($item->get('phone')) {
            $build_array['phone'] = $item->get('phone')->getValue();
          }
          if ($item->get('work_phone')) {
            $build_array['work_phone'] = $item->get('work_phone')->getValue();
          }
          if ($item->get('address')) {
            $build_array['address'] = $item->get('address')->getValue();
          }
          if ($item->get('url')) {
            $build_array['url'] = $item->get('url')->getValue();
          }
          if ($item->get('note')) {
            $build_array['note'] = $item->get('note')->getValue();
          }
          if ($item->get('organization')) {
            $build_array['organization'] = $item->get('organization')->getValue();
          }
          if ($item->get('title')) {
            $build_array['title'] = $item->get('title')->getValue();
          }
          if ($item->get('birthday')) {
            $build_array['birthday'] = $item->get('birthday')->getValue();
          }

          break;

        case 'qrcode_event':
          if ($item->get('summary')) {
            $build_array['summary'] = $item->get('summary')->getValue();
          }
          if ($item->get('description')) {
            $build_array['description'] = $item->get('description')->getValue();
          }
          if ($item->get('location')) {
            $build_array['location'] = $item->get('location')->getValue();
          }
          if ($item->get('dstart')) {
            $build_array['dstart'] = $item->get('dstart')->getValue();
          }
          if ($item->get('dend')) {
            $build_array['dend'] = $item->get('dend')->getValue();
          }

          break;

        case 'qrcode_wifi':
          if ($item->get('network_name')) {
            $build_array['network_name'] = $item->get('network_name')->getValue();
          }
          if ($item->get('password')) {
            $build_array['password'] = $item->get('password')->getValue();
          }
          if ($item->get('hidden')) {
            $build_array['hidden'] = $item->get('hidden')->getValue();
          }
          if ($item->get('encryption')) {
            $build_array['encryption'] = $item->get('encryption')->getValue();
          }
          break;
      }

      $image = $this->qrImage->setPlugin($qrImageActivePlugin)
        ->build($build_array,
       $imageWidth,
       $imageHeight
      );
      $elements[$delta]['image'] = $image;
    }

    return $elements;
  }

}
