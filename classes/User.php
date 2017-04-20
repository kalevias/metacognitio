<?php

/*
* A user class to keep track of everything of importance regarding a
* user's information.
*/

class User
{
    /**
     * Constants used during setProvince and setCountry to increase input identification speed and accuracy
     */
    const MODE_DBID = 1;
    const MODE_ISO = 2;
    const MODE_NAME = 3;

    private $altEmail;
    private $city;
    private $country;
    private $email;
    private $fName;
    private $gradSemester;
    private $gradYear;
    private $hash;  //array("pkStateID"=>int,   "idISO"=>string,    "nmName"=>string)
    private $isActive;   //array("pkCountryID"=>int, "idISO"=>string,    "nmName"=>string,   "idPhoneCode"=>int)
    private $isInDatabase;
    private $lName;
    private $permissions;
    private $phone;
    private $province;
    private $salt;
    private $streetAddress;
    private $userID;
    private $zip;

    /**
     * User constructor.
     */
    public function __construct()
    {
        //This segment of code originally written by rayro@gmx.de
        //http://php.net/manual/en/language.oop5.decon.php
        $a = func_get_args();
        $i = func_num_args();
        if (method_exists($this, $f = '__construct' . $i)) {
            call_user_func_array(array($this, $f), $a);
        }
    }

    /**
     * Loads a user from the database.
     *
     * @param string|int $identifier May be either user email or ID
     * @param int $mode
     * @return null|User
     */
    public static function load($identifier, int $mode = self::MODE_NAME)
    {
        try {
            return new User($identifier, $mode);
        } catch (InvalidArgumentException $iae) {
            return null;
        }
    }

    /**
     * User constructor (13 arguments).
     *
     * @param string $fName
     * @param string $lName
     * @param string $email
     * @param string $altEmail
     * @param string $streetAddress
     * @param string $city
     * @param string $province
     * @param int $zip
     * @param int $phone
     * @param string $gradSemester
     * @param int $gradYear
     * @param string $password
     * @param bool $isActive
     * @throws Exception
     */
    public function __construct13(string $fName, string $lName, string $email, string $altEmail, string $streetAddress, string $city, string $province, int $zip, int $phone, string $gradSemester, int $gradYear, string $password, bool $isActive)
    {
        $dbc = new DatabaseConnection();
        $params = ["s", $province];
        $provinceResult = $dbc->query("select", "SELECT * FROM `province` WHERE `idISO`=?", $params);
        if ($provinceResult) {
            $params = ["i", $provinceResult["fkCountryID"]];
            $country = $dbc->query("select", "SELECT * FROM `country` WHERE `pkCountryID`=?", $params);

            if ($country) {
                $result = [
                    $this->setFName($fName),
                    $this->setLName($lName),
                    $this->setEmail($email),
                    $this->setAltEmail($altEmail),
                    $this->setStreetAddress($streetAddress),
                    $this->setCity($city),
                    $this->setProvince($provinceResult["idISO"], self::MODE_ISO),
                    $this->setCountry($country["idISO"]),
                    $this->setZip($zip),
                    $this->setPhone($phone),
                    $this->setGradsemester($gradSemester),
                    $this->setGradYear($gradYear),
                    $this->updatePassword($password),
                    $this->setIsActive($isActive),
                ];
                if (in_array(false, $result, true)) {
                    throw new Exception("User->__construct13($fName, $lName, $email, $altEmail, $streetAddress, $city, $province, $zip, $phone, $gradSemester, $gradYear, $password, $isActive) - Unable to construct User object; variable assignment failure");
                }
                $this->permissions = [];
                $this->isInDatabase = false;
            }
        } else {
            throw new InvalidArgumentException("User->__construct13($fName, $lName, $email, $altEmail, $streetAddress, $city, $province, $zip, $phone, $gradSemester, $gradYear, $password, $isActive) - Unable to construct User object; Invalid province");
        }
    }

