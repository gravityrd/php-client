<?php
/**
 * The GravityClient and related classes are used to connect to and communicate with the
 * Gravity recommendation engine.
 *
 * @package GravityClient
 */

/**
 * The GravityClient class can be used to send events, item and user information to
 * the recommendation engine and get recommendations.
 *
 * Example usage:
 * 
 * <pre>
 *		function createGravityClient() {
 *			$config = new GravityClientConfig();
 *			$config->remoteUrl = 'https://saas.gravityrd.com/grrec-CustomerID-war/WebshopServlet';
 *			$config->user = 'sampleUser';
 *			$config->password = 'samplePasswd';
 *			$config->retry = 0;
 *			return new GravityClient($config);
 *		}
 *		$client = createGravityClient();
 *		$context = new GravityRecommendationContext();
 *		$context->numberLimit = 5;
 *		$context->scenarioId = 'HOMEPAGE_MAIN';
 *              $context->nameValues = array(
 *                      new GravityNameValue('minPrice', '100'),
 *              );
 *		$client->getItemRecommendation('user1', '123456789abcdef', $context);
 * </pre>
 *
 * Please do not modify the GravityClient.php file (e.g. do not write your configuration parameters into the GravityClient.php file).
 * Using an unmodified client makes version updates easier.
 * Use your own factory function (like createGravityClient in the example above) to pass your configuration information to the GravityClient constructor.
 * 
 */

class GravityClient {

	/**
	 * The version info of the client.
	 */
	public $version = '1.0.13';

	/**
	 * Creates a new client instance with the specified configuration
	 * @param GravityClientConfig <var>$config</var> The configuration
	 */
	public function __construct(GravityClientConfig $config) {
		if ($config->timeoutSeconds <= 0) {
			throw new GravityException(
			'Invalid configuration. Timeout must be a positive integer.',
			new GravityFaultInfo(GRAVITY_ERRORCODE_CONFIG_ERROR));
		}
		if (!$config->remoteUrl) {
			throw new GravityException(
			'Invalid configuration. Remote URL must be specified.',
			new GravityFaultInfo(GRAVITY_ERRORCODE_CONFIG_ERROR));
		}
		$this->config = $config;
	}

	/**
	 * Adds an event to the recommendation engine.
	 *
	 * @param GravityEvent <var>$event</var> The event to add.
	 * @param boolean <var>$async</var> true if the call is asynchronous. An asynchronous call
	 * returns immediately after an input data checking,
	 * a synchronous call returns only after the data is saved to database.
	 */
	public function addEvent(GravityEvent $event, $async) {
		$this->addEvents(array($event), $async);
	}

	/**
	 * Adds multiple events to the recommendation engine.
	 *
	 * @param GravityEvent[] <var>$events</var> The events to add.
	 * @param boolean <var>$async</var> true if the call is asynchronous. An asynchronous call
	 * returns immediately after an input data checking,
	 * a synchronous call returns only after the data is saved to database.
	 */
	public function addEvents(array $events, $async) {
		$this->sendRequest('addEvents', array('async' => $async), $events, false);
	}

	/**
	 * Adds an item to the recommendation engine.
	 * If the item already exists with the specified itemId,
     * the entire item along with its NameValue pairs will be replaced to the new item specified here.
	 *
	 * @param GravityItem <var>$item</var> The item to add
	 * @param boolean <var>$async</var> true if the call is asynchronous. An asynchronous call
	 * returns immediately after an input data checking, 
	 * a synchronous call returns only after the data is saved to database.
	 */
	public function addItem(GravityItem $item, $async) {
		$this->addItems(array($item), $async);
	}

	/**
	 * Adds items to the recommendation engine.
 	 * If an item already exists with the specified itemId,
     * the entire item along with its NameValue pairs will be replaced to the new item specified here.
	 *
	 * @param GravityItem[] <var>$items</var> The items to add
	 * @param boolean <var>$async</var> true if the call is asynchronous. An asynchronous call
	 * returns immediately after an input data checking,
	 * a synchronous call returns only after the data is saved to database.
	 */
	public function addItems(array $items, $async) {
		$this->sendRequest('addItems', array('async' => $async), $items, false);
	}

	/**
	 * Existing item will be updated. If item does not exist Exception will be thrown.
	 * Update rules:
	 *  - Key-value pairs won't be deleted only existing ones updated or new ones added. But If a key occurs in the key
	 *    value list, then all values with the given key will be deleted and new values added in the recengine.
	 *  - Hidden field has to be always specified!
	 *
	 * @param GravityItem $item The item to update
	 */
	public function updateItem(GravityItem $item) {
		$this->updateItems(array($item));
	}

