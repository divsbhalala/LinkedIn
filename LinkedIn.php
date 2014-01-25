<?php

/**
 * Basic implemntation of LinkedIn authentication via OAuth 2
 * (more details at https://developer.linkedin.com/documents/authentication).
 * Reference source codes could be found at http://developer.linkedin.com/documents/code-samples
 *
 * @author Vojtěch Lacina <MoraviaD1@gmail.com>
 * @version 2014-01-24
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class LinkedIn
{
	/** @var String */
	private $apiKey;

	/** @var String */
	private $apiSecret;

	/** @var String */
	private $redirectUri; // http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME']

	/** @var String */
	private $scope = 'r_fullprofile r_emailaddress rw_nus';

	/** @var Array Fields of interests */
	private $foi = array();


	// function __construct($apiKey, $apiSecret)
	// {
	// 	$this->apiKey = $apiKey;
	// 	$this->apiSecret = $apiSecret;
	// }

	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> Setters

	public function setApiKey($key)
	{
		$this->apiKey = $key;
		return $this;
	}

	public function setApiSecret($key)
	{
		$this->apiSecret = $key;
		return $this;
	}

	public function setRedirectUri($uri)
	{
		$this->redirectUri = $uri;
		return $this;
	}

	public function setScope($scope)
	{
		$this->scope = $scope;
		return $this;
	}

	public function addFoi($field)
	{
		if (!is_array($field)) $field = explode(',', $field);
		foreach ($field as $key => $value) if (!in_array($value, $this->foi)) $this->foi[] = $value;
		return $this;
	}

	//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> Others

	public function run()
	{
		// OAuth 2 Control Flow
		if (isset($_GET['error']))
		{
			// LinkedIn returned an error
			print $_GET['error'] . ': ' . $_GET['error_description'];
			exit;
		}
		elseif (isset($_GET['code']))
		{
			// echo "Get access token<br>";
			// User authorized your application
			if ($_SESSION['state'] == $_GET['state'])
			{
				// Get token so you can make API calls
				$this->getAccessToken();
			}
			else
			{
				// CSRF attack? Or did you mix up your states?
				echo "CSRF? better exit...";
				exit;
			}
		}
		else
		{
			if ((empty($_SESSION['expires_at'])) || (time() > $_SESSION['expires_at']))
			{
				// Token has expired, clear the state
				$_SESSION = array();
			}
			if (empty($_SESSION['access_token']))
			{
				// Start authorization process
				$this->getAuthorizationCode();
			}
		}

		// In case that fields of interest wasn't set
		if (empty($this->foi)) $this->addFoi('firstName,lastName,id,emailAddress,headline,location,pictureUrl');
		return $this->fetch('GET', '/v1/people/~:('.implode(',', $this->foi).')');
	}



	private function getAuthorizationCode()
	{
		if (empty($this->apiKey) || empty($this->scope) || empty($this->redirectUri))
			throw new LinkedInException('API key, scope or recirect URI can\'t be empty.');

		$params = array('response_type' => 'code',
			'client_id' => $this->apiKey,
			'scope' => $this->scope,
			'state' => uniqid('', TRUE), // unique long string
			'redirect_uri' => $this->redirectUri,
		);

		// Authentication request
		$url = 'https://www.linkedin.com/uas/oauth2/authorization?' . http_build_query($params);

		// Needed to identify request when it returns to us
		$_SESSION['state'] = $params['state'];

		// Redirect user to authenticate
		header("Location: $url");
		exit;
	}



	private function getAccessToken()
	{
		if (empty($this->apiKey) || empty($this->apiSecret) || empty($this->redirectUri))
			throw new LinkedInException('API key, API secret or recirect URI can\'t be empty.');

		$params = array(
			'grant_type' => 'authorization_code',
			'client_id' => $this->apiKey,
			'client_secret' => $this->apiSecret,
			'code' => $_GET['code'],
			'redirect_uri' => $this->redirectUri,
		);

		// Access Token request
		$url = 'https://www.linkedin.com/uas/oauth2/accessToken?' . http_build_query($params);

		// Tell streams to make a POST request
		$context = stream_context_create(
			array(
				'http' => array(
					'method' => 'POST',
				)
			)
		);

		// Retrieve access token information
		if ($response = file_get_contents($url, FALSE, $context))
		{
			$token = json_decode($response);

			// Store access token and expiration time
			$_SESSION['access_token'] = $token->access_token; // guard this!
			$_SESSION['expires_in']   = $token->expires_in; // relative time (in seconds)
			$_SESSION['expires_at']   = time() + $_SESSION['expires_in']; // absolute time

			return TRUE;
		}
		else
		{
			throw new LinkedInException('HTTP request failed.');
		}
	}



	private function fetch($method, $resource, $body = '')
	{
		$params = array(
			'oauth2_access_token' => $_SESSION['access_token'],
			'format' => 'json',
		);

		// HTTPS is required
		$url = 'https://api.linkedin.com' . $resource . '?' . http_build_query($params);
		// Tell streams to make a (GET, POST, PUT, or DELETE) request
		$context = stream_context_create(
			array(
				'http' => array(
					'method' => $method,
				)
			)
		);

		if ($response = @file_get_contents($url, FALSE, $context))
			return json_decode($response);
		else return FALSE;
	}
}

/**
 * 'LinkedInException' class declaration.
 * @access public
 */
class LinkedInException extends Exception {}
