<?php

namespace Drupal\qrcode_fields;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Define QR URL service plugin interface.
 */
interface QRUrlServicePluginInterface extends PluginInspectionInterface {

  /**
   * Get QR image URL.
   *
   * @return \Drupal\Core\Url
   *   URL object.
   */
  public function getUrl();

  /**
   * Get QR image URL query params.
   *
   * This method should return all necessary query params
   * needed to generate URL image.
   *
   * @return array
   *   Array of query params.
   */
  public function getUrlQueryParams();

}
