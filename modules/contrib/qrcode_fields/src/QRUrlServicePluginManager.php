<?php

namespace Drupal\qrcode_fields;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\qrcode_fields\Annotation\QRUrlServicePlugin;

/**
 * QR URL service plugin manager.
 */
class QRUrlServicePluginManager extends DefaultPluginManager {

  /**
   * Constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
    'Plugin/qrcode_fields',
    $namespaces,
    $module_handler,
    QRUrlServicePluginInterface::class,
    QRUrlServicePlugin::class
    );
    $this->setCacheBackend($cache_backend, 'qrcode_fields_plugin');
    $this->alterInfo('qrcode_fields_plugin');
  }

  /**
   * Get discovered plugin definitions list.
   *
   * @return array
   *   Array of discovered plugin definitions.
   */
  public function getDefinitionsList(): array {
    $definitions = $this->getDefinitions();
    $definitionsList = [];
    foreach ($definitions as $definition) {
      $definitionsList[$definition['id']] = $definition['label'];
    }
    return $definitionsList;
  }

}
