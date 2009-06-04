<?php
/**
 * LetsCod
 * 
 * @author Elie Andraos
 * @version 1.6.7
 */

  /*
   * function that returns the type of the plugin
   * @params plugins, provider
   * @return string
   */
  function getPluginType($plugins,$provider)
  {
  	if (isset($plugins['email'][$provider])) 
  	   return "email";
    elseif (isset($plugins['social'][$provider])) 
       return "social";
    else
      return NULL;
  }
  
  
?>