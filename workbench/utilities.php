<?php
require_once ('session.php');
require_once ('shared.php');
require_once ('header.php');



if(isset($_POST['passwordChangeType'])){
	changePassword($_POST['passwordChangeType']);
} else {
	displayForm();
}


function changePassword($passwordChangeType){	
	global $mySforceConnection;
	
	try{
		if($passwordChangeType == 'set' && isset($_POST['userId']) && isset($_POST['passwordOne'])){
			if($_POST['passwordOne'] == $_POST['passwordConfirm']){
				$mySforceConnection->setPassword($_POST['userId'],$_POST['passwordOne']);
				$infos[] = "Successfully set password for " . $_POST['userId'];
			} else {
				$errors[] = "Passwords must match, and don't be sneaky and turn off JavaScript";
			}
		} else if($passwordChangeType == 'reset' && isset($_POST['userId'])){
			$changePasswordResult = $mySforceConnection->resetPassword($_POST['userId']);
			$infos[] = "Successfully reset password for " . $_POST['userId'];
		}
	} catch(Exception $e){
		$errors[] = $e->getMessage();
	}
	
	displayForm($infos, $errors);
	
}


function displayForm($infos=null, $errors=null){
?>

<style>
<!--

	td label{
		font-weight: bold;
	}
	
-->
</style>

<script type="text/javascript">
<!--
 
 function togglePasswordFields(changeType){
 
 	if(changeType == 'set'){
 		document.getElementById('passwordOne').disabled = false;
 		document.getElementById('passwordConfirm').disabled = false;
 		
 		//document.getElementById('changePasswordAction').value = 'Set Password';
  	} else if(changeType == 'reset'){
 		document.getElementById('passwordOne').value = null;
 		document.getElementById('passwordConfirm').value = null;
 		
 		document.getElementById('passwordOne').disabled = true;
 		document.getElementById('passwordConfirm').disabled = true;
 		
 		document.getElementById('passwordOne').style.background = 'white';
 		document.getElementById('passwordConfirm').style.background = 'white';
 		
 		//document.getElementById('changePasswordAction').value = 'Reset Password';
 	}
 	
 }
 
 
 function doPasswordsMatch(doAlert){
 	 if(document.getElementById('passwordOne').value.length < document.getElementById('passwordConfirm').value.length){ 	
 	 	document.getElementById('passwordConfirm').style.background = 'LightPink';
 	 }
 	 
 	 if(doAlert && document.getElementById('passwordOne').value.length == 0 && document.getElementById('passwordOne').disabled == false){ 	
 	 	 document.getElementById('passwordOne').style.background = 'LightPink';
  	 	 alert('Must provide a password if setting password; otherwise, choose reset');
  	 	 document.getElementById('passwordOne').focus();
 	 	 return false;
 	 } else {
 	 	document.getElementById('passwordOne').style.background = 'white';
 	 }
 	 
 
 	 if(document.getElementById('passwordOne').value == document.getElementById('passwordConfirm').value){
 	 	document.getElementById('passwordConfirm').style.background = 'white';
		return true;
 	 } else {
 	 	 if(doAlert){
 	 	 	document.getElementById('passwordConfirm').style.background = 'LightPink';
 	 	 	alert('Passwords do not match');
 	 	 }
 	 	
 	 	if(document.getElementById('passwordOne').value.length == document.getElementById('passwordConfirm').value.length){ 	
 	 		document.getElementById('passwordConfirm').style.background = 'LightPink';
 	 	}
 	 	 
 	 	return false;
 	 }
 }
 
 function clearForm(){
	document.getElementById('userId').value = null;
 	document.getElementById('passwordOne').value = null;
 	document.getElementById('passwordConfirm').value = null;
 	document.getElementById('passwordOne').style.background = 'white';
 	document.getElementById('passwordConfirm').style.background = 'white';
 }
	
//-->
</script>

	<?php 
	if(isset($infos)) show_info($infos);
	if(isset($errors)) show_error($errors); 
	?>

	<h2>Password Management</h2>

	<form name='passwordChange' method='post' action='<?php $_SERVER[PHP_SELF] ?>'>
	<table border='0'>
		
		<tr><td colspan='2' align='right'>
			<p>
			<label><input type='radio' name='passwordChangeType' value='set' onclick="togglePasswordFields('set');" checked='checked' /> Set</label>
			&nbsp;
			<label><input type='radio' name='passwordChangeType' value='reset' onclick="togglePasswordFields('reset');"/> Reset</label>
			</p>
		</td></tr>
		
		<tr>
			<td align='right'><label for='userId'>User Id: &nbsp;</label></td>
			<td><input type='text' id='userId' name='userId' size='45'/></td>
		</tr>
		
		<tr>	
			<td align='right'><label for='passwordOne'>Password: &nbsp;</label></td>
			<td><input type='password' id='passwordOne' name='passwordOne' size='45' onkeyup="doPasswordsMatch(false);"/></td>
		</tr>
		<tr>
			<td align='right'><label for='passwordConfirm'>Confirm Password: &nbsp;</label></td>
			<td><input type='password' id='passwordConfirm' name='passwordConfirm' size='45' onkeyup="doPasswordsMatch(false);"/></td>
		</tr>
		
		<tr><td colspan='2' align='right'>
			<p>
			<input type='submit' id='changePasswordAction' name='changePasswordAction' value='Change Password' onclick="return doPasswordsMatch(true);"/>
			<input type='button' value='Clear Form' onclick="clearForm();"/>
			
			</p>
		</td></tr>
		
	</table>
	</form>

	
<?php
}
require_once ('footer.php');
?>