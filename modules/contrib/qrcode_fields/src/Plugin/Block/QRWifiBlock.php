<?php

namespace Drupal\qrcode_fields\PLugin\Block;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\qrcode_fields\Service\QRImageInterface;
use Drupal\token\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * QR wifi block.
 *
 * @Block(
 *   id = "qrcode_wifi_block",
 *   admin_label = @Translation("QR wifi block"),
 *   category = @Translation("QR Code Fields")
 * )
 */
class QRWifiBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * QR image service.
   *
   * @var \Drupal\qrcode_fields\Service\QRImageInterface
   */
  protected $qrImage;

  /**
   * Token service.
   *
   * @var \Drupal\token\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PluginManagerInterface $pluginManager,
    QRImageInterface $qrImage,
    Token $token,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManager = $pluginManager;
    $this->qrImage = $qrImage;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
    $configuration,
    $plugin_id,
    $plugin_definition,
    $container->get('plugin.manager.qrcode_fields'),
    $container->get('qrcode_fields.qrimage'),
    $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'qrcode_plugin' => 'goqr',
      'network_name' => '',
      'password' => '',
      'hidden' => '',
      'encryption' => '',
      'display_text' => FALSE,
      'image' => [
        'width' => 200,
        'height' => 200,
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['network_name'] = [
      '#title' => $this->t('Wifi network name'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config['network_name'] ?? '',
      '#description' => $this->t('Enter WiFi network name'),
    ];
    $form['password'] = [
      '#title' => $this->t('Password'),
      '#type' => 'textfield',
      '#default_value' => $config['password'] ?? '',
      '#description' => $this->t('Enter WiFi password'),
    ];
    $form['hidden'] = [
      '#title' => $this->t('Hidden'),
      '#type' => 'checkbox',
      '#default_value' => $config['hidden'] ?? '',
      '#description' => $this->t('Is this a hidden WiFi network?'),
    ];
    $form['encryption'] = [
      '#title' => $this->t('Encryption'),
      '#type' => 'radios',
      '#required' => TRUE,
      '#default_value' => $config['encryption'] ?? '',
      '#options' => [
        'nopass' => $this->t('None'),
        'WPA' => $this->t('WPA/WPA2'),
        'WEP' => $this->t('WEP'),
      ],
      '#description' => $this->t('The type of security protocol on your network.'),
    ];

    $form['display_text'] = [
      '#title' => $this->t('Display text'),
      '#type' => 'checkbox',
      '#description' => $this->t('Shows text encoded in QR code.'),
      '#default_value' => $config['display_text'],
    ];
    $form['qrcode_plugin'] = [
      '#title' => $this->t('QR code service plugin'),
      '#type' => 'select',
      '#options' => $this->pluginManager->getDefinitionsList(),
      '#description' => $this->t('Service to use for QR code generation.'),
      '#default_value' => $config['qrcode_plugin'],
    ];
    $form['image'] = [
      '#type' => 'container',
    ];
    $form['image']['label'] = [
      '#title' => $this->t('QR image dimensions'),
      '#type' => 'label',
    ];
    $form['image']['width'] = [
      '#title' => $this->t('Width'),
      '#type' => 'number',
      '#default_value' => $config['image']['width'],
      '#placeholder' => $this->t('Width'),
    ];
    $form['image']['height'] = [
      '#title' => $this->t('Height'),
      '#type' => 'number',
      '#default_value' => $config['image']['height'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['network_name'] = $form_state->getValue('network_name');
    $this->configuration['password'] = $form_state->getValue('password');
    $this->configuration['hidden'] = $form_state->getValue('hidden');
    $this->configuration['encryption'] = $form_state->getValue('encryption');
    $this->configuration['display_text'] = $form_state->getValue('display_text');
    $this->configuration['qrcode_plugin'] = $form_state->getValue('qrcode_plugin');
    $this->configuration['image']['width'] = $form_state->getValue('image')['width'];
    $this->configuration['image']['height'] = $form_state->getValue('image')['height'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $build = [];
    $build['image'] = $this->qrImage
      ->setPlugin($config['qrcode_plugin'])
      ->build(
                [
                  'network_name' => $config['network_name'],
                  'password' => $config['password'],
                  'hidden' => $config['hidden'],
                  'encryption' => $config['encryption'],
                  'plugin_id' => $this->pluginId,
                  'field_type' => 'qrcode_wifi',
                ],
                 $config['image']['width'], $config['image']['height']);
    if ($config['display_text']) {
      $build['network_name'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['network_name']),
        '#attributes' => [
          'class' => 'qrcode_wifi-' . $this->pluginId,
        ],
      ];
      $build['password'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['password']),
        '#attributes' => [
          'class' => 'qrcode_wifi-' . $this->pluginId,
        ],
      ];
      $build['hidden'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['hidden']),
        '#attributes' => [
          'class' => 'qrcode_wifi-' . $this->pluginId,
        ],
      ];
      $build['encryption'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['encryption']),
        '#attributes' => [
          'class' => 'qrcode_wifi-' . $this->pluginId,
        ],
      ];
    }

    return $build;
  }

}
