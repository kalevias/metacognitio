<?php

/**
 * Created by PhpStorm.
 * User: Jacob
 * Date: 4/14/2017
 * Time: 9:10 PM
 */
class LogEvent
{
    /*
     * Event types and their pkEventID's in the database (needs to be updated every once in a while
     * so that any added event types are represented here).
     */

    /**
     * One of 3 modes to help indicate desired input or output from various internal functions.
     * Specifies a description-like string as the type.
     */
    const MODE_DESCRIPTION = 1;
    /**
     * One of 3 modes to help indicate desired input or output from various internal functions.
     * Specifies an int as the type.
     */
    const MODE_ID = 2;
    /**
     * One of 3 modes to help indicate desired input or output from various internal functions.
     * Specifies a name-like string as the type.
     */
    const MODE_NAME = 3;

    const AUTHOR_BIOGRAPHY = 1;
    const CONTACT_CREATE = 2;
    const CONTACT_DELETE = 3;
    const CONTACT_UPDATE = 4;
    const CRITIQUE_CREATE = 5;
    const CRITIQUE_DELETE = 6;
    const CRITIQUE_SEND = 7;
    const CRITIQUE_UPDATE = 8;
    const EDITOR_MEETING_NO = 10;
    const EDITOR_MEETING_YES = 11;
    const EDITOR_UPDATE_FORMS = 9;
    const FILE_ACTIVATE = 12;
    const FILE_DEACTIVATE = 13;
    const MEETING_CREATE = 14;
    const MEETING_DELETE = 15;
    const MEETING_UPDATE_AGENDA = 16;
    const MEETING_UPDATE_LOCATION = 17;
    const MEETING_UPDATE_TIME = 18;
    const NOTIFICATION_CREATE = 19;
    const NOTIFICATION_DISMISS = 20;
    const PUBLICATION_CREATE = 21;
    const PUBLICATION_DELETE = 22;
    const PUBLICATION_STATUS_CANCELLED = 23;
    const PUBLICATION_STATUS_PUBLISHED = 24;
    const PUBLICATION_STATUS_WIP = 25;
    const PUBLICATION_UPDATE = 26;
    const SERIAL_CREATE = 27;
    const SERIAL_DELETE = 28;
    const SERIAL_UPDATE = 29;
    const SUBMISSION_CREATE = 30;
    const SUBMISSION_DELETE = 31;
    const SUBMISSION_LICENSE = 32;
    const SUBMISSION_REVISION_ANONYMIZE = 33;
    const SUBMISSION_REVISION_UPLOAD = 34;
    const SUBMISSION_STATUS_CANCELLED = 35;
    const SUBMISSION_STATUS_FINAL = 36;
    const SUBMISSION_STATUS_INITIAL = 37;
    const SUBMISSION_STATUS_PUBLISH = 38;
    const SUBMISSION_STATUS_REJECTED = 39;
    const SUBMISSION_STATUS_REVISION = 40;
    const SUBMISSION_UPDATE = 41;
    const SUBMISSION_UPDATE_PUBLICATION = 42;
    const USER_ACTIVATE = 44;
    const USER_CREATE = 45;
    const USER_DEACTIVATE = 46;
    const USER_DELETE = 47;
    const USER_FORGOT_PASSWORD_REQUEST = 43;
    const USER_LOGIN = 48;
    const USER_PERMISSION_ADD = 49;
    const USER_PERMISSION_REMOVE = 50;
    const USER_UPDATE = 51;
    const USER_UPDATE_EMAIL = 52;
    const USER_UPDATE_PASSWORD = 53;

    /**
     * @var int
     */
    private $eventID;
    /**
     * @var array ["id"=>int,"name"=>string,"description"=>string]
     */
    private $eventtype;
    /**
     * @var File
     */
    private $file;
    /**
     * Foreign key referencing primary key in the table specified by $table.
     * Thus, it can vary, and be either an string or an int.
     *
     * @var int|string
     */
    private $identifier;
    /**
     * An array storing both the name of the column, and the value of the identifier stored in the table referred to
     * in $table;
     *
     * @var array ["name"=string,"id"=string|int]
     */
    private $table;
    /**
     * @var string
     */
    private $timestamp;
    /**
     * @var User
     */
    private $user;

