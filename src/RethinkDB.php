<?php

/**
 * @contains \Drupal\rethinkdb\RethinkDB.
 */

namespace Drupal\rethinkdb;

use Drupal\Core\Site\Settings;
use r\Connection;
use r\Queries\Dbs\Db;
use r\Queries\Tables\Table;

class RethinkDB {

  /**
   * @var Connection
   *
   * The connection object.
   */
  protected $connection;

  /**
   * @var array
   *
   * Array with the information of the connection.
   */
  protected $settings;

  /**
   * An alias to the RethinkDB service.
   *
   * @return RethinkDB
   */
  public static function getService() {
    return \Drupal::service('rethinkdb');
  }

  /**
   * RethinkDB constructor.
   *
   * @param Settings $settings
   *   The global object settings. Define the database connection in the
   *   settings.php file.
   */
  public function __construct(Settings $settings) {
    $info = $settings->get('rethinkdb', []);

    $info += [
      'host' => 'localhost',
      'port' => '28015',
      'database' => 'drupal',
      'apiKey' => NULL,
      'timeout' => NULL,
    ];

    $this->setConnection(\r\connect($info['host'], $info['port'], $info['database'], $info['apiKey'], $info['timeout']));
    $this->setSettings($info);
  }

  /**
   * Get the connection for the DB.
   *
   * @return Connection
   */
  public function getConnection() {
    return $this->connection;
  }

  /**
   * Set the connection object.
   *
   * @param Connection $connection
   *   A connection object.
   *
   * @return RethinkDB
   */
  public function setConnection(Connection $connection) {
    $this->connection = $connection;
    return $this;
  }

  /**
   * Get the settings of the DB connection.
   *
   * @return array
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Setter for settings property.
   *
   * @param $settings
   *  The settings relate to the DB connection.
   *
   * @return RethinkDB
   */
  public function setSettings($settings) {
    $this->settings = $settings;
    return $this;
  }

  /**
   * Get the DB object.
   *
   * @return Db
   */
  public function getDb() {
    return \r\db($this->settings['database']);
  }

  /**
   * Create a DB in the server.
   *
   * @param $db
   *   Optional. The database name. If not provided the database from the
   *   rethinkdb settings will used.
   * @param $delete_if_exists
   *   Optional. Delete the DB if exists. Default to TRUE.
   *
   * @throws \Exception
   *
   * @return RethinkDB
   */
  public function createDb($db = NULL, $delete_if_exists = TRUE) {
    if (!$db) {
      $db = $this->settings['database'];
    }

    $list = \r\dbList()->run($this->getConnection());

    if (in_array($this->settings['database'], $list)) {
      if ($delete_if_exists) {
        \r\dbDrop($db)->run($this->getConnection());
      }
      else {
        throw new \Exception("A database with the name {$db} already exists.");
      }
    }

    \r\dbCreate($db)->run($this->getConnection());

    return $this;
  }

  /**
   * Get the table object query-ready to use.
   *
   * @param $table
   *   The table name.
   *
   * @return Table
   */
  public function getTable($table) {
    return \r\table($table);
  }

  /**
   * Creating a table.
   *
   * @param $table
   *   The table name.
   *
   * @return array|\ArrayObject|\DateTime|null|\r\Cursor
   */
  public function tableCreate($table) {
    return $this->getDb()->tableCreate($table)
      ->run($this->getConnection());
  }

  /**
   * Insert a document into the DB.
   *
   * @param $table
   *   The table name.
   * @param $document
   *   The document object.
   *
   * @return array|\ArrayObject|\DateTime|null|\r\Cursor
   */
  public function insert($table, $document) {
    return $this->getTable($table)->insert($document)->run($this->getConnection());
  }

  /**
   * Load multiple items from the DB.
   *
   * @param $table_name
   *   The table name.
   * @param array $ids
   *   Array of documents IDs.
   *
   * @return \r\Queries\Selecting\GetAll
   */
  public function getAll($table_name, array $ids) {
    return $this->getAllWrapper($table_name, $ids)->run($this->getConnection())->toArray();
  }

  /**
   * Deleting multiple documents from table.
   *
   * @param $table_name
   *   The table name.
   * @param array $ids
   *   List of IDs.
   *
   * @return array|\ArrayObject|\DateTime|null|\r\Cursor
   */
  public function deleteAll($table_name, array $ids) {
    return $this->getAllWrapper($table_name, $ids)->delete()->run($this->getConnection());
  }

  /**
   * Wrapping the get all logic.
   *
   * @param $table_name
   *   The table name.
   * @param array $ids
   *   List of IDs.
   *
   * @return \r\Queries\Selecting\GetAll
   */
  protected function getAllWrapper($table_name, array $ids) {
    return $this->getTable($table_name)->getAll(\r\args($ids));
  }

  /**
   * Update a document in the DB.
   *
   * @param $table
   *   The table name.
   * @param $document
   *   The document object.
   *
   * @return array|\ArrayObject|\DateTime|null|\r\Cursor
   */
  public function update($table, $document) {
    return $this->getTable($table)->update($document)->run($this->getConnection());
  }

}
