<?php include_stylesheets_for_form($form) ?>
<?php include_javascripts_for_form($form) ?>

<?php
  use_javascript('/lcOpenInviterPlugin/js/jquery-1.2.6.js');
  use_javascript('/lcOpenInviterPlugin/js/openinviter.js');
  use_stylesheet('/lcOpenInviterPlugin/css/openinviter.css');
?>

<div class="open-inviter-container">
	  <h2>Enter your information</h2>
	  <form action="<?php echo url_for("openInviter/show")?>" method="post">
	    <table id="open-inviter-login">
	      <tfoot>
	        <tr>
	          <td colspan="2">
	            <input type="submit" value="Get Contacts" />
	          </td>
	        </tr>
	      </tfoot>
	      <tbody>
	        <?php echo $form ?>
	      </tbody>
	    </table>
	  </form>
</div>