<?php

class DbHandler
{
    private $connection;

    function __construct()
    {
        // open database connection
        $this->connection = Database::getInstance();
    }

    function __destruct()
    {
        $this->connection = null;
    }

    /* ------------------------------Validation--------------------------------------- */
    public function getAccountId($apiKey)
    {
        $STH = $this->connection->prepare("SELECT idAccount FROM Accounts WHERE apiKey=:apiKey");
        $STH->bindParam(':apiKey', $apiKey);
        if ($STH->execute()) {
            $account = $STH->fetch();
            $idAccount = $account ['idAccount'];
            return $idAccount;
        } else {
            return NULL;
        }
    }

    /**
     * Validating account api key
     * If the api key is there in db, it is a valid key
     *
     * @param String $apiKey
     *            account api key
     * @return boolean
     */
    public function isValidApiKey($apiKey)
    {
        $STH = $this->connection->prepare("SELECT idAccount FROM Accounts WHERE apiKey =:apiKey");
        $STH->bindParam(':apiKey', $apiKey);
        $STH->execute();
        //$num_rows = $STH->rowCount();

        return $STH->rowCount() > 0 ? $STH->fetch()['idAccount'] : ERROR;

        //return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for bar Api key
     */
    private function generateApiKey()
    {
        return md5(uniqid(rand(), true));
    }

    /**
     * Checking Account login
     *
     * @param String $email
     *            Account login email
     * @param String $password
     *            Account login password
     * @return boolean Account login status success/fail
     */
    public function checkLogin($email, $password)
    {
        // fetching Account by email
        $STH = $this->connection->prepare("SELECT password, apiKey FROM Accounts WHERE email = :email");
        $STH->bindParam(':email', $email);
        $STH->execute();

        $row = $STH->fetch();

        if ($row > 0) {
            // Found Account with the email
            // Now verify the password
            if (PassHash::check_password($row ['password'], $password) && $this->setApiKey($email)) {
                // Account password is correct, apiKey created
                return TRUE;
            } else {
                // Account password is incorrect
                return FALSE;
            }
        } else {
            // Account not existed with the email
            return FALSE;
        }
    }

    private function setApiKey($email)
    {
        // Generating API key
        $apiKey = $this->generateApiKey();

        $STH = $this->connection->prepare("UPDATE Accounts SET apiKey = :apiKey
						WHERE email= :email;");

        $STH->bindParam(':apiKey', $apiKey);
        $STH->bindParam(':email', $email);

        return $STH->execute();
    }

    /**
     * Fetching Account by email
     *
     * @param String $email
     *            Account email
     */
    public function getAccountByEmail($email)
    {
        $STH = $this->connection->prepare("SELECT idAccount, apiKey
            FROM Accounts WHERE email=:email");
        $STH->bindParam(':email', $email);

        if ($STH->execute()) {
            $account = $STH->fetch();
            return $account;
        } else {
            return NULL;
        }
    }
    /* ------------------------------Validation--------------------------------------- */

    /* -------------------------------Account----------------------------------------- */
    /**
     * Creating new Account
     *
     * @param String $confirmCode
     *            confirmation code
     * @param String $email
     *            bar login email
     * @param String $password
     *            bar login password
     * @return constant ('ACCOUNT_CREATED_SUCCESSFULLY', 'ACCOUNT_CREATE_FAILED', 'ACCOUNT_ALREADY_EXIST')
     */
    public function createAccount($confirmCode, $email, $password)
    {
        // First check if account already exist in db
        if (!$this->isAccountInDb($email)) {
            // Generating password hash
            $passwordHash = PassHash::hash($password);

            // Generating API key
            $apiKey = $this->generateApiKey();

            $STH = $this->connection->prepare("INSERT INTO Accounts(email, password, apiKey, confirmationCode)
					VALUES(:email, :password, :apiKey, :confirmCode);");

            $STH->bindParam(':email', $email);
            $STH->bindParam(':password', $passwordHash);
            $STH->bindParam(':apiKey', $apiKey);
            $STH->bindParam(':confirmCode', $confirmCode);

            $result = $STH->execute();

            $accountId = $this->connection->lastInsertId();

            // Check for successful insertion
            if ($result) {
                // Account successfully inserted
                return $accountId;
            } else {
                // Failed to create Account
                return ACCOUNT_CREATE_FAILED;
            }
        } else {
            // Account with same email already existed in the db
            return ACCOUNT_ALREADY_EXIST;
        }
    }

    /**
     * Checking for duplicate account by email address
     *
     * @param String $email
     *            email to check in db
     * @return boolean
     */
    private function isAccountInDb($email)
    {
        $STH = $this->connection->prepare("SELECT idAccount from Accounts WHERE email = :email");
        $STH->bindParam(':email', $email);
        $STH->execute();
        $num_rows = $STH->rowCount();
        return $num_rows > 0;
    }

    public function logout()
    {
        $STH = $this->connection->prepare("UPDATE Accounts SET apiKey = NULL WHERE idAccount=:idAccount");
        $STH->bindParam(':idAccount', Validation::$idAccount);

        return $STH->execute();
    }

    public function changePassword($oldPwd, $newPwd)
    {
        // fetching Account by email
        $STH = $this->connection->prepare("SELECT password FROM Accounts WHERE idAccount = :idAccount");
        $STH->bindParam(':idAccount', Validation::$idAccount);
        $STH->execute();

        $row = $STH->fetch();

        if ($row > 0) {
            // Found Account with the email
            // Now verify the password
            if (PassHash::check_password($row ['password'], $oldPwd) && $this->setNewPassword($newPwd)) {
                // Account password is correct
                return TRUE;
            } else {
                // Account password is incorrect
                return FALSE;
            }
        } else {
            // Account not existed with the email
            return FALSE;
        }
    }

    private function setNewPassword($newPwd)
    {
        // Generating password hash
        $passwordHash = PassHash::hash($newPwd);
        $STH = $this->connection->prepare("UPDATE Accounts SET password = :password WHERE idAccount= :idAccount;");

        $STH->bindParam(':password', $passwordHash);
        $STH->bindParam(':idAccount', Validation::$idAccount);

        return $STH->execute();
    }

    /* -------------------------------Account----------------------------------------- */
}