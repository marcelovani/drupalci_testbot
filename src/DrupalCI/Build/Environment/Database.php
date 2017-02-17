<?php

namespace DrupalCI\Build\Environment;

use DrupalCI\Console\DrupalCIStyleInterface;
use Pimple\Container;

class Database implements DatabaseInterface {

  /**
   * @var \PDO
   */
  protected $connection;

  protected $version;

  protected $dbtype;

  protected $url;

  protected $username;

  protected $configuration_file;

  protected $port;

  protected $password;

  protected $host;

  protected $dbfile;

  /**
   * @var
   *
   * This is the role that this database plays. Could be system, or results database for now.
   */
  protected $dbrole;

  protected $dbname;

  /**
   * Style object.
   *
   * @var \DrupalCI\Console\DrupalCIStyleInterface
   */
  protected $io;

  /**
   * Database constructor.
   *
   * @param $dbrole
   */
  public function __construct(DrupalCIStyleInterface $io, $dbrole) {
    $this->io = $io;
    $this->dbrole = $dbrole;
  }

  /**
   * @inheritDoc
   */
  public function getDbname() {
    return $this->dbname;
  }

  /**
   * @inheritDoc
   */
  public function setDbname($dbname) {
    $this->dbname = $dbname;
  }

  /**
   * @inheritDoc
   */
  public function getDbrole() {
    return $this->dbrole;
  }

  /**
   * @inheritDoc
   */
  public function setDbrole($dbrole) {
    $this->dbrole = $dbrole;
  }

  /**
   * @inheritDoc
   */
  public function getConnection() {
    return $this->connection;
  }

  /**
   * @inheritDoc
   */
  public function setConnection($connection) {
    $this->connection = $connection;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   * @inheritDoc
   */
  public function setVersion($version) {
    $this->version = $version;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getDbtype() {
    return $this->dbtype;
  }

  /**
   * @inheritDoc
   */
  public function setDbtype($dbtype) {
    $this->dbtype = $dbtype;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getUrl() {
    // Scheme + "://" + username + ":" + password + "@" + host + path
    if ($this->dbtype == 'sqlite') {
      return 'sqlite://localhost/sites/default/files/db.sqlite';
    }

    $new_url = $this->getScheme() . '//';
    if (isset($this->username)) {
      $new_url .= $this->username;
      if (isset($this->password)) {
        $new_url .= ':' . $this->password;
      }
      $new_url .= '@';
    }
    $new_url .= $this->host;
    if (isset($this->dbname)) {
      $new_url .= "/" . $this->dbname;
    }
    return $new_url;
  }

  /**
   * @inheritDoc
   */
  public function setUrl($url) {
    $this->url = $url;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * @inheritDoc
   */
  public function setUsername($username) {
    $this->username = $username;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getConfigurationFile() {
    return $this->configuration_file;
  }

  /**
   * @inheritDoc
   */
  public function setConfigurationFile($configuration_file) {
    $this->configuration_file = $configuration_file;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getPort() {
    return $this->port;
  }

  /**
   * @inheritDoc
   */
  public function setPort($port) {
    $this->port = $port;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getPassword() {
    return $this->password;
  }

  /**
   * @inheritDoc
   */
  public function setPassword($password) {
    $this->password = $password;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getHost() {
    return $this->host;
    // return 'drupaltestbot-db-' . $this->dbtype . '-' . $this->version;

  }

  /**
   * @inheritDoc
   */
  public function setHost($host) {
    $this->host = $host;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getDbfile() {
    return $this->dbfile;
  }

  /**
   * @inheritDoc
   */
  public function setDbfile($dbfile) {
    $this->dbfile = $dbfile;
  }

  /**
   * @inheritDoc
   */
  public function createDB() {
    try {
      $this->connect()->exec('CREATE DATABASE ' . $this->dbname);
    }
    catch (\PDOException $e) {
      $this->io->writeln("<comment>Could not create database $this->dbname.</comment>");
      return FALSE;
    }
    return TRUE;

  }

  /**
   * @inheritDoc
   */
  public function getScheme() {
    // This is kinda gross, but will do for now.
    $scheme = $this->dbtype;
    // @TODO find out what happens if we percona?
    switch ($this->dbtype) {
      case 'sqlite':
        $scheme = 'sqlite:';
        break;

      case 'mariadb':
        $scheme = 'mysql:';
        break;

      default:
        $scheme = $this->dbtype . ':';
    }
    return $scheme;
  }

  /**
   * @inheritDoc
   */
  public function connect($database = NULL) {
    // @TODO: maybe this can work with sqlite?
    // if($this->dbtype != 'sqlite') {
    $counter = 0;
    $increment = 10;
    $max_sleep = 120;
    // @TODO explore using PDO:ATTR_TIMEOUT to see if that works instead of polling in php.
    while ($counter < $max_sleep ) {
      if ($this->establishDBConnection($database)) {
        $this->io->writeln("<comment>Database is active.</comment>");
        break;
      }
      if ($counter >= $max_sleep) {
        $this->io->writeln("<error>Max retries reached, exiting promgram.</error>");
        exit(1);
      }
      $this->io->writeln("<comment>Sleeping " . $increment . " seconds to allow service to start.</comment>");
      sleep($increment);
      $counter += $increment;
    }
    return $this->connection;
    // }
  }

  /**
   * @inheritDoc
   */
  public function getDataDir() {

    $type = $this->dbtype;
    // @TODO find out what happens if we percona?
    switch ($type) {
      case 'pgsql':
        $dir = "/var/lib/postgresql/" . $this->version;
        break;

      case 'mysql':
      case 'mariadb':
        $dir = "/var/lib/mysql";
        break;

      case 'sqlite':
        $dir = "/var/www/html/sites/default/files/db.sqlite";
        break;

      default:
        $dir = "/var/lib/" . $this->dbtype;
    }
    return $dir;
  }

  protected function establishDBConnection($database = NULL) {
    try {
      $conn_string = $this->getPDODsn($database);
      $this->io->writeln("<comment>Attempting to connect to database server.</comment>");
      // @TODO: This shouldnt happen here, but lets just do it like this for now.
      $conn = new \PDO($conn_string, $this->username, $this->password);
    }
    catch (\PDOException $e) {
      $this->io->writeln("<comment>Could not connect to database server.</comment>");
      return FALSE;
    }
    $this->setConnection($conn);
    return TRUE;
  }

  /**
   * @param null $database
   * If set, tries to connect to a specific database
   *
   * @return string
   */
  protected function getPDODsn($database = NULL) {

    $conn_string = $this->getScheme();
    // @TODO FIX: again, I think I wanna see subclasses vs If's n switches.
    if ($this->dbtype == 'sqlite') {
      $conn_string .= $this->dbfile;
    }
    else {
      $conn_string .= 'host=' . $this->getHost();
      if (!empty($database)) {
        $conn_string .= ';dbname=' . $this->dbname;
      }
    }
    return $conn_string;
  }

}
