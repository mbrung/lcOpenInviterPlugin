<?php

class ShowContactsForm extends sfForm
{
    
  public function configure()
  {

    // handling a special case for "facebook"
    // the problem is that the keys of the returned array contacts are the url parameters not the ids as before
    // therefore, I had a form invlaid error
    // solution: I index the keys (0,1,2,...) only for facebook
    // and I get the url parameters values (the original keys) and put them in another array with same new indexed key
    $indexed_contacts_array_values = array_values($this->getOption('contacts'));
    $indexed_contacts_array_keys   = array_keys($this->getOption('contacts'));

    $provider  = $this->getOption('provider');

    if($provider == "facebook")
    {
      $contacts = $indexed_contacts_array_values;
    }
    else
    {
      $contacts = $this->getOption('contacts');
    }

    
  	 /**********************************
     *    Widget Schema Definition     *
     ***********************************/
		$this->setWidgets(array(
		    'contacts'   => new sfWidgetFormSelectCheckbox( array('choices'  => $contacts)),
		    'message'    => new sfWidgetFormTextarea(),
        'email'      => new sfWidgetFormInputHidden(),
		    'password'   => new sfWidgetFormInputHidden(),
		    'provider'   => new sfWidgetFormInputHidden(),
		    'sessionId'  => new sfWidgetFormInputHidden(),
        'plugType'   => new sfWidgetFormInputHidden()		                  
			));
			
		
		
		$this->widgetSchema->setLabels(array(
			'contacts'   => 'Your contacts',
		  'message'    => 'Attached message'
			));

		$this->setDefaults(array(
      'email'       => $this->getOption('email'),
		  'password'    => $this->getOption('password'),
		  'provider'    => $this->getOption('provider'),
      'sessionId'   => $this->getOption('sessionId'),	
		  'plugType'    => $this->getOption('plugType')	
      ));
	  
	  $this->widgetSchema->setFormFormatterName('table'); 
    $this->widgetSchema->setNameFormat('showInviter[%s]');
	  
    
    
    /*************************************
     *   Validator Schema Definition     *
     *************************************/	  
	  $this->setValidators(array(
      'contacts'  => new sfValidatorChoiceMany( 
	                         array('choices'  => array_keys($contacts)),
	                         array('required' => 'You have to select at least 1 contact')
	                 ),
	    'message'   => new sfValidatorString(array('required' => false)),
	    'email'     => new sfValidatorString(),
	    'password'  => new sfValidatorString(),             
	    'provider'  => new sfValidatorString(),
	    'sessionId' => new sfValidatorString(),
	    'plugType'  => new sfValidatorString()
    ));
    
  
  foreach($contacts as $key => $name)
    {
      $this->widgetSchema["email_or_id_".$key]  = new sfWidgetFormInputHidden();

      //facebook special case
      if($provider == "facebook")
      {
           $this->widgetSchema["email_or_id_".$key]->setDefault($indexed_contacts_array_keys[$key]);
      }
      else
      {
        $this->widgetSchema["email_or_id_".$key]->setDefault($key);
      }
      
      $this->widgetSchema["contact_name_".$key] = new sfWidgetFormInputHidden();
      $this->widgetSchema["contact_name_".$key]->setDefault($name);
      
      $this->validatorSchema["email_or_id_".$key]  = new sfValidatorString();
      $this->validatorSchema["contact_name_".$key] = new sfValidatorString();
    }
    
  }
    
}

?>