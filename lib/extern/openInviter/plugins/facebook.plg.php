<?php
/*Import Friends from Facebook
 * You can send message to your Friends Inbox
 */
$_pluginInfo=array(
	'name'=>'Facebook',
	'version'=>'1.2.1',
	'description'=>"Get the contacts from a Facebook account",
	'base_version'=>'1.8.0',
	'type'=>'social',
	'check_url'=>'http://m.facebook.com/index.php',
	'requirement'=>'email',
	'allowed_domains'=>false,
	);
/**
 * FaceBook Plugin
 * 
 * Imports user's contacts from FaceBook and sends
 * messages using FaceBook's internal system.
 * 
 * @author OpenInviter
 * @version 1.0.8
 */
class facebook extends openinviter_base
	{
	private $login_ok=false;
	public $showContacts=true;
	public $internalError=false;
	protected $timeout=30;
	
	public $debug_array=array(
				'initial_get'=>'pass',
				'login_post'=>'accesskey="2"',
				'url_friends'=>'&amp;a&amp;refid=5',
				'get_friends'=>'summary border_bottom',
				'update_status'=>'notice border_bottom',
				'url_message'=>'body',
				'send_message'=>'notice'
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
		$this->service='facebook';
		$this->service_user=$user;
		$this->service_password=$pass;
		if (!$this->init()) return false;
		
		$res=$this->get("http://m.facebook.com/",true);
		if ($this->checkResponse("initial_get",$res))
			$this->updateDebugBuffer('initial_get',"http://www.facebook.com/",'GET');
		else
			{
			$this->updateDebugBuffer('initial_get',"http://www.facebook.com/",'GET',false);
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
			
		$form_action=str_replace("&amp;","&",urldecode($this->getElementString($res,'action="','"')));
		$post_elements=array('email'=>$user,'pass'=>$pass,'login'=>'Login In');
		$res=$this->post($form_action,$post_elements,true,true);
		if ($this->checkResponse("login_post",$res))
			$this->updateDebugBuffer('login_post',"{$form_action}",'POST',true,$post_elements);
		else
			{
			$this->updateDebugBuffer('login_post',"{$form_action}",'POST',false,$post_elements);
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
		
		$url_friends_array=$this->getElementDOM($res,"//a[@accesskey='2']",'href');
		$url_friends='http://m.facebook.com'.$url_friends_array[0];
		$res=$this->get($url_friends,true);
		if ($this->checkResponse("url_friends",$res))
			$this->updateDebugBuffer('url_friends',$url_friends,'GET');
		else
			{
			$this->updateDebugBuffer('url_friends',$url_friends,'GET',false);
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
		
		preg_match("#\<\/a\> \&\#8226\; \<a href\=\"\/friends\.php\?(.+)\&amp\;a\&amp\;refid\=5#U",$res,$url_my_friends_array);
		if (!empty($url_my_friends_array[1])) $url_my_friends="http://m.facebook.com/friends.php?{$url_my_friends_array[1]}&a&refid=5";
		else $url_my_friends=false;
		$this->login_ok=$url_my_friends;
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
		if ($this->checkResponse("get_friends",$res))
			$this->updateDebugBuffer('get_friends',"{$url}",'GET');
		else
			{
			$this->updateDebugBuffer('get_friends',"{$url}",'GET',false);
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
		$nextPage=true;$friends=10;$page=1;$contacts=array();
		while($nextPage)
			{
			$nextPage=false;
			if (preg_match_all("#\<td\>\<a href\=\"\/profile\.php(.+)\>(.+)\<\/a\>#U",$res,$names))
				if (!empty($names[2]))				
					if (preg_match_all("#\<small\>\<a href\=\"\/inbox\/(.+)\"\>#U",$res,$hrefs))
						{
						if (!empty($hrefs[1][0])) unset($hrefs[1][0]);
						foreach($hrefs[1] as $key=>$href)
							if (!empty($names[2][($key-1)]))
								if (!isset($contacts["/inbox".$href])) { $contacts["/inbox".$href]=htmlspecialchars($names[2][($key-1)]); $nextPage=true; }
						}							
			//get the next page				
			if (!empty($nextPage))
				if (preg_match("#{$page}\&nbsp\;\<a href\=\"\/friends\.php\?(.+)\&amp\;a\&amp\;f={$friends}\&amp\;refid\=5\"\>#U",$res,$matches))
					{ $nextPage="http://m.facebook.com/friends.php?{$matches[1]}&a&f={$friends}&refid=5";$res=$this->get($nextPage,true);$friends+=10;$page++; }
			}
		return $contacts;
		}

	/**
	 * Send message to contacts
	 * 
	 * Sends a message to the contacts using
	 * the service's inernal messaging system
	 * 
	 * @param string $session_id The OpenInviter user's session ID
	 * @param string $message The message being sent to your contacts
	 * @param array $contacts An array of the contacts that will receive the message
	 * @return mixed FALSE on failure.
	 */
	public function sendMessage($session_id,$message,$contacts)
		{
		$res=$this->get('http://m.facebook.com',true);
		if (preg_match("#\/a\/home\.php\?r(.+)\"#U",$res,$form_action_array))
			$form_action='http://m.facebook.com'.str_replace('&amp;','&',$form_action_array[0]);	
		else
			{
			$this->updateDebugBuffer('url_friends',"http://m.facebook.com",'GET',false);
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			} 		
		$post_elements=$this->getHiddenElements($res);$post_elements['status']=$message['body'];$post_elements['update']=$this->getElementString($res,'name="update" value="','"');
		$res=$this->post($form_action,$post_elements,true);
		if ($this->checkResponse("update_status",$res))
			$this->updateDebugBuffer('update_status',"{$form_action}",'POST',true,$post_elements);
		else
			{
			$this->updateDebugBuffer('update_status',"{$form_action}",'POST',false,$post_elements);
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
		
		$countMessages=0;
		foreach ($contacts as $href=>$name)
			{			
			$countMessages++;
			$formatedHref='http://m.facebook.com'.str_replace('&amp;','&',$href);
			$res=$this->get($formatedHref,true);
			if ($this->checkResponse("url_message",$res))
				$this->updateDebugBuffer('url_message',$formatedHref,'GET');
			else
				{
				$this->updateDebugBuffer('url_message',$formatedHref,'GET',false);
				$this->debugRequest();
				$this->stopPlugin();
				return false;
				}
			
			$form_action_array=$this->getElementDOM($res,"//form[@method='post']","action");
			$form_action='http://m.facebook.com'.str_replace('&amp;','&',$form_action_array[0]);
			$post_elements=array('subject'=>$message['subject'],
								'body'=>$message['body'],
								'send'=>$this->getElementString($res,'name="send" value="','"'),
								'post_form_id'=>$this->getElementString($res,'name="post_form_id" value="','"'),
								'compose'=>1,
								'ids'=>$this->getElementString($res,'name="ids" value="','"'),
								);
			$res=$this->post($form_action,$post_elements,true);
			if ($this->checkResponse("send_message",$res))
				$this->updateDebugBuffer('send_message',"{$form_action}",'POST',true,$post_elements);
			else
				{
				$this->updateDebugBuffer('send_message',"{$form_action}",'POST',false,$post_elements);
				$this->debugRequest();
				$this->stopPlugin();
				return false;
				}
			sleep($this->messageDelay);
			if ($countMessages>$this->maxMessages) {$this->debugRequest();$this->resetDebugger();$this->stopPlugin();break;}
			}
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
		$res=$this->get("http://m.facebook.com",true);
		if (!empty($res))
			{
			$url_logout="http://m.facebook.com/logout.php".str_replace('&amp;','&',$this->getElementString($res,'/logout.php','"'));
			$res=$this->get($url_logout,true);
			}
		$this->debugRequest();
		$this->resetDebugger();
		$this->stopPlugin();
		return true;	
		}
	}	

?>