    /**
     * User Constructor (2 arguments).
     *
     * @param $identifier
     * @param int $mode
     * @throws Exception
     */
    public function __construct2($identifier, int $mode = self::MODE_NAME)
    {
        $dbc = new DatabaseConnection();
        if ($mode === self::MODE_DBID) {
            $params = ["i", $identifier];
            $user = $dbc->query("select", "SELECT * FROM `user` WHERE `pkUserID`=?", $params);
        } else {
            $params = ["s", $identifier];
            $user = $dbc->query("select", "SELECT * FROM `user` WHERE `txEmail`=?", $params);
        }

        if ($user) {
            $params = ["i", $user["fkProvinceID"]];
            $province = $dbc->query("select", "SELECT * FROM `province` WHERE `pkStateID`=?", $params);
            if ($province) {
                $params = ["i", $province["fkCountryID"]];
                $country = $dbc->query("select", "SELECT * FROM `country` WHERE `pkCountryID`=?", $params);

                if ($country) {
                    $result = [
                        $this->setUserID($user["pkUserID"]),
                        $this->setFName($user["nmFirst"]),
                        $this->setLName($user["nmLast"]),
                        $this->setEmail($user["txEmail"]),
                        $this->setAltEmail($user["txEmailAlt"]),
                        $this->setStreetAddress($user["txStreetAddress"]),
                        $this->setCity($user["txCity"]),
                        $this->setProvince($province["idISO"], self::MODE_ISO),
                        $this->setZip($user["nZip"]),
                        $this->setPhone($user["nPhone"]),
                        $this->setGradsemester($user["enGradSemester"]),
                        $this->setGradYear($user["dtGradYear"]),
                        $this->setSalt($user["blSalt"]),
                        $this->setHash($user["txHash"]),
                        $this->setIsActive($user["isActive"]),
                    ];
                    if(in_array(false, $result, true)) {
                        throw new Exception("User->__construct2($identifier, $mode) - Unable to construct User object; variable assignment failure");
                    }
                    $this->isInDatabase = true;
                    $this->removeAllPermissions();
                    $params = ["i", $user["pkUserID"]];
                    $permissions = $dbc->query("select multiple", "SELECT `fkPermissionID` FROM `userpermissions` WHERE `fkUserID` = ?", $params);
                    if ($permissions) {
                        foreach ($permissions as $permission) {
                            $this->addPermission(new Permission($permission["fkPermissionID"]));
                        }
                    }
                } else {
                    throw new Exception("User->__construct2($identifier, $mode) - Unable to select from database");
                }
            } else {
                throw new Exception("User->__construct2($identifier, $mode) - Unable to select from database");
            }
        } else {
            throw new InvalidArgumentException("User->__construct2($identifier, $mode) - User not found");
        }
    }

    /**
     * Adds a permission to the user's permissions.
     *
     * @param Permission $permission
     * @return bool|int
     * @throws InvalidArgumentException()
     */
    public function addPermission(Permission $permission)
    {
        if (in_array($permission, $this->getPermissions())) {
            return false;
        } else {
            return array_push($this->permissions, $permission);
        }
    }