	/**
	 * Existing items will be updated. If item does not exist Exception will be thrown.
	 * Update rules:
	 *  - Key-value pairs won't be deleted only existing ones updated or new ones added. But If a key occurs in the key
	 *    value list, then all values with the given key will be deleted and new values added in the recengine.
	 *  - Hidden field has to be always specified!
	 *
	 * @param GravityItem[] $items The items to update
	 */
	public function updateItems(array $items) {
		$this->sendRequest('updateItems', NULL, $items, false);
	}

	
	/**
	 * Adds user to the recommendation engine.
	 * If the user already exists with the specified userId,
	 * the entire user will be replaced with the new user specified here.
	 *
	 * @param GravityUser <var>$user</var> The user to add.
	 * @param boolean <var>$async</var> true if the call is asynchronous. An asynchronous call
	 * returns immediately after an input data checking,
	 * a synchronous call returns only after the data is saved to database.
	 */
	public function addUser(GravityUser $user, $async) {
		$this->addUsers(array($user), $async);
	}

	/**
	 * Adds users to the recommendation engine. The existing users will be updated.
	 * If a user already exists with the specified userId,
	 * the entire user will be replaced with the new user specified here.
	 *
	 * @param GravityUser[] <var>$users</var> The users to add.
	 * @param boolean <var>$async</var> true if the call is asynchronous. An asynchronous call
	 * returns immediately after an input data checking,
	 * a synchronous call returns only after the data is saved to database.
	 */
	public function addUsers(array $users, $async) {
		$this->sendRequest('addUsers', array('async' => $async), $users, false);
	}

	/**
	 * Returns a list of recommended items, based on the given context parameters.
	 *
	 * @param string <var>$userId</var> The identifier of the logged in user. If no user is logged in, null should be specified.
	 * @param string <var>$cookieId</var> It should be a permanent identifier for the end users computer, preserving its value across browser sessions.
	 * It should be always specified.
	 * @param GravityRecommendationContext <var>$context</var>
	 *	Additional information which describes the actual scenario.
	 * @return GravityItemRecommendation
	 *	An object containing the recommended items and other information about the recommendation.
	 */
	public function getItemRecommendation($userId, $cookieId, GravityRecommendationContext $context) {
		return $this->sendRequest(
				'getItemRecommendation',
				array(
				'userId' => $userId,
				'cookieId' => $cookieId,
				),
				$context,
				true
		);
	}

	/**
	 * Given the userId and the cookieId, we can request recommendations for multiple scenarios (described by the context).
	 * This function returns lists of recommended items for each of the given scenarios in an array.
	 *
	 * @param string <var>$userId</var> The identifier of the logged in user. If no user is logged in, null should be specified.
	 * @param string <var>$cookieId</var> It should be a permanent identifier for the end users computer, preserving its value across browser sessions.
	 * It should be always specified.
	 * @param GravityRecommendationContext[] <var>$context</var>
	 * Additional Array of information which describes the actual scenarios.
	 * @return GravityItemRecommendation[]
	 *	An Array containing the recommended items for each scenario with other information about the recommendation.
	 */
	public function getItemRecommendationBulk($userId, $cookieId, array $context) {
		foreach ($context as $element) {
			$element->cookieId = $cookieId;
			$element->userId = $userId;
		}
		return $this->sendRequest(
				'getItemRecommendationBulk',
				array(
				'userId' => $userId,
				'cookieId' => $cookieId,
				),
				$context,
				true
		);
	}
	
	/**
	 * Simple test function to test without side effects whether the service is alive.
	 * @return string "Hello " + <code>$name</code>
	 */
	public function test($name) {
		return $this->sendRequest('test', array('name' => $name), $name, true);
	}

	/**
	 * Simple test function to test throwing an exception.
	 */
	public function testException() {
		$this->sendRequest('testException', null, null, true);
	}

	private function getRequestQueryString($methodName, $queryStringParams) {
		$queryString = $queryStringParams ? http_build_query($queryStringParams, null, '&') : '';
		if ($queryString) {
			$queryString = "&" . $queryString;
		}
		return "?method=" . urlencode($methodName) . $queryString . "&_v=" . urlencode($this->version);
	}
	
