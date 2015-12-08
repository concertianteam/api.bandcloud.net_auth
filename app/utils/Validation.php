<?php

class Validation
{

    // account id from database - Global Variable
    static $idAccount;

    /**
     * Verifying requred params
     * $requiredParamsArray = array('param1', 'param2', '...', 'paramN');
     */
    function verifyRequiredParams($requiredParamsArray)
    {
        $success = true;
        $missingParams = "";
        $requestParams = array();
        $requestParams = $_REQUEST;

        // Handling PUT request params
        if ($_SERVER ['REQUEST_METHOD'] == 'PUT') {
            $app = \Slim\Slim::getInstance();
            parse_str($app->request()->getBody(), $requestParams);
        }

        foreach ($requiredParamsArray as $param) {
            if (!isset ($requestParams [$param]) || strlen(trim($requestParams [$param])) <= 0) {
                $success = false;
                $missingParams .= $param . ', ';
            }
        }

        if (!$success) {
            // Required field(s) are missing or empty
            // echo error json and stop the app
            $response = array();
            $app = \Slim\Slim::getInstance();
            $response ["success"] = $success;
            $response ["message"] = 'Required param(s) ' . substr($missingParams, 0, -2) . ' are missing or empty!';
            ClientEcho::echoResponse(BAD_REQUEST, $response);
            $app->stop();
        }
    }

    /**
     * Middle layer to autenticate every request
     * Checking if the request has valid API key in the 'Authorization' header
     */
    public static function authenticate($apiKey)
    {
        // Getting request headers
        /*$headers = apache_request_headers ();
        $response = array ();
        $app = \Slim\Slim::getInstance ();

        // Verifying Authorization header*/
        if (isset ($apiKey /*$headers ['Authorization']*/)) {
            $dbHandler = new DbHandler ();

            // get the api key
            //$apiKey = $headers ['Authorization'];
            // validating api key
            if (!$dbHandler->isValidApiKey($apiKey)) {
                // api key does not exist in database
                $response ["success"] = false;
                $response ["message"] = "Access Denied. Invalid Api Key!";
                return ClientEcho::echoResponse(UNAUTHORIZED, $response);
                //$app->stop ();
            } else {
                // get account id
                $mIdAccount = $dbHandler->getAccountId($apiKey);
                if ($mIdAccount != NULL)
                    Validation::$idAccount = $mIdAccount;
            }
        } else {
            // api key is missing in header
            $response ["success"] = false;
            $response ["message"] = "Api key is missing!";
            return ClientEcho::echoResponse(BAD_REQUEST, $response);
            //$app->stop ();
        }
    }

    public static function isPaid($account)
    {
        //chceck if it's paid
        $currentDate = date_create(date('Y-m-d'));
        $dateFromDb = date_create($account['date']);
        if ($currentDate > $dateFromDb) {
            $diff = date_diff($currentDate, $dateFromDb);
            //payment OK - return idAccount
            if ($diff->days <= 31) {
                $response['status'] = PAID;
                $response['idAccount'] = $account['idAccount'];
                $response ['apiKey'] = $account['apiKey'];
            } else { // not paid for this account
                $response['status'] = NOT_PAID;
                $response['idAccount'] = $account['idAccount'];
            }
        } else {
            $response['status'] = ERROR;
        }
        return $response;
    }
}