    public function __construct()
    {
        /* This segment of code originally written by rayro@gmx.de
         * http://php.net/manual/en/language.oop5.decon.php
         */
        $a = func_get_args();
        $i = func_num_args();
        if ($i > 2) {
            $i = 2;
        }
        if (method_exists($this, $f = '__construct' . $i)) {
            call_user_func_array(array($this, $f), $a);
        }
    }

    /**
     * Constructor for retrieving existing logs from the database.
     *
     * @param int $eventID
     */
    public function __construct1(int $eventID)
    {
        $dbc = new DatabaseConnection();
        $params = ["i", $eventID];
        $event = $dbc->query("select", "SELECT * FROM `log` WHERE `pkLogID`=?", $params);

        if ($event) {
            $this->setEventID($eventID);
            $this->setEventType($event["fkEventID"], self::MODE_ID);
            $this->setTimestamp($event["dtTimestamp"]);
            $this->setUser(User::load($event["fkUserID"], User::MODE_DBID));

            if (isset($event["nmTable"])) {
                $this->setTable($event["nmTable"]);
            }
            if (isset($event["fkIdentifier"])) {
                $this->setIdentifier($event["fkIdentifier"]);
            }
            if (isset($event["fkFilename"])) {
                $this->setFile(new File($event["fkFilename"]));
            }
        }
    }

    /**
     * Constructor for new logs added via the Logger.
     *
     * @param User $user
     * @param int $eventTypeID
     * @param File|null $file
     * @param string|null $table
     * @param string|int|null $identifier
     * @param int|null $timestamp
     */
    public function __construct2(User $user, int $eventTypeID, File $file = null, string $table = null, $identifier = null, int $timestamp = null)
    {
        $this->setUser($user);
        $this->setEventType($eventTypeID);
        $this->setFile($file);
        $this->setTable($table);
        $this->setIdentifier($identifier);
        $this->setTimestamp($timestamp);
    }

    /**
     * @return int
     */
    public function getEventID(): int
    {
        return $this->eventID;
    }

    /**
     * @param int $mode
     * @return string|int
     */
    public function getEventType(int $mode = self::MODE_NAME)
    {
        switch ($mode) {
            case self::MODE_ID:
                return $this->eventtype["id"];
            case self::MODE_DESCRIPTION:
                return $this->eventtype["description"];
            default:
                return $this->eventtype["name"];
        }
    }

    /**
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @return int|string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param string|int $eventType
     * @param int $mode
     * @return bool
     * @throws Exception|InvalidArgumentException
     */
    public function setEventType($eventType, int $mode = self::MODE_ID): bool
    {
        $dbc = new DatabaseConnection();
        if ($mode === self::MODE_ID) {
            $params = ["i", $eventType];
            $event = $dbc->query("select", "SELECT `nmEvent`, `txDescription` FROM `event` WHERE `pkEventID`=?", $params);
            if ($event) {
                $this->eventtype = ["id" => $eventType, "name" => $event["nmEvent"], "description" => $event["txDescription"]];
                return true;
            } else {
                throw new Exception("LogEvent->setEventType($eventType) -  Unable to select from database");
            }
        } else if ($mode === self::MODE_NAME) {
            $params = ["s", $eventType];
            $event = $dbc->query("select", "SELECT `pkEventID`, `txDescription` FROM `event` WHERE `nmEvent` = ?", $params);
            if ($event) {
                $this->eventtype = ["id" => $event["pkEventID"], "name" => $eventType, "description" => $event["txDescription"]];
            }
        }
    }

