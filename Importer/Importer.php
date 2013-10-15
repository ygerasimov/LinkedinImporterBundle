<?php

namespace CCC\LinkedinImporterBundle\Importer;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Importer { 

	protected $_container			= null; //handle to outside services
	protected $_config 				= null;	//config options from file
	protected $_state				= null;	//unique string passed between linkedin and here to prevent csrf attacks
	protected $_code				= null;	//auth code returned from linkedin required for pulling private data
	protected $_redirect 			= null;	//url linkedin redirects to. must be present in all auth requests
	protected $_access_token 		= null; //token received from linkedin to pull private data
	protected $_public_profile_url 	= null; //linkedin url to pull public data

	/**
	 * Sets handle to service container so we can get an instance of the session later
	 * @param ContainerInterface $ci
	 */
	public function __construct(ContainerInterface $ci) {
		$this->_container = $ci;
	}
	
	/**
	 * @todo Refactor.. not really necessary
	 * @return array
	 */
	public function getConfig() {
		if(!$this->_config) {
	    	$this->_config = $this->_container->getParameter('ccc_linkedin_importer');
		}
		return $this->_config;
	}

	/**
	 * Returns the value of linkedin_state from the session
	 * @return string
	 */
	public function getState() {
		$session = $this->_container->get('session');
		return $session->get('linkedin_state');
	}
	
	/**
	 * Reinitializes the value of linkedin_state in the session
	 * @param string $value
	 * @return \CCC\LinkedinImporterBundle\Importer\Importer
	 */
	public function resetState($value = null) {
		if(!$value) {
			$value = md5(mt_rand(1,1000000)
						. 'IAMTHEVERYMODELOFAMODERNMAJORGENERAL' //some salt
						. __CLASS__
						. mt_rand(1,1000000)
					);
		}
		$value = (string)$value;
		
		$session = $this->_container->get('session');
		$session->invalidate();
		$session->set('linkedin_state', $value);

		return $this;
	}

	/**
	 * Returns linkedin auth code
	 * @return string
	 */
	public function getCode() {
		return $this->_code;
	}
	
	/**
	 * Sets auth code received from linkedin
	 * @param string $value
	 * @return \CCC\LinkedinImporterBundle\Importer\Importer
	 */
	public function setCode($value) {
		$this->_code = (string)$value;
		return $this;
	}
	
	/**
	 * Gets redirect url required for linkedin authorization
	 * @return string
	 */
	public function getRedirect() {
		return $this->_redirect;
	}
	
	/**
	 * Sets redirect url
	 * @param string $value
	 * @return \CCC\LinkedinImporterBundle\Importer\Importer
	 */
	public function setRedirect($value) {
		$this->_redirect = (string)$value;
		return $this;
	}

	/**
	 * Returns the url of the profile you are accessing when doing a public call 
	 * @return string
	 */
	public function getPublicProfileUrl() {
		return $this->_public_profile_url;
	}
	
	/**
	 * Sets public profile url
	 * @param string $value
	 * @return \CCC\LinkedinImporterBundle\Importer\Importer
	 */
	public function setPublicProfileUrl($value) {
		$this->_public_profile_url = (string)$value;
		return $this;
	}
	
	/**
	 * Returns access token retrieved from linkedin
	 * @return string
	 */
	public function getAccessToken() {
		return $this->_access_token;
	}
	
	/**
	 * Sets access token
	 * @param string $value
	 * @return \CCC\LinkedinImporterBundle\Importer\Importer
	 */
	public function setAccessToken($value) {
		$this->_access_token = (string)$value;
		return $this;
	}
	
	
	/**
	 * Sends a permissions request to linkedin.
	 * This function redirects to linkedin's site where it will prompt the user to allow access to your application.
	 * If successful, linkedin will send the user back to the url set previously with $this->setRedirect()
	 * 
	 * @param string $type
	 * @throws Exception
	 * @return NULL, stdClass
	 */
	public function requestPermission($type = 'private')
    {
		
		if(!$this->getRedirect()) {
			throw new \Exception('please set a redirect url for your permissions request');
		}
		
		$config = $this->getConfig();
		if(!isset($config['dataset'][$type])) {
            // @todo Is this configurable?
			throw new \Exception('unknown action. please check your apiconfig.yml file for the available types');
		}

		$params 					= array();
		$params['response_type'] 	= 'code';
		$params['client_id'] 		= $config['api_key'];
		$params['redirect_uri']		= $this->getRedirect();
		$params['scope'] 			= $config['dataset'][$type]['scope'];
		$params['state']			= $this->resetState()->getState();

        $url = $config['urls']['auth'] . '?' . http_build_query($params);

		return new RedirectResponse($url);
	}
	
	/**
	 * Gets access token from linkedin
	 * 
	 * @throws \Exception
	 * @return string
	 */
	public function requestAccessToken() {
		
		$config 				= $this->getConfig();
		$redirect 				= $this->getRedirect();

        $token_url				= $config['urls']['token'];
		$params 				= array();
		$params['client_id'] 	= $config['api_key'];
		$params['redirect_uri']	= $redirect;
		$params['code'] 		= $this->getCode();
		$params['grant_type']	= 'authorization_code';
		$params['client_secret'] = $config['secret_key'];


        //get access token
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $token_url);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  // don't worry about bad ssl certs
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));  // @todo why post a get query?
		
		$response = curl_exec($ch);

		if(!$response) {
			throw new \Exception('no response from linkedin');
		}
		
		$response = json_decode($response);
		if(!isset($response->access_token) || !$response->access_token) {
			throw new \Exception('no access token received from linkedin');
		}
		
		$this->setAccessToken($response->access_token);
		
		return (string)$response->access_token;
	}
	
	/**
	 * Gets user data from linkedin.  
	 * 
	 * 
	 * @param string $type
	 * @param string $access_token
	 * @throws \Exception
	 * @return SimpleXMLElement
	 */
	public function	requestUserData($type = 'private', $access_token = null) {
		
		$config 		= $this->getConfig();
		$access_token 	= ($access_token) ? $access_token : $this->getAccessToken(); 
		$url 			= null;
		
		switch($type) {
			case 'public':
				//error if no profile set 
				if(!$this->getPublicProfileUrl()) {
					throw new \Exception('please set the public profile you want to pull');
				}
				
				$url = $config['urls']['public']
					 . $this->getPublicProfileUrl()
					 . $config['dataset'][$type]['fields'];
				break;
			case 'private':
			case 'login':
			default:
				$url = $config['urls']['private']
					 . $config['dataset'][$type]['fields'];
				break;
		}

		//add access token to request url
		$url .= '?' . http_build_query(array('oauth2_access_token' => $access_token, 'secure-urls' => 'true'));
		
		//send it
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  // don't worry about bad ssl certs
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept-Language: en-US, ja"));
		$response = curl_exec($ch);
		
		$data = simplexml_load_string($response);

		return $data;
	}
}
