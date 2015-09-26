<?php
define ( "APP_ROOT", __DIR__ );

require_once (APP_ROOT . "/app/core/Config.php");
require_once (APP_ROOT . "/app/core/Database.php");
require_once (APP_ROOT . "/app/core/DbHandler.php");
require_once (APP_ROOT . "/app/utils/ClientEcho.php");
require_once (APP_ROOT . "/app/utils/Validation.php");
require_once (APP_ROOT . "/app/utils/PassHash.php");
require_once (APP_ROOT . "/config/statusCodes.php");
require_once (APP_ROOT . "/config/responseTypes.php");
require_once (APP_ROOT . "/config/constants.php");
require (APP_ROOT . "/libs/Slim/Slim.php");

\Slim\Slim::registerAutoloader ();
$app = new \Slim\Slim ();

$app->get ( '/', function () use($app) {
	// test
	Database::getInstance (); // ok?
	echo ('idze');
} );

/* ----------------------REGISTER METHODS------------------------- */

/**
 * Account Registration
 * url - /register
 * method - POST
 * params - (valid) email, password
 */
$app->post ( '/register', function () use($app) {
	// check for required params
	$validation = new Validation ();
	$validation->verifyRequiredParams ( array (
			'email',
			'password' 
	) );
	
	$response = array ();
	
	// reading post params
	$email = $app->request->post ( 'email' );
	$password = $app->request->post ( 'password' );
	
	$dbHandler = new DbHandler ();
	$accountId = $dbHandler->createAccount ( $email, $password );
	
	if ($accountId == ACCOUNT_CREATE_FAILED) {
		$response ["success"] = FALSE;
		$response ["status"] = ACCOUNT_CREATE_FAILED;
		ClientEcho::echoResponse ( OK, $response );
	} else if ($accountId == ACCOUNT_ALREADY_EXIST) {
		$response ["success"] = FALSE;
		$response ["status"] = ACCOUNT_ALREADY_EXIST;
		ClientEcho::echoResponse ( OK, $response );
	} else {
		$response ["success"] = TRUE;
		$response ["accountId"] = $accountId;
		ClientEcho::echoResponse ( CREATED, $response );
	}
} );

/**
 * Login
 * url - /auth
 * method - POST
 * params - email, password
 */
$app->post ( '/auth', function () use($app) {
	// check for required params
	$validation = new Validation ();
	$validation->verifyRequiredParams ( array (
			'email',
			'password' 
	) );
	
	// reading post params
	$email = $app->request->post ( 'email' );
	$password = $app->request->post ( 'password' );
	$response = array ();
	
	$dbHandler = new DbHandler ();
	// check for correct email and password
	if ($dbHandler->checkLogin ( $email, $password )) {
		// get Account by email
		$account = $dbHandler->getAccountByEmail ( $email );
		
		if ($account != NULL) {
			$response ["success"] = TRUE;
			$response ['idAccount'] = $account ['idAccount'];
			$response ['apiKey'] = $account ['apiKey'];
			
			ClientEcho::echoResponse ( OK, $response );
		} else {
			// unknown error occured
			$response ['success'] = FALSE;
			$response ['status'] = ERROR;
			ClientEcho::echoResponse ( INTERNAL_SERVER_ERROR, $response );
		}
	} else {
		// account credentials are wrong
		$response ['success'] = FALSE;
		$response ['status'] = INVALID_CREDENTIALS;
		ClientEcho::echoResponse ( UNAUTHORIZED, $response );
	}
} );

/**
 * Logout
 * url - /auth
 * method - DELETE
 * params - api_key
 */
$app->delete ( '/auth', array (
		'Validation',
		'authenticate' 
), function () use($app) {
	$dbHandler = new DbHandler ();	
	
	// logout account
	$res = $dbHandler->logout ();
	
	// chceck if logging out was successfull
	if ($res) {
		// account was logged out
		$response ['success'] = TRUE;
		ClientEcho::echoResponse ( 'SUCCESS', $response );
	} else {
		// unknown error occurred
		$response ['success'] = FALSE;
		$response ['status'] = ERROR;
		ClientEcho::echoResponse ( INTERNAL_SERVER_ERROR, $response );
	}
} );

/**
 * Change password
 * url - /changepassword
 * method - POST
 * params - apiKey, oldPwd, newPwd
 */
$app->post ( '/changepassword', array (
		'Validation',
		'authenticate' 
), function () use($app) {
	$validation = new Validation ();
	$dbHandler = new DbHandler ();
	$validation->verifyRequiredParams ( array (
			'oldPwd',
			'newPwd' 
	) );
	
	// reading post params
	$oldPwd = $app->request->post ( 'oldPwd' );
	$newPwd = $app->request->post ( 'newPwd' );
	
	$res = $dbHandler->changePassword ( $oldPwd, $newPwd );
	
	$response = array ();
	if ($res) {
		$response ["success"] = TRUE;
	} else {
		$response ["success"] = FALSE;
	}
	ClientEcho::echoResponse ( OK, $response );
} );
$app->run ();