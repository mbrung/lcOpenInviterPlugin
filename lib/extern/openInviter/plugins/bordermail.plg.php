<?php
$_pluginInfo=array(
	'name'=>'Bordermail',
	'version'=>'1.0.2',
	'description'=>"Get the contacts from a Bordermail account",
	'base_version'=>'1.8.0',
	'type'=>'email',
	'check_url'=>'http://www.boardermail.com/',
	'requirement'=>'user',
	'allowed_domains'=>false,
	);
/**
 * Bordermail Plugin
 * 
 * Imports user's contacts from Bordermail AddressBook
 * 
 * @author OpenInviter
 * @version 1.0.0
 */
class bordermail extends openinviter_base
	{
	private $login_ok=false;
	public $showContacts=true;
	public $internalError=false;
	protected $timeout=30;
	
	public $debug_array=array(
				'initial_get'=>'login',
				'login_post'=>'frontpage',
				'url_contacts'=>'compose'
				
				);
	
	/**
	 * Login function
	 * 
	 * Makes all the necessary requests to authenticate
	 * the current user to the server.
	 * 
	 * @param string $user The current user.
	 * @param string $pass The password for the current user.
	 * @return bool TRUE if the current user was authenticated successfully, FALSE otherwise.
	 */
	public function login($user,$pass)
		{
		$this->resetDebugger();
		$this->service='bordermail';
		$this->service_user=$user;
		$this->service_password=$pass;
		if (!$this->init()) return false;
					
		$res=$this->get("http://www.boardermail.com/");
		if ($this->checkResponse("initial_get",$res))
			$this->updateDebugBuffer('initial_get',"http://www.boardermail.com",'GET');
		else
			{
			$this->updateDebugBuffer('initial_get',"http://www.boardermail.com",'GET',false);	
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
	
		$form_action="http://www.boardermail.com/scripts/common/login.main";
		$post_elements=array('show_frame'=>'Enter','login'=>$user,'password'=>$pass);
		$res=$this->post($form_action,$post_elements,true);
		if ($this->checkResponse('login_post',$res))
			$this->updateDebugBuffer('login_post',$form_action,'POST',true,$post_elements);
		else
			{
			$this->updateDebugBuffer('login_post',$form_action,'POST',false,$post_elements);	
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
		
		$this->login_ok="http://www.boardermail.com/scripts/addr/addressbook.cgi?showaddressbook=1&.ob=";
		return true;
		}

	/**
	 * Get the current user's contacts
	 * 
	 * Makes all the necesarry requests to import
	 * the current user's contacts
	 * 
	 * @return mixed The array if contacts if importing was successful, FALSE otherwise.
	 */	
	public function getMyContacts()
		{
		if (!$this->login_ok)
			{
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
		else $url=$this->login_ok;
		$res=$this->get($url,true);
		if ($this->checkResponse("url_contacts",$res))
			$this->updateDebugBuffer('url_contacts',$url,'GET');
		else
			{
			$this->updateDebugBuffer('url_contacts',$url,'GET',false);	
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
			
		$contacts=array();
		$doc=new DOMDocument();libxml_use_internal_errors(true);if (!empty($res)) $doc->loadHTML($res);libxml_use_internal_errors(false);
		$xpath=new DOMXPath($doc);$query="//a";$data=$xpath->query($query);
		foreach ($data as $node)
			{
			$hrefBulk=$node->getAttribute('href');
			if (strpos($hrefBulk,'Outblaze.mail?compose')!==false) 
				{
				$email=urldecode($this->getElementString($hrefBulk,'%3C','%3E'));
				$name=$node->nodeValue;
				if (!empty($email)) $contacts[$email]=array('first_name'=>(!empty($name)?$name:false),'email_1'=>$email);
				}
			}
		foreach ($contacts as $email=>$name) if (!$this->isEmail($email)) unset($contacts[$email]);
		return $this->returnContacts($contacts);
		}

	/**
	 * Terminate session
	 * 
	 * Terminates the current user's session,
	 * debugs the request and reset's the internal 
	 * debudder.
	 * 
	 * @return bool TRUE if the session was terminated successfully, FALSE otherwise.
	 */	
	public function logout()
		{
		if (!$this->checkSession()) return false;
		$res=$this->get("http://www.boardermail.com/scripts/mail/Outblaze.mail?logout&",true);
		$this->debugRequest();
		$this->resetDebugger();
		$this->stopPlugin();
		return true;	
		}
	
	}	

?>