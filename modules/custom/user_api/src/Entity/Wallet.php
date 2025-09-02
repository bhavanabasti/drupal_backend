<?php

namespace Drupal\user_api\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the Wallet entity.
 *
 * @ContentEntityType(
 *   id = "wallet",
 *   label = @Translation("Wallet"),
 *   base_table = "wallet",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "changed" = "changed"
 *   },
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder"
 *   },
 *   admin_permission = "administer site configuration",
 *   links = {
 *     "canonical" = "/wallet/{wallet}",
 *     "add-form" = "/wallet/add",
 *     "edit-form" = "/wallet/{wallet}/edit",
 *     "delete-form" = "/wallet/{wallet}/delete"
 *   }
 * )
 */

class Wallet extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {
  use EntityOwnerTrait;
  use EntityChangedTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['balance'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Wallet Balance'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue(0.00);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }
}
