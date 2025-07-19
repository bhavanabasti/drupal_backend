<?php

namespace Drupal\qrcode_fields\Service;

/**
 * Defines QRImage service interface.
 */
interface QRImageInterface {

  /**
   * Build QR image ready for rendering.
   *
   * @param array $data
   *   QR image data.
   *   Supported keys:
   *   - text: QR image text.
   *   - objects: Array of keyed objects used for Token::replace.
   * @param int $width
   *   QR image width.
   * @param int $height
   *   QR image height.
   *
   * @return array
   *   Return image render array.
   */
  public function build(array $data, $width, $height);

  /**
   * Set "qrcode_fields" plugin ID for generating QR image.
   *
   * @param string $pluginId
   *   Plugin ID.
   *
   * @return self
   *   Self.
   */
  public function setPlugin($pluginId);

}
