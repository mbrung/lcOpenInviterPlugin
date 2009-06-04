 <div id="invite-sent"> 
	<?php if($sent): ?>
	  <div class="sent-success">
		  <h2> Invitations Send </h2>
		  <p>your invitation(s) has been sent succesfully</p>
		</div>
		<?php echo link_to("Invite more","openInviter/index") ?>
	<?php else: ?>
	  <div class="sent-error">
		  <h2> Invitations Failed </h2>
		  <p> There were errors while sending your invites.<br>Please try again later!</p>
		</div>
	  <?php echo link_to("Invite again","openInviter/index") ?>
	<?php endif; ?>
</div>
