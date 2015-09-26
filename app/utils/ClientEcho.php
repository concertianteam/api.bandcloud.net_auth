<?php
class ClientEcho {
	/**
	 * Echoing json response to client
	 *
	 * $statusCode Http response code
	 * $response Json response
	 */
	static function echoResponse($response) {
		return json_encode ( $response );
	}
	
	/*
	 * @param $result - result from database
	 * @param $type - type of response (from responseTypes.php)
	 * @param $bands - array of bands if ($type == EVENT), NULL otherwise
	 */
	static function buildResponse($result, $type) {
		$response ["success"] = TRUE;
		// looping through result and preparing order array
		if (! count ( $result ) == 0) {
			switch ($type) {
				case EVENT :
					$response = ClientEcho::buildEventsResponse ( $result, $response );
					break;
				case BAND :
					$response = ClientEcho::buildBandsResponse ( $result, $response );
					break;
				case VENUE :
					$response = ClientEcho::buildVenuesResponse ( $result, $response );
					break;
				case FAVOURITE :
					$response = ClientEcho::buildFavsResponse ( $result, $response );
					break;
				default :
					$response ["success"] = FALSE;
					$response ["message"] = "Oops! An error occurred!";
					break;
			}
			
			ClientEcho::echoResponse ( OK, $response );
		} else {
			$response ["success"] = FALSE;
			$response ["message"] = "The requested resource doesn't exists";
			ClientEcho::echoResponse ( NOT_FOUND, $response );
		}
	}
	private static function buildEventsResponse($result, $response) {
		//$response["events"][] = array ();
		foreach ( $result as $row ) {
			$tmp = array ();
			
			$tmp ["idEvent"] = $row ["idEvents"];
			$tmp ["name"] = $row ["name"];
			$tmp ["datetime"] = $row ["datetime"];
			$tmp ["status"] = $row ["status"];
			$tmp ["visible"] = $row ["visible"];
			$response['events'][] = $tmp;
		}
		
		return $response;
	}
	private static function buildBandsResponse($result, $response) {
		$response ["bands"] = array ();
		return $response;
	}
	private static function buildVenuesResponse($result, $response) {
		$response ["venues"] = array ();
		return $response;
	}
	private static function buildFavsResponse($result, $response) {
		$response ["favourites"] = array ();
		return $response;
	}
	/*
	 * private static function sortBands($idEvent, $bands) {
	 * $tmp = array ();
	 * $result = array ();
	 *
	 * foreach ( $bands as $band ) {
	 * if ($band ['idEvent'] != $idEvent) {
	 * $tmp [] ['bands'] = array (
	 * 'idBand' => $row ["idBand"],
	 * 'role' => $row ["role"],
	 * 'reward' => $row ["reward"],
	 * 'extras' => $row ["extras"],
	 * 'technicalNeeds' => $band ['technicalNeeds'],
	 * 'note' => $band ['note']
	 * );
	 * array_push ( $result, $tmp );
	 * }
	 * }
	 * return $result;
	 * }
	 */
}