<?php
define("APP_ROOT", __DIR__);

require_once(APP_ROOT . "/app/core/Config.php");
require_once(APP_ROOT . "/app/core/Database.php");
require_once(APP_ROOT . "/app/core/DbHandler.php");
require_once(APP_ROOT . "/app/utils/ClientEcho.php");
require_once(APP_ROOT . "/app/utils/Validation.php");
require_once(APP_ROOT . "/app/utils/PassHash.php");
require_once(APP_ROOT . "/config/statusCodes.php");
require_once(APP_ROOT . "/config/responseTypes.php");
require_once(APP_ROOT . "/config/constants.php");

class MySoapServer
{
    /**
     * Account Registration
     * params - (valid) email, password
     */

    function register($confirmCode, $email, $password)
    {
        if (!isset($email, $password)) return $this->unknownError();
        $dbHandler = new DbHandler ();
        $accountId = $dbHandler->createAccount($confirmCode, $email, $password);

        if ($accountId == ACCOUNT_CREATE_FAILED) {
            $response ["success"] = FALSE;
            $response ["status"] = ACCOUNT_CREATE_FAILED;
        } else if ($accountId == ACCOUNT_ALREADY_EXIST) {
            $response ["success"] = FALSE;
            $response ["status"] = ACCOUNT_ALREADY_EXIST;
        } else {
            $response ["success"] = TRUE;
            $response ["accountId"] = $accountId;
        }
        return ClientEcho::echoResponse($response);
    }

    /**
     * Login
     * params - email, password
     */

    function checkLogin($email, $password)
    {

        if (!isset($email, $password)) return $response['status'] = INVALID_CREDENTIALS;

        $dbHandler = new DbHandler ();
        // check for correct email and password
        if ($dbHandler->checkLogin($email, $password)) {
            // get Account by email
            $account = $dbHandler->getAccountByEmail($email);

            if ($account != NULL) {
                return Validation::isPaid($account);
                /* $response ["success"] = TRUE;
                 $response ['idAccount'] = $account ['idAccount'];
                 $response ['apiKey'] = $account ['apiKey'];

                 return ClientEcho::echoResponse($response);*/
            } else {
                // unknown error occured
                return $response['status'] = ERROR;
            }
        }
    }

    /**
     * Logout
     * params - api_key
     */

    function logout($apiKey)
    {
        Validation::authenticate($apiKey);
        $dbHandler = new DbHandler ();

        // logout account
        $res = $dbHandler->logout();

        // chceck if logging out was successfull
        if ($res) {
            // account was logged out
            $response ['success'] = TRUE;
            return ClientEcho::echoResponse($response);
        } else {
            // unknown error occurred
            $response ['success'] = FALSE;
            $response ['status'] = ERROR;
            return ClientEcho::echoResponse($response);
        }

    }

    /**
     * Change password
     * params - apiKey, oldPwd, newPwd
     */

    function changePassword($apiKey, $oldPwd, $newPwd)
    {
        if (!isset($oldPwd, $newPwd)) return $this->unknownError();
        Validation::authenticate($apiKey);
        $dbHandler = new DbHandler ();

        $res = $dbHandler->changePassword($oldPwd, $newPwd);

        $response = array();
        if ($res) {
            $response ["success"] = TRUE;
        } else {
            $response ["success"] = FALSE;
        }
        return ClientEcho::echoResponse($response);

    }

    /**
     * Validate apiKey
     * @return boolean
     */

    function validateApiKey($apiKey)
    {
        $dbHandler = new DbHandler();
        return $dbHandler->isValidApiKey($apiKey);
    }

    private function unknownError()
    {
        $response ['success'] = FALSE;
        $response ['status'] = ERROR;
        return ClientEcho::echoResponse($response);
    }

}

$server = new SoapServer(NULL, ['uri' => 'api.bandcloud.net/auth/MySoapServer.php']);
$server->setClass('MySoapServer');
$server->handle();