    /**
     * @return mixed
     */
    public function getAltEmail()
    {
        return $this->altEmail;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $identifier
     * @return mixed
     */
    public function getCountry(string $identifier)
    {
        $identifier = strtolower($identifier);
        switch ($identifier) {
            case "iso":
            case "idiso":
                return $this->country["idISO"];
            case "phone":
            case "idphonecode":
                return $this->country["idPhoneCode"];
            default:
                return $this->country["nmName"];
        }
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getFName()
    {
        return $this->fName;
    }

    /**
     * @return mixed
     */
    public function getGradSemester()
    {
        return $this->gradSemester;
    }

    /**
     * @return mixed
     */
    public function getGradYear()
    {
        return $this->gradYear;
    }

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return mixed
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @return mixed
     */
    public function getLName()
    {
        return $this->lName;
    }

    /**
     * @return Permission[]
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param int $identifier
     * @return string|int
     */
    public function getProvince(int $identifier = self::MODE_NAME)
    {
        $identifier = strtolower($identifier);
        switch ($identifier) {
            case self::MODE_ISO:
                return $this->province["idISO"];
            case self::MODE_DBID:
                return $this->province["pkStateID"];
            default:
                return $this->province["nmName"];
        }
    }

    /**
     * @return mixed
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @return mixed
     */
    public function getStreetAddress()
    {
        return $this->streetAddress;
    }

    /**
     * @return mixed
     */
    public function getUserID()
    {
        return $this->userID;
    }

    /**
     * @return mixed
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param Permission $permission
     * @return bool
     * @throws InvalidArgumentException()
     */
    public function hasPermission(Permission $permission)
    {
        if ($permission instanceof Permission) {
            return in_array($permission, $this->getPermissions());
        } else {
            throw new InvalidArgumentException("User->hasPermission(" . $permission . ") - expected Permission: got " . (gettype($permission) == "object" ? get_class($permission) : gettype($permission)));
        }
    }

    /**
     * @return bool
     */
    public function isInDatabase()
    {
        return $this->isInDatabase;
    }

    /**
     * @return bool
     */
    public function removeAllPermissions()
    {
        $this->permissions = [];
        return true;
    }

    /**
     * @param Permission $permission
     * @return bool
     * @throws InvalidArgumentException()
     */
    public function removePermission(Permission $permission)
    {
        if ($permission instanceof Permission) {
            if (($key = array_search($permission, $this->getPermissions(), true)) !== false) {
                unset($this->permissions[$key]);
            } else {
                return false;
            }
        } else {
            throw new InvalidArgumentException("User->removePermission(" . $permission . ") - expected Permission: got " . (gettype($permission) == "object" ? get_class($permission) : gettype($permission)));
        }
    }

    /**
     * @param string $email
     * @return bool
     */
    public function setAltEmail(string $email = null): bool
    {
        if ($email === null) {
            $this->altEmail = null;
            return true;
        }
        $dbc = new DatabaseConnection();
        if (strlen($email) <= $dbc->getMaximumLength("user", "txEmailAlt") and $filtered = filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->altEmail = filter_var($filtered, FILTER_SANITIZE_EMAIL);
            return true;
        }
        return false;
    }

    /**
     * @param string $city
     * @return bool
     */
    public function setCity(string $city): bool
    {
        $dbc = new DatabaseConnection();
        if (strlen($city) <= $dbc->getMaximumLength("user", "txCity")) {
            $this->city = $city;
            return true;
        }
        return false;
    }

    /**
     * @param mixed $country ISO code, country name, or primary key in database
     * @param int $mode one of MODE_ISO, MODE_NAME, or MODE_DBID
     * @return bool true if successfully set, false otherwise
     */
    public function setCountry($country, int $mode = self::MODE_ISO): bool
    {
        if (gettype($country) == "string" or gettype($country) == "integer") {
            $dbc = new DatabaseConnection();
            if ($mode === self::MODE_NAME or $mode === self::MODE_ISO) {
                $country = strtoupper($country);
                $params = ["s", $country];
                if ($mode === self::MODE_ISO) {
                    $result = $dbc->query("select", "SELECT * FROM `country` WHERE `idISO`=?", $params);
                } else {
                    $result = $dbc->query("select", "SELECT * FROM `country` WHERE UPPER(`nmName`)=?", $params);
                }
            } else {
                $params = ["i", $country];
                $result = $dbc->query("select", "SELECT * FROM `country` WHERE `pkCountryID`=?", $params);
            }

            if ($result) {
                if (isset($this->province)) {
                    $params = ["s", $this->getProvince(self::MODE_ISO)];
                    $result2 = $dbc->query("select", "SELECT * FROM `province` WHERE `idISO`=?", $params);
                    if ($result2) {
                        $params = ["i", $result2["fkCountryID"]];
                        $result3 = $dbc->query("select", "SELECT * FROM `country` WHERE `pkCountryID`=?", $params);
                        if ($result3) {
                            if ($result3["pkCountryID"] != $result["pkCountryID"]) {
                                unset($this->province);
                            }
                        }
                    }
                }
                $this->country["pkCountryID"] = $result["pkCountryID"];
                $this->country["idISO"] = $result["idISO"];
                $this->country["nmName"] = $result["nmName"];
                $this->country["idPhoneCode"] = $result["idPhoneCode"];
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $email
     * @return bool
     */
    public function setEmail(string $email): bool
    {
        if ($email === null) {
            $this->email = null;
            return true;
        }
        $dbc = new DatabaseConnection();
        if (strlen($email) <= $dbc->getMaximumLength("user", "txEmail") and $filtered = filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->email = filter_var($filtered, FILTER_SANITIZE_EMAIL);
            return true;
        }
        return false;
    }

    /**
     * @param string $fName
     * @return bool
     */
    public function setFName(string $fName): bool
    {
        $dbc = new DatabaseConnection();
        if (strlen($fName) <= $dbc->getMaximumLength("user", "nmFirst")) {
            $this->fName = $fName;
            return true;
        }
        return false;
    }

    /**
     * @param string $gradSemester
     * @return bool
     */
    public function setGradSemester(string $gradSemester): bool
    {
        $dbc = new DatabaseConnection();
        $result = $dbc->query("select", "SELECT SUBSTRING(COLUMN_TYPE,5) AS `enum`
                                                        FROM `information_schema`.`COLUMNS`
                                                        WHERE `TABLE_SCHEMA` = '" . $dbc->getDatabaseName() . "' 
                                                            AND `TABLE_NAME` = 'user'
                                                            AND `COLUMN_NAME` = 'enGradSemester'");
        $value = trim($result["enum"], "()");
        $values = explode(",", $value);
        array_map("trim", $values, array_fill(0, count($values), "'"));
        if (in_array($gradSemester, $values)) {
            $this->gradSemester = $gradSemester;
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $gradYear
     * @return bool
     */
    public function setGradYear(int $gradYear): bool
    {
        $options = [
            "options" => [
                "min_range" => 1970,
                "max_range" => 3000
            ]
        ];
        if ($filtered = filter_var($gradYear, FILTER_VALIDATE_INT, $options)) {
            $this->gradYear = $filtered;
            return true;
        }
        return false;
    }

    /**
     * @param bool $isActive
     * @return bool
     */
    public function setIsActive(bool $isActive): bool
    {
        $this->isActive = $isActive;
        return true;
    }

    /**
     * @param string $lName
     * @return bool
     */
    public function setLName(string $lName): bool
    {
        $dbc = new DatabaseConnection();
        if (strlen($lName) <= $dbc->getMaximumLength("user", "nmLast")) {
            $this->lName = $lName;
            return true;
        }
        return false;
    }

    /**
     * @param int $phone
     * @return bool
     */
    public function setPhone(int $phone): bool
    {
        $phoneNumberUtil = libphonenumber\PhoneNumberUtil::getInstance();
        $phoneNumberObject = $phoneNumberUtil->parse($phone, $this->getCountry("ISO"));
        $isValid = $phoneNumberUtil->isValidNumberForRegion($phoneNumberObject, $this->getCountry("ISO"));

        if ($isValid) {
            $this->phone = $phoneNumberUtil->format($phoneNumberObject, \libphonenumber\PhoneNumberFormat::E164);
            return true;
        }
        return false;
    }

    /**
     * @param string $province ISO code or province name
     * @param int $mode Indicates input types, and must be either MODE_ISO or MODE_NAME
     * @return bool
     */
    public function setProvince(string $province, int $mode): bool
    {
        $province = strtoupper($province);
        $dbc = new DatabaseConnection();
        $params = ["s", $province];
        if ($mode === self::MODE_ISO) {
            $result = $dbc->query("select", "SELECT * FROM `province` WHERE `idISO`=?", $params);
        } else {
            $result = $dbc->query("select", "SELECT * FROM `province` WHERE UPPER(`nmName`)=?", $params);
        }

        if ($result) {
            $this->setCountry($result["fkCountryID"], self::MODE_DBID);
            $this->province["pkStateID"] = $result["pkStateID"];
            $this->province["idISO"] = $result["idISO"];
            $this->province["nmName"] = $result["nmName"];
            return true;
        }
    }

    /**
     * @param string $streetAddress
     * @return bool
     */
    public function setStreetAddress(string $streetAddress): bool
    {
        $dbc = new DatabaseConnection();
        if (strlen($streetAddress) <= $dbc->getMaximumLength("user", "txStreetAddress")) {
            $this->streetAddress = $streetAddress;
            return true;
        }
        return false;
    }

    /**
     * @param int $zip
     * @return bool
     */
    public function setZip(int $zip): bool
    {
        $dbc = new DatabaseConnection();
        if (strlen($zip) <= $dbc->getMaximumLength("user", "nZip")) {
            $this->zip = $zip;
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws Exception
     *
     * Pulls data stored in the database to the current User instance.
     */
    public function updateFromDatabase()
    {
        if ($this->isInDatabase()) {
            $this->__construct2($this->getUserID(), self::MODE_DBID);
            return true;
        } else {
            throw new LogicException("User->updateFromDatabase() - Unable to pull from database when User instance is not stored in database");
        }
    }

    /**
     * @param string $password
     * @return bool
     */
    public function updatePassword(string $password)
    {
        $saltedHash = Hasher::cryptographicHash($password);
        if (is_array($saltedHash)) {
            $r1 = $this->setSalt($saltedHash["salt"]);
            $r2 = $this->setHash($saltedHash["hash"]);
            return $r1 and $r2;
        }
        return false;
    }

    /**
     * @return bool indicates if the update was completed successfully
     *
     * Pushes data stored in current User instance to the database.
     */
    public function updateToDatabase()
    {
        $dbc = new DatabaseConnection();
        if ($this->isInDatabase()) {
            $params = [
                "ssssssiiisissi",
                $this->getFName(),
                $this->getLName(),
                $this->getEmail(),
                $this->getAltEmail(),
                $this->getStreetAddress(),
                $this->getCity(),
                $this->getProvince(self::MODE_DBID),
                $this->getZip(),
                $this->getPhone(),
                $this->getGradSemester(),
                $this->getGradYear(),
                $this->getSalt(),
                $this->getHash(),
                $this->getIsActive(),
                $this->getUserID()
            ];
            $result = $dbc->query("update", "UPDATE `user` SET 
                                      `nmFirst`=?,`nmLast`=?,`txEmail`=?,`txEmailAlt`=?,
                                      `txStreetAddress`=?,`txCity`=?,`fkProvinceID`=?,`nZip`=?,
                                      `nPhone`=?,`enGradSemester`=?,`dtGradYear`=?,`blSalt`=?,
                                      `txHash`=?,`isActive`=?
                                      WHERE `pkUserID`=?", $params);

            $params = ["i", $this->getUserID()];
            $result = ($result and $dbc->query("delete", "DELETE FROM `userpermissions` WHERE `fkUserID`=?", $params));

            foreach ($this->getPermissions() as $permission) {
                $params = ["ii", $permission->getPermissionID(), $this->getUserID()];
                $result = ($result and $dbc->query("insert", "INSERT INTO `userpermissions` (`fkPermissionID`,`fkUserID`) VALUES (?,?)", $params));
            }
        } else {
            $params = [
                "ssssssiiisissi",
                $this->getFName(),
                $this->getLName(),
                $this->getEmail(),
                $this->getAltEmail(),
                $this->getStreetAddress(),
                $this->getCity(),
                $this->getProvince(self::MODE_DBID),
                $this->getZip(),
                $this->getPhone(),
                $this->getGradSemester(),
                $this->getGradYear(),
                $this->getSalt(),
                $this->getHash(),
                $this->getIsActive()
            ];
            $result = $dbc->query("insert", "INSERT INTO `user` (`pkUserID`, 
                                          `nmFirst`, `nmLast`, `txEmail`, `txEmailAlt`, 
                                          `txStreetAddress`, `txCity`, `fkProvinceID`, `nZip`, 
                                          `nPhone`, `enGradSemester`, `dtGradYear`, `blSalt`, 
                                          `txHash`, `isActive`) 
                                          VALUES 
                                          (NULL,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", $params);

            $params = ["s", $this->getEmail()];
            $result2 = $dbc->query("select", "SELECT `pkUserID` FROM `user` WHERE `txEmail`=?", $params);

            $this->setUserID($result2["pkUserID"]);

            foreach ($this->getPermissions() as $permission) {
                $params = ["ii", $permission->getPermissionID(), $this->getUserID()];
                $result = ($result and $dbc->query("insert", "INSERT INTO `userpermissions` (`fkPermissionID`,`fkUserID`) VALUES (?,?)", $params));
            }

            $this->isInDatabase = $result;
        }

        return (bool)$result;
    }

    /**
     * @param string $hash
     * @return bool
     */
    private function setHash(string $hash): bool
    {
        $dbc = new DatabaseConnection();
        if (strlen($hash) == $dbc->getMaximumLength("user", "txHash")) {
            $this->hash = $hash;
            return true;
        }
        return false;
    }

    /**
     * @param string $salt
     * @return bool
     */
    private function setSalt(string $salt): bool
    {
        $dbc = new DatabaseConnection();
        if (strlen($salt) == $dbc->getMaximumLength("user", "blSalt")) {
            $this->salt = $salt;
            return true;
        }
        return false;
    }

    /**
     * @param int $userID
     * @return bool
     */
    private function setUserID(int $userID): bool
    {
        $options = [
            "options" => [
                "min_range" => 0,
                "max_range" => pow(2, 31) - 1
            ]
        ];
        if ($filtered = filter_var($userID, FILTER_VALIDATE_INT, $options)) {
            $this->userID = $filtered;
            return true;
        }
        return false;
    }
}