    /**
     * @param File $file
     * @return bool
     */
    public function setFile(File $file=null): bool
    {
        if (!isset($file) or $file->isInDatabase()) {
            $this->file = $file;
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int|string|null $identifier
     * @return bool
     * @throws Exception
     */
    public function setIdentifier($identifier = null): bool
    {
        if ($identifier === null) {
            $this->identifier = null;
            return true;
        } else if ($this->getTable() === null and $identifier) {
            throw new Exception("File->setIdentifier($identifier) - Unable to set foreign key identifier when table is null");
        } else {
            $dbc = new DatabaseConnection();
            $identifierType = $this->getIdentifierType() == "int" ? "i" : "s";
            $params = ["$identifierType", $identifier];
            $exists = $dbc->query("exists", "SELECT * FROM `" . $this->getTable() . "` WHERE `" . $this->getIdentifierName() . "` = ?", $params);

            if ($exists) {
                $this->identifier = $identifier;
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param string|null $table
     * @return bool
     * @throws Exception|InvalidArgumentException
     */
    public function setTable(string $table = null): bool
    {
        // Setting a table to null means that any identifier stored in the log is meaningless, so it should also be set to null
        if ($table === null) {
            $this->table = null;
            // This if block is used to make sure an infinite recursion loop doesn't occur between setIdentifier and setTable
            // when the input to either function is null.
            if ($this->getIdentifier() !== null) {
                $this->setIdentifier();
            }
            return true;
            // Do nothing if the proposed table is the same as the current table.
        } else if ($this->getTable() === $table) {
            return true;
        } else {
            $dbc = new DatabaseConnection();
            // Gets a list of tables, as setTable should only allow you to set an existing table as the table that this
            // LogEvent references.
            $tables = $dbc->query("select multiple", "SHOW TABLES");

            // If a good result returns from the database, ...
            if ($tables) {
                $tableList = [];
                // ... then properly sort out the database output into an array (is returned by default as a 2d array,
                // where each row returned is an array whose elements are indexed by column names).
                foreach ($tables as $table) {
                    $tableList[] = $table["Tables_in_metacognitiodb"];
                }
                // Finally, perform the check to see if the proposed table is a valid one
                if (in_array($table, $tableList)) {
                    // Make sure $identifier is set to null once $table changes, since the old value will have no meaning.
                    $this->table = $table;
                    $this->setIdentifier();
                    return true;
                } else {
                    throw new InvalidArgumentException("LogEvent->setTable($table) - Unable to find table in database");
                }
            } else {
                throw new Exception("LogEvent->setTable($table) - Unable to select from database");
            }
        }
    }

    /**
     * @param int $time
     * @return void
     */
    public function setTimestamp(int $time = null): void
    {
        if (isset($time)) {
            $this->timestamp = date('Y-m-d H:i:s', $time);
        } else {
            $this->timestamp = date('Y-m-d H:i:s', time());
        }
    }

    /**
     * @param User $user
     * @return bool
     */
    public function setUser(User $user): bool
    {
        if($user->isInDatabase()) {
            $this->user = $user;
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getIdentifierName(): string
    {
        $dbc = new DatabaseConnection();
        $params = ["ss", $dbc->getTableSchema(), $this->getTable()];
        $name = $dbc->query("select", "SELECT `COLUMN_NAME`
                                                    FROM `information_schema`.`COLUMNS`
                                                    WHERE (`TABLE_SCHEMA` = ?)
                                                      AND (`TABLE_NAME` = ?)
                                                      AND (`COLUMN_KEY` = 'PRI')
                                                      LIMIT 1", $params);
        if ($name) {
            return $name["COLUMN_NAME"];
        } else {
            throw new Exception("LogEvent->getIdentifierName() - Unable to select from database: information_schema");
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getIdentifierType(): string
    {
        $dbc = new DatabaseConnection();
        $params = ["sss", $dbc->getTableSchema(), $this->getTable(), $this->getIdentifierName()];
        $type = $dbc->query("select", "SELECT `DATA_TYPE` 
                                                    FROM `information_schema`.`COLUMNS` 
                                                    WHERE `TABLE_SCHEMA`= ? 
                                                      AND `TABLE_NAME`= ? 
                                                      AND `COLUMN_NAME` = ?", $params);
        if ($type) {
            return $type["DATA_TYPE"];
        } else {
            throw new Exception("LogEvent->getIdentifierType() - Unable to select from database: information_schema");
        }
    }

    /**
     * @param int $eventID
     * @return bool
     */
    private function setEventID(int $eventID): void
    {
        $this->eventID = $eventID;
    }

}