<?php

namespace Drupal\qrcode_fields\Plugin\qrcode_fields;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\qrcode_fields\QRUrlServicePluginInterface;

/**
 * QR service plugin implementation.
 *
 * @QRUrlServicePlugin(
 *   id = "tec_it",
 *   label = "QR Code Tec-IT"
 * )
 *
 * Format example:
 *  https://qrcode.tec-it.com/API/QRCode?data=/?size=200&data=DATA
 */
class QrCodeTecIt extends PluginBase implements QRUrlServicePluginInterface {

  /**
   * Service API URL.
   *
   * @var string
   */
  protected $url = 'https://qrcode.tec-it.com/API/QRCode';

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
      'size' => "{$this->configuration['image_width']}",
    ];
  }

}
