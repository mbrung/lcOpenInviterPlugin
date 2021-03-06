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
    public  $pluginSuffix = '.plg';
    private $ignoredFiles = array('default.php'=>'', 'index.php'=>'', '_base.php' =>'', '_hosted.plg.php' => '' );
    private $version = "";
    private $basePath;
    public  $plugins;
    private $availablePlugins = array();
	private $currentPlugin    = array();

    public function __construct()
    {
        parent::__construct();
        $this->basePath = dirname(__FILE__);
        $this->version = parent::getVersion();
        set_time_limit(0);
    }


    /****************************************
     *    Error handling functions thrown   *
     *    in GetContactsForm.class.php      *
     * **************************************/
    // overriding the function startPlugin
    public function startPlugin($plugin_name, $getPlugins=false)
    {
        $plugins_dir = $this->basePath."/extern/openInviter/plugins";
        $conf_dir    = $this->basePath."/extern/openInviter/conf";
        //error thrown in GetContactsForm.class.php
        if (!file_exists($this->basePath."/installation-complete.dat")) {
            $this->internalError = 1;
        } elseif(!self::checkMessageConfig()) {
            $this->internalError = 2;
        } elseif (file_exists($plugins_dir."/{$plugin_name}".$this->pluginSuffix.".php"))
        {
            $ok=true;
            if (!class_exists($plugin_name))
            include_once($plugins_dir."/{$plugin_name}".$this->pluginSuffix.".php");
            
            $this->currentPlugin = $plugin_name;
            $this->plugin=new $plugin_name();
            $this->plugin->settings=$this->settings;
            $this->plugin->base_version=$this->version;
            $this->plugin->base_path = $this->basePath."/extern/openInviter";
            //Setting the current plugin, it is used in checkLoginCredentials()
            $this->currentPlugin=$this->availablePlugins[$plugin_name];
            if (file_exists($conf_dir."/{$plugin_name}.conf"))
            {
                include($conf_dir."/{$plugin_name}.conf");
                if (empty($enable)) $this->internalError="Invalid service provider";
                if (!empty($messageDelay)) $this->plugin->messageDelay=$messageDelay; else  $this->plugin->messageDelay=1;
                if (!empty($maxMessages)) $this->plugin->maxMessages=$maxMessages; else $this->plugin->maxMessages=10;
            }
        }
        else {
            $this->internalError="Invalid service provider";
        }
    }
    
    // overriding the function checkLoginCredentials
    public function checkLoginCredentials($user)
    {
        $is_email=$this->plugin->isEmail($user);
        
        if ( $this->currentPlugin['requirement'] )
        {
            if ($this->currentPlugin['requirement']=='email' && !$is_email)
            {
                $this->internalError="Please enter the full email, not just the username";
                return false;
            }
            elseif ($this->currentPlugin['requirement']=='user' && $is_email)
            {
                $this->internalError="Please enter just the username, not the full email";
                return false;
            }
        }
        
        if ( $this->currentPlugin['allowed_domains'] && $is_email)
        {
            $temp=explode('@',$user);$user_domain=$temp[1];$temp=false;
             foreach ($this->currentPlugin['allowed_domains'] as $domain)
				if (preg_match($domain,$user_domain)) 
				{ 
					$temp=true;
					break; 
				}
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
        $plugins_dir = $this->basePath."/extern/openInviter/plugins";
        $conf_dir    = $this->basePath."/extern/openInviter/conf";

        // get contacts form
        if(!$update) {
            $array_file = $this->getUserWishList();
            //running updates task or the install task (first time)
        } else {
            $array_file = $this->readAll();
        }
        
        if (count($array_file)>0)
        {
            sort($array_file);
            foreach($array_file as $key=>$file)
            {
                $val=str_replace("{$plugins_dir}/",'',$file);
                $plugin_key=str_replace( $this->pluginSuffix.'.php','',$val);

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
        
        if (!empty($plugins)) {
            // Setting the available plugins
            $temp = array();
            foreach ($plugins as $type=>$type_plugins) 
              $temp = array_merge($temp,$type_plugins); 
		    $this->availablePlugins=$temp;
		    //return the plugins
            return $plugins;
        } else {
            return false;
        }
    }

    /***********************************************
     *    overriding the getPlugins()  function     *
     * **********************************************/

    public function getMyContacts()
    {
        $contacts=$this->plugin->getMyContacts();
        //if ($contacts!==false) $this->statsRecordImport(count($contacts));
        return $contacts;
    }

    public function sendMessage($session_id,$message,$contacts)
    {
        $this->plugin->init($session_id);
        $internal=$this->getInternalError();
        if ($internal) return false;
        if (!method_exists($this->plugin,'sendMessage'))
        {
            //$this->statsRecordMessages('E',count($contacts));
            return -1;
        }
        else
        {
            $sent=$this->plugin->sendMessage($session_id,$message,$contacts);
            //if ($sent!==false) $this->statsRecordMessages('I',count($contacts));
            return $sent;
        }
    }

	/**
	 * Login function
	 * 
	 * Acts as a wrapper function for the plugin's
	 * login function.
	 * 
	 * @param string $user The username being logged in
	 * @param string $pass The password for the username being logged in
	 * @return mixed FALSE if the login credentials don't match the plugin's requirements or the result of the plugin's login function.
	 */
	public function login($user,$pass)
    {
    	// removed the check login because it's already done in the form validation
		// if (!$this->checkLoginCredentials($user)) return false;
		return $this->plugin->login($user,$pass);
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
        $plugins_dir = $this->basePath."/extern/openInviter/plugins";
        $wishlist = sfConfig::get("app_lcOpenInviter_wish-list");
        $temp = array();

        if(!isset($wishlist))
        {
            $array_file =  self::getDefaultWishList();
            foreach($array_file as $key => $val)
            $temp[$plugins_dir."/".$key.".php"] = $plugins_dir."/".$key.$this->pluginSuffix.".php";
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
                if(!isset($this->ignoredFiles[$val.".php"]) && in_array($plugins_dir."/".$val.$this->pluginSuffix.".php", array_keys($plugins_dir_list)))
                $temp[$plugins_dir."/".$val.".php"] = $plugins_dir."/".$val.$this->pluginSuffix.".php";
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
        $plugins_dir = $this->basePath."/extern/openInviter/plugins";
        $array_file=array();
         
        $temp=glob("{$plugins_dir}/*.php");
        foreach ($temp as $file) {
//            echo 'File: '.$file.' - Check: '.str_replace("{$plugins_dir}/",'',$file)."\n";

            if (($file!=".") AND ($file!="..") AND (!isset($this->ignoredFiles[str_replace("{$plugins_dir}/",'',$file)]))) {
                $array_file[$file]=$file;
            }
        }

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



