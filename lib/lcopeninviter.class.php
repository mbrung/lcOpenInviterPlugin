<?php
/**
 * LetsCod
 * 
 * @author Elie Andraos
 * @version 1.6.7
 */

include_once dirname(__FILE__)."/extern/openInviter/openinviter.php";
 
class LcOpenInviter extends OpenInviter
{

	  public  $pluginTypes  = array('email'=>'Email Providers','social'=>'Social Networks');
    private $ignoredFiles = array('default.php'=>'','index.php'=>'');
    private $version      = '1.7.2';
    
    //my added variables
    //private $pluginsDir   =  dirname(__FILE__)."/extern/openInviter/plugins";
    //private $confDir      =  dirname(__FILE__)."/extern/openInviter/conf";
    
	  public function __construct()
		{
		  parent::__construct();
		}

		
		/****************************************
		 *    Error handling functions thrown   *
		 *    in GetContactsForm.class.php      *
		 * **************************************/
		
		// overriding the function startPlugin		
    public function startPlugin($plugin_name)
    {
    	$plugins_dir = dirname(__FILE__)."/extern/openInviter/plugins";
      $conf_dir    = dirname(__FILE__)."/extern/openInviter/conf";
      
      //error thrown in GetContactsForm.class.php
      if (!file_exists(dirname(__FILE__)."/installation-complete.dat"))
        $this->internalError = 1;
      elseif(!self::checkMessageConfig())
        $this->internalError = 2;
      elseif (file_exists($plugins_dir."/{$plugin_name}.php"))
      {
        $ok=true;
        if (!class_exists($plugin_name)) 
          include_once(dirname(__FILE__)."/plugins/{$plugin_name}.php");
        $this->plugin=new $plugin_name();
        $this->plugin->settings=$this->settings;
        $this->plugin->base_version=$this->version;
        $this->plugin->base_path = dirname(__FILE__)."/extern/openInviter";
        
        if (file_exists($conf_dir."/{$plugin_name}.conf")) 
        { 
          include($conf_dir."/{$plugin_name}.conf");
          if (empty($enable)) $this->internalError="Invalid service provider";
          if (!empty($messageDelay)) $this->plugin->messageDelay=$messageDelay; else  $this->plugin->messageDelay=1;
          if (!empty($maxMessages)) $this->plugin->maxMessages=$maxMessages; else $this->plugin->maxMessages=10;
        }
      }
    else
      $this->internalError="Invalid service provider";
    }
	
   
   // overriding the function checkLoginCredentials  
   public function checkLoginCredentials($user)
   {
	    $is_email=$this->plugin->isEmail($user);

	    if ($this->plugin->requirement)
	      {
	      if ($this->plugin->requirement=='email' AND !$is_email)
	        {
	        $this->internalError="Please enter the full email, not just the username";
	        return false;
	        }
	      elseif ($this->plugin->requirement=='user' AND $is_email)
	        {
	        $this->internalError="Please enter just the username, not the full email";
	        return false;
	        }
	      }

	    if ($this->plugin->allowed_domains AND $is_email)
	      {
	      	
	      $temp=explode('@',$user);$user_domain=$temp[1];$temp=false;
	      foreach ($this->plugin->allowed_domains as $domain)
	        if (strpos($user_domain,$domain)!==false) $temp=true;
	      if (!$temp)
	        {
	        $this->internalError="<b>{$user_domain}</b> is not a valid domain for this provider";
	        return false;
	        }
	      }
	    return true;
   }

   
   
     /***********************************************
     *    overriding the getPlugins()  function     *
     * **********************************************/
   
