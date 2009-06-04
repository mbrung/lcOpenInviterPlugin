<?php

class ShowContactsForm extends sfForm
{
    
  public function configure()
  {  

  	 /**********************************
     *    Widget Schema Definition     *
     ***********************************/
		$this->setWidgets(array(
		    'contacts'   => new sfWidgetFormSelectCheckbox( array('choices'  => $this->getOption('contacts'))),
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
	                         array('choices'  => array_keys($this->getOption('contacts'))),
	                         array('required' => 'You have to select at least 1 contact')
	                 ),
	    'message'   => new sfValidatorString(array('required' => false)),
	    'email'     => new sfValidatorString(),
	    'password'  => new sfValidatorString(),             
	    'provider'  => new sfValidatorString(),
	    'sessionId' => new sfValidatorString(),
	    'plugType'  => new sfValidatorString()
    ));
    
    
    
  foreach($this->getOption('contacts') as $email => $name)
    {
      $this->widgetSchema["email_or_id_".$email]  = new sfWidgetFormInputHidden();
      $this->widgetSchema["email_or_id_".$email]->setDefault($email);
      
      $this->widgetSchema["contact_name_".$email] = new sfWidgetFormInputHidden();
      $this->widgetSchema["contact_name_".$email]->setDefault($name);
      
      $this->validatorSchema["email_or_id_".$email]  = new sfValidatorString();
      $this->validatorSchema["contact_name_".$email] = new sfValidatorString();
    }
    
  }
    
}

?>