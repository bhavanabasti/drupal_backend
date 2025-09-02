<?php

namespace Drupal\qrcode_fields\Plugin\qrcode_fields;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\qrcode_fields\QRUrlServicePluginInterface;

/**
 * QR service plugin implementation.
 *
 * @QRUrlServicePlugin(
 *   id = "goqr",
 *   label = "QR Code Generator (goQR.me)"
 * )
 *
 * Format example:
 *  https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=DATA
 */
class QRCodeGenerator extends PluginBase implements QRUrlServicePluginInterface {

  /**
   * Service API URL.
   *
   * @var string
   */
  protected $url = 'https://api.qrserver.com/v1/create-qr-code';

  /**
   * QR URL query params.
   *
   * @var array
   *  Array of params.
   */
  protected $urlQueryParams = [];

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return Url::fromUri($this->url, [
      'query' => $this->getUrlQueryParams(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlQueryParams() {
    return $this->urlQueryParams += [
      'data' => $this->configuration['data'],
      'size' => "{$this->configuration['image_width']}x{$this->configuration['image_height']}",
    ];
  }

}
