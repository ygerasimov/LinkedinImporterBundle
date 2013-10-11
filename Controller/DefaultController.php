<?php

namespace CCC\LinkedinImporterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use CCC\LinkedinImporterBundle\Form as LiForm;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('CCCLinkedinImporterBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * Example of how to request the user's private data.
     */
    public function requestPrivateAction() {
    	
    	//load up the importer class
    	$importer 	= $this->get('linkedin.importer');
    	
    	//set a redirect for linkedin to bump the user back to after they approve your app
    	$importer->setRedirect($this->generateUrl('ccc_linkedin_importer_receivePrivate', array('submit'=>true), true));
    	
    	//nothing on this form except for a submit button to start the process
    	$form 		= $this->createForm(new LiForm\RequestPrivate());
    	$request 	= $this->getRequest();
    	
    	if($request->isMethod('POST')) {

    		$form->handleRequest($request);
    		
    		if($form->isValid()) {
    			//user hit the start button, so request permission from the user to allow your app on their linkedin account
    			//this will redirect to linkedin's site 
    			return $importer->requestPermission();
    		}
    		
    	}
    	
    	return $this->render('CCCLinkedinImporterBundle:Default:requestPrivate.html.twig', array('form' => $form->createView()));
    }
    
    /**
     * Receive user's private data.
     * At this point, linkedin has sent the user back to the page set with $importer->setRedirect() in requestPrivateAction().
     * Linkedin has also appended 2 $_GET params to the url, state & code. State is used to check that the request hasn't been
     * tampered with, and code need to be traded for an access token to get the user data. 
     */
    public function receivePrivateAction() {
    	$importer 	= $this->get('linkedin.importer');
    	//set the redirect again for the trade.  needs to match the redirect used in the request step
    	$importer->setRedirect($this->generateUrl('ccc_linkedin_importer_receivePrivate', array('submit'=>true), true));
    	
    	$request 	= $this->getRequest();
    	//this form will check that state has the value as in the request step 
    	$form 		= $this->createForm(new LiForm\ReceivePrivate($importer, null, array('csrf_protection' => false)));
    	$form->handleRequest($request);
    	
    	$profile_data = array();
    	if($form->isValid()) {
    		
    		$form_data 		= $form->getData();
    		
    		//give code to the importer class, and then trade it for an access token
    		$access_token 	= $importer->setCode($form_data['code'])->requestAccessToken();
    		
    		//$access_token is good for 60 days by default, so you should save it to a DB for later use 
    		//if nothing is passed to requestUserData(), it will use the most recently obtained $access_token by default 
    		$profile_data	= $importer->requestUserData('private', $access_token);

    	}
    	
    	return $this->render('CCCLinkedinImporterBundle:Default:receivePrivate.html.twig', array('form' => $form->createView(), 'profile_data' => $profile_data));
    }

    /**
     * Example of how to request the user's public data.
     * First step is same as above, except that this form has a text field to input another user's public profile url
     */
    public function requestPublicAction() {
    	$importer 	= $this->get('linkedin.importer');
    	$form 		= $this->createForm(new LiForm\RequestPublic());
    	$request 	= $this->getRequest();
    	
    	if($request->getMethod() == 'POST') {

    		$form->handleRequest($request);
    		
    		if($form->isValid()) {
    			$form_data = $form->GetData();
    			//add the other user's public profile url to the redirect 
		    	$importer->setRedirect($this->generateUrl('ccc_linkedin_importer_receivePublic', array('submit'=>true, 'url' => urlencode($form_data['url'])), true));
		    	//still need to ask the current user for permission to go through our app
    			return $importer->requestPermission('public');
    		}
    		
    	}
    	
    	return $this->render('CCCLinkedinImporterBundle:Default:requestPublic.html.twig', array('form' => $form->createView()));
    }
    
    /**
     * Receive another user's public data.
     */
    public function receivePublicAction() {
    	$importer 	= $this->get('linkedin.importer');

    	$request 	= $this->getRequest();
    	$form 		= $this->createForm(new LiForm\ReceivePublic($importer, null, array('csrf_protection' => false)));
    	$form->handleRequest($request);
    	
    	$profile_data = array();
    	if($form->isValid()) {
    		
    		$form_data 		= $form->getData();
	    	$importer->setRedirect($this->generateUrl('ccc_linkedin_importer_receivePublic', array('submit'=>true, 'url' => $form_data['url']), true));
    		$access_token 	= $importer->setCode($form_data['code'])->requestAccessToken(); 
    		//everything's the same as in receivePrivateAction(), except this time we're asking for a different user's public data
    		//so need to give that url to the importer class and tell it we want public data 
    		$profile_data	= $importer->setPublicProfileUrl($form_data['url'])->requestUserData('public');

    	}
    	
    	return $this->render('CCCLinkedinImporterBundle:Default:receivePublic.html.twig', array('form' => $form->createView(), 'profile_data' => $profile_data));
    }

}
