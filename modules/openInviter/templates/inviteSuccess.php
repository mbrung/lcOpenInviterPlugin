<?php
  use_javascript('/lcOpenInviterPlugin/js/openinviter.js');
  use_stylesheet('/lcOpenInviterPlugin/css/openinviter.css');
?>

 <?php 
  foreach ($form as $widget_key => $widget)
  {
    if($widget->getName() == "contacts")
    {
    		$options = $widget->getWidget()->getOptions();
    		$choices = $options['choices'];
    }
  }
 
 ?>
 
 <div class="open-inviter-container">
   <h2><?php echo __('Send your invitation')?></h2>
   <form action="<?php echo url_for("openInviter/invite")?>" method="post" id="openinviter">
		 
		 <div id="open-inviter-contacts-list">
			 <table>
			   <tr>
			      <td colspan="2"><?php echo $form['contacts']->renderError() ?></td>
			   </tr>
			    <tr>
			      <td> <input type="checkbox" id="checkbox_selector" onChange="toggleAll(this)" /> </td>
			      <th> <?php echo __("Contacts")?> </th>
			    </tr>
		     <?php foreach($choices as $email => $name): ?>
		       <tr>
		        <td> 
		          <?php echo $form['contacts']->getWidget()->renderTag("input", array("type" => "checkbox", "value" => $email, "name" => "showInviter[contacts][]", "id" => "showInviter_contacts_".$email)) ?> 
		          <?php echo $form["email_or_id_".$email] ?>
		          <?php echo $form["contact_name_".$email] ?>
		        
		        </td>
		        <td> <label for="showInviter_contacts_<?php echo $email ?>"><?php echo $name ?></td>
		       </tr>
		     <?php endforeach; ?>
			 </table>
		 </div>
		 
		 <table id="invite-message">
		   <tr>
         <td><?php echo $form['message']->renderLabel()?></td>
         <td><?php echo $form['message'] ?></td>
       </tr>
       <tfoot>
        <tr>
          <td colspan="2">
            <input type="submit" value="Invite" />
          </td>
        </tr>
      </tfoot>
		 </table>
		 
		 <!-- hidden fields -->
		 <?php echo $form['email']; ?>
		 <?php echo $form['password']; ?>
		 <?php echo $form['provider']; ?>
		 <?php echo $form['sessionId']; ?>
		 <?php echo $form['plugType']; ?>
		 
   </form>
</div>
 