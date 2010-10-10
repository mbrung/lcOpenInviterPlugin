 <div id="invite-sent"> 
	<?php if($sent): ?>
	  <div class="sent-success">
		  <h2> <?php echo __("Invitations Sent")?> </h2>
		  <p><?php echo __("Your invitation(s) has been sent succesfully.")?></p>
		</div>
		<?php echo link_to("Invite more","openInviter/index") ?>
	<?php else: ?>
	  <div class="sent-error">
		  <h2> <?php echo __("Invitations Failed")?> </h2>
		  <p><?php echo __("There were errors while sending your invites.  Please try again later!")?></p>
		</div>
	  <?php echo link_to(__("Invite again"),"openInviter/index") ?>
	<?php endif; ?>
</div>
