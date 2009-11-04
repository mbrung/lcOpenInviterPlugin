<?php
$_pluginInfo=array(
	'name'=>'AOL',
	'version'=>'1.5.1',
	'description'=>"Get the contacts from an AOL account",
	'base_version'=>'1.8.0',
	'type'=>'email',
	'check_url'=>'http://webmail.aol.com',
	'requirement'=>'email',
	'allowed_domains'=>array('/(aol.com)/i'),
	);
/**
 * AOL Plugin
 * 
 * Imports user's contacts from AOL's AddressBook
 * 
 * @author OpenInviter
 * @version 1.4.7
 */
class aol extends openinviter_base
	{
	private $login_ok=false;
	public $showContacts=true;
	public $internalError=false;
	protected $timeout=30;
	
	public $debug_array=array(
			 'initial_get'=>'logintabs',
	    	 'login_post'=>'gSuccessPath',
	    	 'inbox'=>'aol.wsl.afExternalRunAtLoad = []',
	    	 'print_contacts'=>'window\x27s'
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
		$this->service='aol';
		$this->service_user=$user;
		$this->service_password=$pass;
		if (!$this->init()) return false;
		
		$user=(strpos($user,'@aol')!==false?str_replace('@aol.com','',$user):$user);
		
		$res=$this->get("http://webmail.aol.com",true);
		if ($this->checkResponse('initial_get',$res))
			$this->updateDebugBuffer('initial_get',"http://webmail.aol.com",'GET');
		else 
			{
			$this->updateDebugBuffer('initial_get',"http://webmail.aol.com",'GET',false);
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}  
			
		$post_elements=$this->getHiddenElements($res);$post_elements['loginId']=$user;$post_elements['password']=$pass;
		$res=$this->post("https://my.screenname.aol.com/_cqr/login/login.psp",$post_elements,true);
		if ($this->checkResponse('login_post',$res))	
			$this->updateDebugBuffer('login_post',"https://my.screenname.aol.com/_cqr/login/login.psp",'POST',true,$post_elements);
		else
			{
			$this->updateDebugBuffer('login_post',"https://my.screenname.aol.com/_cqr/login/login.psp",'POST',false,$post_elements);
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
			
		$url_redirect="http://webmail.aol.com".htmlspecialchars_decode($this->getElementString($res,'var gSuccessPath = "','"',$res));
		$url_redirect=str_replace("Suite.aspx","Lite/Today.aspx",$url_redirect);
		$res=$this->get($url_redirect,true);
		if ($this->checkResponse('inbox',$res))
			$this->updateDebugBuffer('inbox',"{$url_redirect}",'GET');
		else 
			{
			$this->updateDebugBuffer('inbox',"{$url_redirect}",'GET',false);
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
			
		$url_contact=$this->getElementDOM($res,"//a[@id='contactsLnk']",'href');
		$this->login_ok=$this->login_ok=$url_contact[0];
		file_put_contents($this->getLogoutPath(),$url_contact[0]);
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
		else
			$url=$this->login_ok;
		//go to url inbox
		$res=$this->get($url,true);

		
		$url_temp=$this->getElementString($res,"command.','','","'");
		$version=$this->getElementString($url_temp,'http://webmail.aol.com/','/');
		$url_print=str_replace("');","",str_replace("PrintContacts.aspx","addresslist-print.aspx?command=all&sort=FirstLastNick&sortDir=Ascending&nameFormat=FirstLastNick&version={$version}:webmail.aol.com&user=",$url_temp));
		$url_print.=$this->getElementString($res,"addresslist-print.aspx','","'");
		

	 	$res=$this->get($url_print,true);
	
		$contacts=array();
		if ($this->checkResponse("print_contacts",$res))
			{
			$doc=new DOMDocument();libxml_use_internal_errors(true);if (!empty($res)) $doc->loadHTML($res);libxml_use_internal_errors(false);
			$nodes=$doc->getElementsByTagName("span");$name=false;$flag_name=false;$flag_email=false;
			$temp=array();
			$descriptionArrayFlag=array('Screen Name:'=>'nickname','Email 1:'=>'email_1','Email 2:'=>'email_2','Mobile: '=>'phone_mobile','Home: '=>'phone_home','Work: '=>'phone_work','Pager: '=>'pager','Fax: '=>'fax_work','Family Names:'=>'last_name');
			$xpath=new DOMXPath($doc);$query="//span";$data=$xpath->query($query);
			foreach($data as $node)
				{
				if ($node->getAttribute("class")=="fullName") { $nameD=$node->nodeValue;$temp=array(); }
				if (end($temp)!==false)
					{
					$key=key($temp);
					if ($key=='Email 1:') $keyDescription=$node->nodeValue;
					if (!empty($keyDescription))
						{
						if (empty($contacts[$keyDescription]['first_name'])) $contacts[$keyDescription]['full_name']=!empty($nameD)?$nameD:false;
						$contacts[$keyDescription][$descriptionArrayFlag[$key]]=!empty($node->nodeValue)?$node->nodeValue:false; $temp[$key]=false;
						}
					}
				if (isset($descriptionArrayFlag[$node->nodeValue])) $temp[$node->nodeValue]=true;
				}
			$this->updateDebugBuffer('print_contacts',"{$url_print}",'GET');
			}
		else
			{ 
			$this->updateDebugBuffer('print_contacts',"{$url_print}",'GET',false);
			$this->debugRequest();
			$this->stopPlugin();
			return false;
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
		if (file_exists($this->getLogoutPath()))
			{
			$url=file_get_contents($this->getLogoutPath());
			$res=$this->get($url,true);
			$url_logout=$this->getElementDOM($res,"//a[@class='signOutLink']",'href');
			if (!empty($url_logout)) $res=$this->get($url_logout[0]);
			}
		$this->debugRequest();
		$this->resetDebugger();
		$this->stopPlugin();
		return true;
		}
				
	}
?>