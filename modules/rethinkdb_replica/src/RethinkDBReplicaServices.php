<?php

namespace Drupal\rethinkdb_replica;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\rethinkdb\RethinkDB;

class RethinkDBReplicaServices {

  /**
   * @var RethinkDB
   */
  protected $rethinkdb;

  /**
   * @var EntityTypeManager
   */
  protected $entityManger;

  function __construct(RethinkDB $rethinkdb, EntityTypeManager $entity_manager) {
    $this->rethinkdb = $rethinkdb;
    $this->entityManger = $entity_manager;
  }

  /**
   * Creating a replica of the DB.
   *
   * @param $entity_type_id
   */
  public function createReplica($entity_type_id) {
    $this->rethinkdb->tableCreate($entity_type_id . '_replica');
    $this->entityManger->getStorage('rethink_replica_list')->create(['id' => $entity_type_id])->save();
  }

  /**
   * Flattering the entity object and make it ready for storing in RethinkDB.
   *
   * @param EntityInterface $entity
   *   The entity object.
   *
   * @return array
   */
  public function EntityFlatter(EntityInterface $entity) {
    $entity_array = $entity->toArray();

    foreach ($entity_array as $key => $field) {
      if (count($field) == 1) {
        // Single field. Flatten the array.
        if ($key == 'body') {
          // This is a body field. No need for other values.
          $new_value = $field[0]['value'];
        }
        else {
          if (is_array($field)) {
            $new_value = reset($field);

            if (count(array_keys($new_value)) === 1) {
              // In case we have only one key in the field, i.e value, we will
              // take it as the value.
              $new_value = reset($new_value);
            }
          }
          else {
            $new_value = $field;
          }
        }
      }
      else {
        // A field with multiple cardinality. Flat that array as well.
        // No need for recursion since there is no more than two level in this
        // case.
        $new_value = [];
        foreach ($field as $value) {
          $new_value[] = is_array($value) ? reset($value) : $value;
        }
      }

      $entity_array[$key] = $new_value;
    }

    return $entity_array;
  }

}