	private function sendRequestRetry($requestUrl, $requestBody, $isLast) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $requestUrl);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if (is_int($this->config->timeoutSeconds)) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->timeoutSeconds);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->config->timeoutSeconds);
		}
		else {
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->config->timeoutSeconds*1000);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->config->timeoutSeconds*1000);
		}

		if (strpos($this->config->remoteUrl, 'https') === 0) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->config->verifyPeer);			
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}

		if ($this->config->user) {
			curl_setopt($ch, CURLOPT_USERPWD, $this->config->user . ':' . $this->config->password);
		}

		if ($requestBody) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
		}	

		$header = array("X-Gravity-RecEng-ClientVersion: $this->version");
		if ($this->config->forwardClientInfo) {
			try {
				if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
					$header[] = "X-Forwarded-For: ".$_SERVER['REMOTE_ADDR'];
				}
				if (array_key_exists('HTTP_REFERER', $_SERVER)) {
					$header[] = "X-Original-Referer: ".$_SERVER['HTTP_REFERER'];
				}
				if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
					$header[] = "X-Device-User-Agent: ".$_SERVER['HTTP_USER_AGENT'];
					curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
				}
				if (array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) { 
					$header[] = "X-Device-Accept-Language: ".$_SERVER['HTTP_ACCEPT_LANGUAGE'];
				}
				$originalRequestUri = $this->guessOriginalRequestURI();
				if (!empty($originalRequestUri)) {
					$header[] = "X-Original-RequestUri: ".$this->guessOriginalRequestURI();
				} else {
					// could not detect original request URI, send SAPI name for debugging purposes
					$header[] = "X-PhpServerAPIName: ".php_sapi_name();
				}
			} catch (Exception $e) {
				// FIXME: add error to the param list ...
			}
		}
		
		// disable Expect: 100-Continue, it would cause an unnecessary roundtrip
		// see http://www.w3.org/Protocols/rfc2616/rfc2616-sec8.html#sec8.2.3
		$header[] = 'Expect:';
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		
		$result = curl_exec($ch);
		$err_code = curl_errno($ch);		
		
		
		if($isLast) {
			$verbStr = '';
			if($this->config->verbose) {
				$hostname = "nan";
				if(gethostname())
					$hostname = gethostname();

				$serverip = "nan";
				if(array_key_exists('SERVER_ADDR', $_SERVER))
					$serverip = $_SERVER['SERVER_ADDR'];
				
				$curlinfo = curl_getinfo($ch);
				$name_lookup_time = "nan";
				if($curlinfo["namelookup_time"])
					$name_lookup_time = $curlinfo["namelookup_time"];

				$verbStr = "\nHOST: " .$hostname . "\nIP: " . $serverip . "\nlookup time: " . $name_lookup_time . "\nURL: " . $requestUrl . "\nBODY: " . print_r($requestBody, true) . "\n";
			}
			$rc = $this->handleError($ch, $result, $verbStr);
		}
		if (is_resource($ch)) {
			curl_close($ch);
		}
		
		return array($err_code, $result);
	}

	private function sendRequest($methodName, $queryStringParams, $requestBody, $hasAnswer) {
		$retry = 0;
		if(isset($this->config->retry) && is_int($this->config->retry)) {
			$retry = $this->config->retry;
		}
		if ($this->config->retryMethods) {
			$retryEnabled = in_array($methodName, $this->config->retryMethods);
		}
		$isLast = !($retryEnabled && $retry > 0);
		
		$requestUrl = $this->config->remoteUrl . "/" . $methodName . $this->getRequestQueryString($methodName, $queryStringParams);	
		$result_arr = $this->sendRequestRetry($requestUrl, $requestBody, $isLast);
		$errnum = $result_arr[0];
		
		while ($retryEnabled && $retry > 0 && $errnum != 0) {
			$isLast = $retry <= 1;
			
			$requestUrl = $requestUrl . "&_er=" . $errnum;
			$result_arr = $this->sendRequestRetry($requestUrl, $requestBody, $isLast);
			$errnum = $result_arr[0];
			
			$retry--;
		}
		
		if ($hasAnswer) {
			$result = $result_arr[1];
			return json_decode($result, false);
		} else {
			return null;
		}
	}

	private function handleError($ch, $result, $verbStr) {
		$errorNumber = curl_errno($ch);
		if ($errorNumber != 0) {
			$errorMessage = curl_error($ch);
			curl_close($ch);
			switch ($errorNumber) {
				case 6: //CURLE_COULDNT_RESOLVE_HOST
					throw new GravityException(
					"CURLE$errorNumber Could not resolve host error during curl_exec(): $errorMessage" . $verbStr,
					new GravityFaultInfo(GRAVITY_ERRORCODE_COMM_HOSTRESOLVE));
				case 7: //CURLE_COULDNT_CONNECT
					throw new GravityException(
					"CURLE$errorNumber Could not connect to host error during curl_exec(): $errorMessage" . $verbStr,
					new GravityFaultInfo(GRAVITY_ERRORCODE_COMM_CONNECT));
				case 28: //CURLE_OPERATION_TIMEDOUT
					throw new GravityException(
					"CURLE$errorNumber Timeout error during curl_exec(): $errorMessage" . $verbStr,
					new GravityFaultInfo(GRAVITY_ERRORCODE_COMM_TIMEOUT));
				default:
					throw new GravityException(
					"CURLE$errorNumber Error during curl_exec(): $errorMessage" . $verbStr,
					new GravityFaultInfo(GRAVITY_ERRORCODE_COMM_OTHER));
			}
		} else {
			$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($responseCode != 200) {
				curl_close($ch);
				$e = json_decode($result, false);
				$supplement = "";
				if (is_object($e)) {
					$supplement = ", Message: $e->message" . $verbStr;
				}
				throw new GravityException(
				"Non-200 HTTP response code: $responseCode $supplement",
				new GravityFaultInfo(GRAVITY_ERRORCODE_COMM_HTTPERRORCODE));
			}
			return $responseCode;
		}
	}

	private function guessOriginalRequestURI()
	{
		$sapi_name = php_sapi_name();
		if ($sapi_name == 'cli') {
			// using CLI PHP
			return null;
		}
		$ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true:false;
		$sp = strtolower($_SERVER['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
		$port = $_SERVER['SERVER_PORT'];
		$port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
		$host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
		$uri = $protocol . '://' . $host . $port . $_SERVER['REQUEST_URI'];
		return $uri;
	}
	/**
	 * The client configuration.
	 *
	 * @var GravityClientConfig
	 */
	private $config;
}


/**
 * Contains a list of recommended items. This class is here only to describe the result,
 * the actual result will not be an instance of this class because of json deserialization.
 */
class GravityItemRecommendation {
	function __construct() {
	}

	/**
	 *
	 * Array containing the recommended items.
	 * This is populated only if the scenario specifies to do so, otherwise this is null.
	 * itemIds is always populated.
	 * The items in this list only have the NameValues specified by the scenario.
	 * The list of NameValues specified by the scenario can be overridden by the
	 * GravityRecommendationContext resultNameValues on a per request basis.
	 *
	 * @var GravityItem[]
	 */
	public $items;

	/**
	 *
	 * The identifiers of the recommended items. Only if not a NameValue recommendation.
	 *
	 * @var int
	 */
	public $itemIds;

	/**
	 *
	 * The unique identifier of the recommendation generated by the recommendation engine.
	 * Strings in the PHP client are always UTF-8 encoded.
	 *
	 * @var string
	 */
	public $recommendationId;
	
	/**
	 * Array containing additional name-value pairs for recommendations.
	 * e.g.: AllItemCount for paging responses.
	 * 
	 * @var GravityNameValue[]
	 */
	public $outputNameValues;
}

/**
 * Describes an event for the recommendation engine, for example a user viewed an item.
 */
class GravityEvent {
	function __construct() {
		$this->time = time();
		$this->nameValues = array();
	}

	/**
	 *
	 * The event type determines the namevalues which can be passed.
	 * <p>Possible list event types, which can be expanded based on what can the external system support:</p>
	 * <table border="1">
	 *	<tr><th><code>Event Type</code><th>Category</th></th><th>Description</th><th>NameValues for the event</th></tr>
	 *	<tr><td><code>VIEW</code></td><td>GENERAL</td><td>The user viewed the info page of an item.</td><td></td></tr>
	 *	<tr><td><code>BUY</code></td><td>GENERAL</td><td>The user bought an item.</td><td>
	 *		<table>
	 *			<tr><td><code>OrderId</code></td><td></td></tr>
	 *			<tr><td><code>UnitPrice</code></td><td>Formatted as a decimal number, for example 1234 or 12345.67</td></tr>
	 *			<tr><td><code>Currency</code></td><td></td></tr>
	 *			<tr><td><code>Quantity</code></td><td>Formatted as a decimal number.</td></tr>
	 *		</table>
	 *	</td></tr>
	 *	<tr><td><code>RATING</code></td><td>GENERAL</td><td>The user rated an item.</td><td>
	 *		<table>
	 *			<tr><td><code>Value</code></td><td>The value of the rating.</td></tr>
	 *		</table>
	 *	</td></tr>
	 *	<tr><td><code>ADD_TO_CART</code></td><td>GENERAL</td><td>The user added an item to the shopping cart.</td><td>
	 *		<table>
	 *			<tr><td><code>Quantity</code></td><td></td></tr>
	 *		</table>
	 *	</td></tr>
	 *	<tr><td><code>REMOVE_FROM_CART</code></td><td>GENERAL</td><td>The user removed an item from the shopping cart.</td><td>
	 *		<table>
	 *			<tr><td><code>Quantity</code></td><td></td></tr>
	 *		</table>
	 *	</td></tr>
	 *	<tr><td><code>ADD_TO_FAVORITES</code></td><td>GENERAL</td><td>The user added the item to his favorites.</td><td>
	 *		<table>
	 *			<tr><td><code>ListId</code></td><td>Use if the webshop supports multiple favorites lists.</td></tr>
	 *		</table>
	 *	</td></tr>
	 *	<tr><td><code>REMOVE_FROM_FAVORITES</code></td><td>GENERAL</td><td>The user removed an item from his favorites.</td><td>
	 *		<table>
	 *			<tr><td><code>ListId</code></td><td>Use if the webshop supports multiple favorites lists.</td></tr>
	 *		</table>
	 *	</td></tr>
	 * 	<tr><td><code>REC_CLICK</code></td><td>GENERAL</td><td>The user clicked on a recommended item.</td><td>
	 *		<table>
	 *			<tr><td><code>Position</code></td><td>The position of the clicked item in the recommendation list. The position of the first item is 1.</td></tr>
	 *		</table>
	 *	</td></tr>
	 *	<tr><td><code>LOGIN</code></td><td>GENERAL</td><td>The user logged in to the site. For this event the cookieId and also the userId must be specified.</td><td></td></tr>
	 *	<tr><td><code>ADD_TO_WISHLIST</code></td><td>ADDITIONAL</td><td>The user added the item to his wishlist.</td><td>
	 *		<table>
	 *			<tr><td><code>ListId</code></td><td>Use if the webshop supports multiple wishlists.</td></tr>
	 *		</table>
	 *	</td></tr>
	 *	<tr><td><code>REMOVE_FROM_WISHLIST</code></td><td>ADDITIONAL</td><td>The user removed an item from his wishlist.</td><td>
	 *		<table>
	 *			<tr><td><code>ListId</code></td><td>Use if the webshop supports multiple wishlists.</td></tr>
	 *		</table>
	 *	</td></tr>
	 *	<tr><td><code>HIDE_PRODUCT</code></td><td>ADDITIONAL</td><td>The user hides a product that should not be recommended to him.</td><td></td></tr>
	 *	<tr><td><code>UNHIDE_PRODUCT</code></td><td>ADDITIONAL</td><td>The user unhides a product that he marked as hidden previously.</td><td></td></tr>
	 *	<tr><td><code>PRODUCT_SEARCH</code></td><td>ADDITIONAL</td><td>A list of products was displayed to the user, for example by browsing a category or by free text search.</td><td>
	 *		<table>
	 *			<tr><td><code>SearchString</code></td><td>The search string, if the list is based on a free text search.</td></tr>
	 *			<tr><td><code>Filter.*</code></td><td>If the listing is based on comparing an item namevalue to a filter value, you can provide the actual filter here.
	 *				For example, if the user was browsing a specific category, name='Filter.CategoryId' and value='CategoryA' can be specified.</td></tr>
	 *		</table>
	 *	</td></tr>
	 *	<tr><td><code>NEXT_RECOMMENDATION</code></td><td>ADDITIONAL</td><td>The user asked for more recommendation.</td><td></td></tr>
	 *	<tr><td><code>COMMENT</code></td><td>ADDITIONAL</td><td>The user wrote a comment for the item.</td><td></td></tr>
	 *	<tr><td><code>NOT_INTERESTED</code></td><td>ADDITIONAL</td><td>The user would not like this item and similar items to be recommended to him, but he also does not want to give a negative rating for this item.</td><td></td></tr>
	 *	<tr><td><code>LETTER_READ</code></td><td>ADDITIONAL</td><td>T The user read a letter which sent for him by the system (eg. a newsletter).</td><td></td></tr>
 	 *	<tr><td><code>CLICK_OUT</code></td><td>PRICE COMPARISON</td><td>The user jumps to an external webshop to buy the product. Used by price comparison sites.</td><td></td></tr>
	 *	<tr><td><code>LANCE</code></td><td>AUCTION</td><td>The user place a bid on the item.</td><td><code>Value</code>The value of the bid as a decimal number.</td></tr>
	 *	<tr><td><code>LETTER_SEND</code></td><td>AUCTION, ADVERTISING</td><td>The user sent a message to the advertiser.</td><td></td></tr>
	 *	<tr><td><code>ADD_ITEM</code></td><td>AUCTION, ADVERTISING</td><td>The user added an item to the site.</td><td></td></tr>
	 *	<tr><td><code>FREE_VIEW</code></td><td>MEDIA</td><td>The user wached/listened an item for free.</td><td><code>Duration</code>How long the user wached the item in seconds as a decimal number.</td></tr>
	 *	<tr><td><code>PAID_VIEW</code></td><td>MEDIA</td><td>The user payed for waching/listening an item.</td><td>
	 *      <table>
	 *			<tr><td><code>Duration</code></td><td>How long the user wached the item in seconds. A decimal number.</td></tr>
	 *			<tr><td><code>Value</code></td><td>How much the user payed for waching the item. A decimal number.</td></tr>
	 *		</table>
	 *  </td></tr>
	 *	<tr><td><code>SUBSCRIPTION_VIEW</code></td><td>MEDIA</td><td>The user watched an item that was available for him by a subscription.</td><td>
	 *      <table>
	 *			<tr><td><code>Duration</code></td><td>How long the user wached the item in seconds. A decimal number.</td></tr>
	 *		</table>
	 *  </td></tr>
	 *	<tr><td><code>FOLLOW_USER</code></td><td>SOCIAL</td><td>The user follows an other user.</td><td><code>OtherUserId</code>The identifier of the followed user.</td></tr>
	 *	<tr><td><code>SHARE</code></td><td>SOCIAL</td><td>The user share the item on a social site (eg. Facebook, Twitter,...).</td><td></td></tr>
	 *	<tr><td><code>REDEEM</code></td><td>COUPON</td><td>The user redeem the item (eg. a coupon).</td><td></td></tr>
	 * </table>
	 *
	 * @var string
	 */
	public $eventType;

	/**
	 * 
	 * This is the identifier of the item which was viewed/bought/etc. by the user.
	 * Set to null if it does no make sense, for example in case of a login event.
	 * Strings in the PHP client are always UTF-8 encoded.
	 *
	 * @var string
	 */
	public $itemId;

	/**
	 *
	 * It should be an id of a previous recommendation, if this event is a consequence of a recommendation.
	 * Strings in the PHP client are always UTF-8 encoded.
	 *
	 * @var string
	 */
	public $recommendationId;

	/**
	 *
	 * The UNIX timestamp of the event, as returned by the standard time() PHP function.
	 *
	 * @var int
	 */
	public $time;

	/**
	 * 
	 * This is the identifier of the user who generated the event.
	 * If unknown set to null (for example if the user is not logged in yet).
	 * Strings in the PHP client are always UTF-8 encoded.
	 *
	 * @var string
	 */
	public $userId;

	/**
	 *
	 * A cookieId should be a permanent identifier for the end users computer, preserving its value across browser sessions.
	 * This way not logged in users can be recognized, if they have logged in previously from the same computer.
	 * The cookieId should be always specified.
	 * Strings in the PHP client are always UTF-8 encoded.
	 *
	 * @var string
	 */
	public $cookieId;

	/**
	 *
	 * The NameValues for the event. The possible list of namevalues depends on the event type.
	 * NameValues provide additional description of the event.
	 * There can multiple NameValues with the same name.
	 * The order of NameValues will not be preserved, but the order of the values for the same name will be preserved.
	 *
	 * @var GravityNameValue[]
	 */
	public $nameValues;
}

/**
 * A user in the recommendation system. A user is an entity which generates event, and can get recommendations.
 */
class GravityUser {
	function __construct() {
		$this->nameValues = array();
	}

	/**
	 *
	 * This is a unqiue identifier for a registered user.
	 * Strings in the PHP client are always UTF-8 encoded.
	 *
	 * @var string
	 */
	public $userId;

	/**
	 *
	 * NameValues provide additional description of the user.
     * There can multiple NameValues with the same name.
	 * The order of NameValues will not be preserved.
	 *
	 * The recommendation engine in most cases does not require detailed information about the users, usually only some basic information can be used to enhance the quality of recommendation.
	 * For example:
	 * <table border="1">
	 *	<tr><th>Name</th><th>Description</th></tr>
	 *	<tr><td>ZipCode</td><td>The zip code of the user.</td></tr>
	 *	<tr><td>City</td><td>The city of the user.</td></tr>
	 *	<tr><td>Country</td><td>The country of the user.</td></tr>
	 * </table>
	 *
	 * @var GravityNameValue[]
	 */
	public $nameValues;

	/**
	 *
	 * True if the user is hidden.  A no more existing user should be set to hidden.
	 *
	 * @var boolean
	 */
	public $hidden;
}

/**
 * An item is something that can be recommended to users.
 */
class GravityItem {
	function __construct() {
		$this->fromDate = 0;
		$this->toDate = 2147483647;
		$this->nameValues = array();
	}

	/**
	 *
	 * The itemId is a unique identifier of the item.
	 * Strings in the PHP client are always UTF-8 encoded.
	 *
	 * @var string
	 */
	public $itemId;

	/**
	 * The item title. This is a short, human-readable name of the item.
	 * If the title is available in multiple languages, try to use your system's primary language, for example English.
	 * Strings in the PHP client are always UTF-8 encoded.
	 *
	 * @var string
	 */
	public $title;

	/**
	 *
	 * The type of the item. It can be used to create different item families.
	 * The purpose of itemtype is to differentiate items which will have different namevalue properties.
	 * Examples:
	 * <ul>
	 *	<li>Book</li>
	 *	<li>Ticket</li>
	 *	<li>Computer</li>
	 *	<li>Food</li>
	 * </ul>
	 *
	 * @var string
	 */
	public $itemType;

	/**
	 * The value of hidden. A hidden item will be never recommended.
	 *
	 * @var boolean
	 */
	public $hidden;

	/**
	 *
	 * An item is never recommended before this date.
	 * The date in unixtime format, the number of seconds elapsed since 1970.01.01 00:00:00 UTC, as returned by the standard time() PHP function.
	 * Set it to 0 if not important.
	 *
	 * @var int
	 */
	public $fromDate;

	/**
	 *
	 * An item is never recommended after this date.
	 * The date in unixtime format, the number of seconds elapsed since 1970.01.01 00:00:00 UTC, as returned by the standard time() PHP function.
	 * Set it to a big number (eg. 2147483647) if not important.
	 *
	 * @var int
	 */
	public $toDate;


	/**
	 *
	 * The NameValues for the item.
	 * <p>NameValues provide additional description of the item.</p>
     * <p>There can multiple NameValues with the same name.</p>
	 * <p>The order of NameValues among different names will not be preserved, but the order of the values for the same name will be preserved.</p>
	 * <p>The recommendation engine can be configured to use some properties to create a relation between items.</p>
	 * <p>A possible list of names:</p>
	 * <table border="1">
	 *	<tr><th>Name</th><th>Localizable</th><th>Description</th></tr>
	 *	<tr><td>Title</td><td>Yes</td><td>The title of the item.</td></tr>
	 *	<tr><td>Description</td><td>Yes</td><td>The description of item.</td></tr>
	 *	<tr><td>CategoryPath</td><td>No</td><td>The full path of the item's category.
	 *		For example, CategoryPath might be "books/computers/databases" for a book about databases.
	 *		CategoryPath can be repeated if an item belongs to multiple categories. Use "/" only for separating levels.
	 *		Later it is possible to use the recommendation engine to filter the list of items based on category, so it can provide recommendations for "Computer Books" category or only for "Computer Books &gt; Database" category.
	 *  </td></tr>
	 *	<tr><td>Tags</td><td>No</td><td>Tags for the item. Specify a separate namevalue for each tag.</td></tr>
	 *	<tr><td>State</td><td>No</td><td>The state of the item, for example 'available', 'out of stock' etc.</td></tr>
	 *	<tr><td>Price</td><td>No</td><td>The price of the item.</td></tr>
	 * </table>
	 *
	 * The recommendation engine can accept arbitrary namevalues, the list above is just an example of basic properties that are used almost everywhere.
	 * The NameValues which are relevant to business rules and possible content based filtering should be provided to the recommendation engine.
	 *
	 * <p>You can use localization by appending a language identifier to the Name of the NameValue. For example, Title_EN, Title_HU.
	 * It is possible to use both non-localized and localized version.
	 * </p>
	 *
	 * @var GravityNameValue[]
	 */
	public $nameValues;

}

/**
 * Contains information for a recommendation request.
 */
class GravityRecommendationContext {
	function __construct() {
		$this->recommendationTime = time();
	}

	/**
	 *
	 * The time of the recommendation (seconds in unixtime format), the time when it will be shown to the end user.
	 * Use a value as returned by the standard time() PHP function.
	 *
	 * @var int
	 */
	public $recommendationTime;
	
	/**
	 *
	 * The value of the maximum number of items in the result.
	 * The maximum number of the items in the result can be also limited by the configuration of the scenario.
	 * If set to 0, this number of items is determined by the scenario.
	 *
	 * @var int
	 */
	public $numberLimit;

	/**
	 *
	 * The value of scenarioId. Scenarios are defined by the scenario management API.
	 * A scenario describes a way how recommended items will be filtered, ordered.
	 *
	 * @var string
	 */
	public $scenarioId;

	/**
	 *
	 * The NameValues for the context.
	 * NameValues can describe the parameters for the actual scenario, like current item id, filtering by category etc.
	 * Item-to-item recommendation is possible by a specific scenario which parses a NameValue describing the current item,
	 * or multiple NameValues if there are multiple actual items.
	 * The list of allowed names depends on the actual scenario.
	 * <p>The scenario can also specify that the result is not a list of items, but a list of values of item NameValues.</p>

	 * <table border="1">
	 *	<tr><th>Name</th><th>Description</th></tr>
	 *	<tr><td>CurrentItemId</td><td>The identifier of the actual item, if the current page is an item page.</td></tr>
	 *	<tr><td>ItemOnPage</td><td>Identifier of item displayed elsewhere on the page. They will be excluded from recommendation. This namevalue can be used multiple times to provide a list of items.</td></tr>
	 *	<tr><td>CartItemId</td><td>Identifier of item in the current shopping cart. This can provide additional information to improve the quality of recommendation. This namevalue must be used as many times as many items the shopping cart contains.</td></tr>
	 *	<tr><td>CartItemQuantity</td><td>The quantity of items in the current shopping cart, in the same order as CartItemId namevalues.</td></tr>
	 *	<tr><td>Filter.*</td><td>If specified, only items having the specified name and value as metadata will be in the result.
	 *			For example, the namevalue with name='Filter'.'CategoryId' and value='A' means that only items belonging to category 'A' will be in the result.</td></tr>
	 *
	 * </table>
	 *
	 * @var GravityNameValue[]
	 */
	public $nameValues;

	/**
	 * If not null, specifies which NameValues of the recommended items should be included in the result.
	 * If null, the returned NameValues are determined by the actual scenario.
	 *
	 * @var GravityNameValue[]
	 */
	public $resultNameValues;

}


/**
 * A name and a value. This can be used to provide information about items, users and events.
 */
class GravityNameValue {
	/**
	 *
	 * The name.
	 * Strings in the PHP client are always UTF-8 encoded.
	 *
	 * @var string
	 */
	public $name;

	/**
	 *
	 * The value.
	 * Strings in the PHP client are always UTF-8 encoded.
	 *
	 * @var string
	 */
	public $value;

	/**
	 * Creates a new instance of a namevalue pair.
	 * Strings in the PHP client are always UTF-8 encoded.
	 *
	 * @param string <var>$name</var> The name.
	 * @param string <var>$value</var> The value.
	 */
	public function __construct($name, $value) {
		$this->name = $name;
		$this->value = $value;
	}
}



class GravityClientConfig {
	function __construct() {
		$this->timeoutSeconds = 2;
		$this->verifyPeer = true;
		$this->retryMethods = array("addUsers", "addItems", "addEvents", "getItemRecommendation");
		$this->retry = 0;
		$this->verbose = true;
		$this->forwardClientInfo = true;
	}

	/**
	 * Forwards the user-agent, referrer, browser language and client IP to the recommendation engine.
	 * Default value is true;
	 * 
	 * @var boolean
	 */
	public $forwardClientInfo;
	
	/**
	 * The URL of the server side interface. It has no default value, must be specified.
	 * Strings in the PHP client are always UTF-8 encoded.
	 *
	 * @var string
	 */
	public $remoteUrl;

	/**
	 * The timeout for the operations in seconds. The default value is 3 seconds.
	 * Double values are supported after PHP version  5.2.3 (uses miliseconds timeout)
	 * @var int
	 */
	public $timeoutSeconds;

	/**
	 * The setting of cURL option <code>CURLOPT_SSL_VERIFYPEER</code> in case of https connection.
	 * Set it to <code>false</code> if your server could not accept our certificate.
	 * The default value is true.
	 * Leave it blank in case of http connection.
	 *
	 * @var boolean
	 */
	public $verifyPeer;

	/**
	 *
	 * The user name for the http authenticated connection. Leave it blank in case of
	 * connection without authentication.
	 *
	 * @var string
	 */
	public $user;

	/**
	 * The password for the http authenticated connection. Leave it blank in case of
	 * connection without authentication.
	 *
	 * @var string
	 */
	public $password;
	
	/**
	 * The list of method names which should be retried after communication error.
	 * 
	 * @var array(string)
	 */
	public $retryMethods;
	
	/**
	 * More verbose error messages in case of error.
	 * 
	 * @var boolean
	 */
	public $verbose;
	
	/**
	 * If > 1 enables retry for the methods specified in $retryMethods.
	 * 
	 * @var int
	 */
	public $retry;
}

/**
 * The client was initilized with invalid configuration.
 * You should never use the value of the constant, always use it be referencing its name.
 */
define('GRAVITY_ERRORCODE_CONFIG_ERROR', -1);

/**
 * Unknown error during communication.
 * You should never use the value of the constant, always use it be referencing its name.
 */
define('GRAVITY_ERRORCODE_COMM_OTHER', -2);

/**
 * Could not resolve the remote host name.
 * You should never use the value of the constant, always use it be referencing its name.
 */
define('GRAVITY_ERRORCODE_COMM_HOSTRESOLVE', -3);

/**
 * Timeout during communication.
 * You should never use the value of the constant, always use it be referencing its name.
 */
define('GRAVITY_ERRORCODE_COMM_TIMEOUT', -4);

/**
 * Could not connect to remote host.
 * You should never use the value of the constant, always use it be referencing its name.
 */
define('GRAVITY_ERRORCODE_COMM_CONNECT', -5);

/**
 * A non-200 HTTP response code was received.
 * You should never use the value of the constant, always use it be referencing its name.
 */
define('GRAVITY_ERRORCODE_COMM_HTTPERRORCODE', -6);

/**
 * The exception class used by the recommendation engine client in case of an error.
 */
class GravityException extends Exception {
	/**
	 * Creates a new instance of GravityException.
	 *
	 * @param string <var>$message</var> The error message.
	 * @param GravityFaultInfo <var>$faultInfo</var> Contains information about the error.
	 *
	 */
	public function __construct($message, $faultInfo) {
		parent::__construct($message, $faultInfo->errorCode);
		$this->faultInfo = $faultInfo;
	}

	/**
	 * The object describing the error.
	 *
	 * @var GravityFaultInfo
	 */
	public $faultInfo;
}

/**
 * Describes the error occured during a request.
 */
class GravityFaultInfo {
	function __construct($errorCode) {
		$this->errorCode = $errorCode;
	}

	public $errorCode;
}

/**
 * This class used to deserialize exceptions coming from the recommendation engine.
 */
class GravityRecEngException {

	public $message;

	/**
	 *
	 * Currently defined error codes:
	 * <ul>
	 *	<li>ERR_ITEMS_IS_NULL</li>
	 *	<li>ERR_ITEMS_HAS_NULL_ELEMENT</li>
	 *	<li>ERR_ITEMID_IS_NULL_OR_EMPTY</li>
	 *	<li>ERR_USERS_IS_NULL</li>
	 *	<li>ERR_USERS_HAS_NULL_ELEMENT</li>
	 *	<li>ERR_USERID_IS_NULL_OR_EMPTY</li>
	 *	<li>ERR_RATINGS_IS_NULL</li>
	 *	<li>ERR_RATINGS_HAS_NULL_ELEMENT</li>
	 *	<li>ERR_RECOMMENDATIONID_IS_NULL_OR_EMPTY</li>
	 *	<li>ERR_ITEM_NOT_FOUND</li>
	 *	<li>ERR_USER_NOT_FOUND</li>
	 *	<li>ERR_EVENTS_IS_NULL</li>
	 *	<li>ERR_EVENTS_HAS_NULL_ELEMENT</li>
	 *	<li>ERR_INVALID_EVENT_TYPE</li>
	 *	<li>ERR_PARAM_IS_NULL</li>
	 *	<li>ERR_NAMEVALUE_IS_NULL</li>
	 *	<li>ERR_NAME_IN_NAMEVALUE_IS_NULL_OR_EMPTY</li>
	 *	<li>ERR_VALUE_IN_NAMEVALUE_IS_NULL</li>
	 *	<li>ERR_NAME_IN_NAMEVALUE_IS_NOT_ALLOWED</li>
	 *	<li>ERR_ITEMID_INVALID_FROMTODATE</li>
	 *	<li>ERR_INVALID_EVENT_RECOMMENDATIONID</li>
	 *	<li>ERR_INTERNAL_ERROR</li>
	 *	<li>ERR_INVALID_METHOD_NAME</li>
	 * </ul>
	 *
	 * @var string
	*/
	public $faultInfo;
}