    public function getPlugins($update=false)
    {

	    $plugins=array();
	    $array_file=array();
	    $plugins_dir = dirname(__FILE__)."/extern/openInviter/plugins";
	    $conf_dir    = dirname(__FILE__)."/extern/openInviter/conf";

	    // get contacts form
	    if(!$update)
        $array_file = self::getUserWishList(); 
      //running updates task  
      else
        $array_file = self::readAll();
        
	    if (count($array_file)>0) 
	    {
	      sort($array_file);
	      foreach($array_file as $key=>$file)
	      {
	        $val=str_replace("{$plugins_dir}/",'',$file);
	        $plugin_key=str_replace('.php','',$val);
	           
	        if (file_exists($conf_dir."/{$plugin_key}.conf"))
	        {
	          include_once($conf_dir."/{$plugin_key}.conf");
	          
	          if ($enable AND $update==false)
	          { 
	          	include("{$plugins_dir}/{$val}");
	          	if ($this->checkVersion($_pluginInfo['base_version'])) 
	          	  $plugins[$_pluginInfo['type']][$plugin_key]=$_pluginInfo; 
	          }
	          elseif ($update==true)
	          { 
	          	include("{$plugins_dir}/{$val}"); 
	          	if ($this->checkVersion($_pluginInfo['base_version'])) 
	          	  $plugins[$_pluginInfo['type']][$plugin_key]=array_merge(array('autoupdate'=>$autoUpdate),$_pluginInfo); 
	          }
	        }
	        else
	        {  
	        	include("{$plugins_dir}/{$val}");
	        	if ($this->checkVersion($_pluginInfo['base_version'])) 
	        	  $plugins[$_pluginInfo['type']][$plugin_key]=$_pluginInfo; 
	        	$this->writePlConf($plugin_key,$_pluginInfo['type']);
	        }
	      }
	    }
	      
	    if (count($plugins)>0) 
	      return $plugins;
	    else 
	      return false;
    }
    
   
    // the default providers returned 
    //if nothing is set in the app.yml
    public function getDefaultWishList()
    {
    	return array("hotmail" => "Live/Hotmail", "yahoo" => "Yahoo","gmail" => "Gmail");
    }
    
    
   /*
    *  function that gets the user wish list.
    * @return array
    */
    public function getUserWishList()
    {
    	$plugins_dir = dirname(__FILE__)."/extern/openInviter/plugins";
     	$wishlist = sfConfig::get("app_lcOpenInviter_wish-list");
     	$temp = array();
     	
     	if(!isset($wishlist))
     	{
     	  $array_file =  self::getDefaultWishList();
     	  foreach($array_file as $key => $val)
     	    $temp[$plugins_dir."/".$key.".php"] = $plugins_dir."/".$key.".php";
     	  return $temp;    	  
     	}
     	else
     	{
     		if(is_array($wishlist['providers']) && count($wishlist['providers']) == 1 && $wishlist['providers'][0] == 'all')
     		  return self::readAll();
     		  
     		if(is_array($wishlist['providers']) && count($wishlist['providers']) > 0)
     		{
     			 $plugins_dir_list = self::readAll();
     			 foreach($wishlist['providers'] as $key => $val)
     			   if(!isset($this->ignoredFiles[$val.".php"]) && in_array($plugins_dir."/".$val.".php", array_keys($plugins_dir_list)))
     			      $temp[$plugins_dir."/".$val.".php"] = $plugins_dir."/".$val.".php";
     			 return $temp;
     		}  
     	}	
    }
    
    /*
     *  function that reads all the providers in the plugins dir
     *  @return array
     */
     public function readAll()
     {
     	 $plugins_dir = dirname(__FILE__)."/extern/openInviter/plugins";
     	 $array_file=array();
     	 
     	 $temp=glob("{$plugins_dir}/*.php");
     	 foreach ($temp as $file) 
            if (($file!=".") AND ($file!="..") AND (!isset($this->ignoredFiles[str_replace("{$plugins_dir}/",'',$file)]))) 
                $array_file[$file]=$file;
                
      return $array_file;
     }

     
     
    public function checkMessageConfig()
    {
    	$message = sfConfig::get("app_lcOpenInviter_message");
    	if(isset($message))
    		if (!isset($message['subject']) || !isset($message['body']) || !isset($message['footer']))
    		  return false;
    		else
    		  return true;
    	else
    	 return false;
    }
    
    public static function isValidEmail($email)
    {
      return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email);
    }

    
	
}
	
	

