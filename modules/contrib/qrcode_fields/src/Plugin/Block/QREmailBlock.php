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
 * QR email block.
 *
 * @Block(
 *   id = "qrcode_email_block",
 *   admin_label = @Translation("QR email block"),
 *   category = @Translation("QR Code Fields")
 * )
 */
class QREmailBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
      'email' => '',
      'subject' => '',
      'message' => '',
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
    $form['email'] = [
      '#title' => $this->t('Email'),
      '#type' => 'textfield',
      '#default_value' => $config['email'] ?? '',
      '#maxlength' => '50',
      '#required' => TRUE,
      '#placeholder' => '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('Enter the email recipient here (e.g., your@company.com or a valid token like [site:mail]).<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 30 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $form['subject'] = [
      '#title' => $this->t('Subject'),
      '#type' => 'textfield',
      '#default_value' => $config['subject'] ?? '',
      '#maxlength' => '90',
      '#placeholder' => '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('Enter the email subject here (e.g., "Feedback on [node:title] for next newsletter").<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 90 characters, either through standard text or by employing token generation.</small>'),
      ],
    ];
    $form['message'] = [
      '#title' => $this->t('Message'),
      '#type' => 'textarea',
      '#default_value' => $config['message'] ?? '',
      '#maxlength' => '230',
      '#placeholder' => '',
      '#description' => [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#prefix' => $this->t('Enter the email message here (e.g.: "Hi dear, I hope this message finds you well! I recently came across information about your newsletter, [node:title] , and I am interested in subscribing to stay updated with your latest content and announcements. I appreciate your time and assistance in providing this information. I am looking forward to becoming a part of your newsletter community.").<br>This field supports tokens:'),
        '#suffix' => $this->t('<br><small>Utilize a maximum of 230 characters, either through standard text or by employing token generation.</small>'),
      ],
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
    $this->configuration['email'] = $form_state->getValue('email');
    $this->configuration['subject'] = $form_state->getValue('subject');
    $this->configuration['message'] = $form_state->getValue('message');
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
                  'email' => $config['email'],
                  'subject' => $config['subject'],
                  'message' => $config['message'],
                  'plugin_id' => $this->pluginId,
                  'field_type' => 'qrcode_email',
                ],
                 $config['image']['width'], $config['image']['height']);
    if ($config['display_text']) {
      $build['email'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['email']),
        '#attributes' => [
          'class' => 'qrcode_email-' . $this->pluginId,
        ],
      ];
      $build['subject'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['subject']),
        '#attributes' => [
          'class' => 'qrcode_email-' . $this->pluginId,
        ],
      ];
      $build['message'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->token->replace($config['message']),
        '#attributes' => [
          'class' => 'qrcode_email-' . $this->pluginId,
        ],
      ];
    }

    return $build;
  }

}
