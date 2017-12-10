<?php
	//db connection
	require '../DB/connection.inc.php';
	require 'regex.php';
	cleanall($_POST);
	
	//check loginToken
	$loggedIn = false;
	//get cookies (user input)
	$loginToken = clean($_COOKIE['loginToken']);
	$name = clean($_COOKIE['name']);
	$role = clean($_COOKIE['role']);
	//get correct login token (Is User student or Dozent)
	
	
	//PW = hashed matnrno&password, If a password is entered, it is verknüpft with matrnr and hashed for Dozent Name + password.
	
	
	if ($role == "Student") {
		$result = mysqli_query($db, 'SELECT matNr, password FROM JTstudent WHERE matNr='.$name.';');
		$row = mysqli_fetch_array($result);
		$correctLoginToken = hash("md5",$row['matNr'].$row['password']);		
		
		if($correctLoginToken == $loginToken) {
			$loggedIn = true;
		}
	} else if ($role == "Dozent") { 
		$result = mysqli_query($db, 'SELECT name, password, teacher_id FROM JTteacher WHERE name="'.$name.'";');
		$row = mysqli_fetch_array($result);
		$correctLoginToken = hash("md5",$row['name'].$row['password']);
		if($correctLoginToken == $loginToken) {
			$loggedIn = true;
			$myTeacherID = $row['teacher_id']; //is needed for update funktion further below
		}
	}
	
	if($loggedIn && $role == "Student") {
		$result = mysqli_query($db, 'SELECT matNr, name, email, studyProgram FROM JTstudent WHERE matNr='.$name.';');
		$row = mysqli_fetch_array($result);
		if (!isset($_POST['email']) && !isset($_POST['studyProgram'])) {	//Prevents update from taking old values instead of the new ones
			$_POST['email'] 		= $row['email'];
			$_POST['studyProgram'] 	= $row['studyProgram'];
		}
		$_POST['matNr'] 		= $row['matNr'];
		$_POST['name'] 			= $row['name'];

		
	} else 	if($loggedIn && $role == "Dozent") {
		$result = mysqli_query($db, 'SELECT name, email, iban FROM JTteacher WHERE name="'.$name.'";'); //Prevents update from taking old values instead of the new ones
		$row = mysqli_fetch_array($result);
		if (!isset($_POST['email']) && !isset($_POST['iban'])) {
			$_POST['email'] 		= $row['email'];
			$_POST['iban'] 			= $row['iban'];
		}
		$_POST['name'] 			= $row['name'];

	}

?>
<!DOCTYPE html>
<html>
<head>
	<!-- Bootstrap -->	
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
	
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
	<title>Datenerfassung</title>
</head>
<body style="padding: 50px;" >
	<input type="button" name="btnLogin" value="LogIn" onClick="self.location.href='login.php'" style="float:right;" class="btn btn-primary" />
	
	
	<!-- Just the head, Disclaimer and "Logged in as" -->
	<?php if($loggedIn) { ?>
		<p style="float:right;" >Eingeloggt als: <?php echo $name; ?> </p>
	<?php } ?>
	
	<h1>Datenerfassung - Testseite: Keine richtigen Daten eingeben!</h1>

	<?php 
		if (!isset($_POST['role']) && !isset($_COOKIE['role'])) {
	?>
	<h3>W&auml;hle deine Rolle</h3>
	<!-- form für die Rollenauswahl -->

		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<table>
				<tr>
					<td>Rolle</td>
					<td>
						<input type="radio" name="role" value="Student" >Student</input><br>
						<input type="radio" name="role" value="Dozent" >Dozent</input>
					</td>
				</tr>
				<tr><td><input type="submit" value="OK" class="btn btn-primary" </td></tr>
			</table>
		</form>
	<?php 
		} else { //Rolle ist bereits gesetzt
			
			if($_POST['role']=="Dozent"||clean($_COOKIE['role'])=="Dozent") {
	?>
	
	<h3>Registrierung f&uuml;r Dozenten</h3>
	<!-- form für den Dozenten -->

		<?php
			$error=""; //for every occuring error, error adds up a new line explaining the mistake with .=
			$ibanpattern = '/^[A-Z]{2}([\s]*[0-9]){20}$/';
				
			//checking whether all variables are set & For Iban: Wether correct format used
			if(	isset($_POST['name']) &&
				isset($_POST['password']) &&
				isset($_POST['email']) &&
				isset($_POST['iban']) &&		
				isset($_POST['emailCheck']) &&
				isset($_POST['passwordCheck']) &&
				(
					isset($_POST['register']) ||
					isset($_POST['update'])
				)) {
				
					$name= $_POST['name'];
					$password = $_POST['password'];
					$email = $_POST['email'];		
					$iban = $_POST['iban'];		
					$emailCheck = $_POST['emailCheck'];
					$passwordCheck = $_POST['passwordCheck'];
					
					//validate the user input
					require '../DB/connection.inc.php';
					
					$error .= mailcheckEqual($email, $emailCheck);
					$error .= mailcheckPattern($email);

					//iban correct
					$ibancheck = preg_match($ibanpattern, $_POST['iban'])? "": $error .= 'Bitte geben sie eine gültige IBAN ein. <br/>';

					//password length
					if(strlen($password)<8){
						$error .= 'Ihr Passwort ist zu kurz.</br>';	//password < 8 letters?
					}
					//same password twice
					if($password!=$passwordCheck){
						$error .= 'Ihre Passwörter stimmen nicht überein.<br/>';	//password = password?
					}
					//same email twice
					if($email!=$emailCheck){
						$error .= 'Ihre E-Mail Adressen stimmen nicht überein.<br/>';	//email = email?
					}
					
				} else {
					$error .= "Bitte fülle alle Felder aus!";
				}
			
			
			
			if($error==""){	//Kein Fehler aufgetreten ?
				 
				
				$doubleName  = ($loggedIn)? "": doubleName	($name, 	mysqli_query($db, 'SELECT name FROM JTteacher'));	//check double names in db
				if($loggedin){																								//check double mails mit Ausnahme der eigenen - wichtig für Update!
					$myEmail = mysqli_fetch_array(mysqli_query($db, 'SELECT email FROM JTteacher WHERE name = "'.$name.'";'));
					$doubleEmail = changemail	($email, 	mysqli_query($db, 'SELECT email FROM JTteacher'), $myEmail['email']);
				} 																				
				$doubleMail = DoubleEmail($email, mysqli_query($db, 'SELECT email FROM JTteacher'));							//check double email in db	
				
				$error .= $doubleName;
				$error .= $doubleEmail;
				
				$info= "";
				
				//put in database
				if ($_POST['register'] == "Registrieren" && $doubleName=="" && $doubleEmail=="") {	
					//register query
					$SQLquery = 'INSERT INTO JTteacher SET '.
						'name ="'. 				$_POST['name'] 					.'",'.
						'iban ="'. 				$_POST['iban']					.'",'.
						'email ="'. 			$_POST['email']					.'",'.
						'corruptionLevel ='.	rand(1, 10)						.', '.
						'password ="'.			hash("md5", $_POST['password'])	.'";';
						mysqli_query($db, $SQLquery);	
						$info = "Erfolgreich registriert, bitte begeben sie sich zum Login.";	
						
				} else if ($_POST['update'] == "Updaten" && $doubleEmail=="") {				//Name fest also kein erneute Check erforderlich
					//update query
					$SQLquery = 'UPDATE JTteacher SET '.
						'name ="'. 				$_POST['name'] 							.'",'.
						'iban ="'. 				$_POST['iban'] 							.'",'.
						'email ="'. 			$_POST['email']				   			.'",'.
						'password ="'.			hash("md5", $_POST['password'])			.'" '.
						'WHERE teacher_id = '. 	$myTeacherID							.'; '; //from logincheck above!
						mysqli_query($db, $SQLquery);	
						$info = "Daten erfolgreich upgedatet.";	
				}		

			}
		?>
		
		
		<!-- Create Dozententable containing: Name, password, email and IBAN-->
		
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
			<input type="hidden" name="role" value="<?php echo clean($_POST['role']); ?>" />
			<table>
				<tr>
					<td align="right">
						Name 
					</td>
					<td>
						<input name="name" type="text" size="40" maxlength="30" placeholder="Max"  <?php echo ($loggedIn)?'disabled':""; ?>  value="<?php echo clean($_POST['name']); ?>"> 
					</td>
				</tr>
				</tr>
					<td align="right">Passwort</td>
					<td>
						<input name="password" type="password" size="40" maxlength="32" placeholder ="password">
					</td>
				</tr>
				<tr>
					<td align="right">Passwort bestätigen</td>
					<td>
						<input name="passwordCheck" type="password" size="40" maxlength="32" placeholder ="password">
					</td>
				</tr>
				<tr>
					<td align="right">EMailadresse</td>
					<td>
						<input name="email" type="text" size="40" maxlength="30" placeholder="Max@Mustermann.de" value="<?php echo clean($_POST['email']); ?>"> 
					</td>
				</tr>
				<tr>
					<td align="right">EMail bestätigen</td>
					<td>
						<input name="emailCheck" type="email" size="40" placeholder="Max@Mustermann.de" maxlength="30"> 
					</td>
				</tr>
				<tr>
					<td align="right">IBAN</td>
					<td>
						<input name="iban" type="text" size="40" maxlength="32" placeholder="DEXX XXXX XXXX XXXX XXXX XX" value ="<?php echo clean($_POST['iban']); ?>">
					</td>
				</tr>
			</table>
			
			<!-- asks wether User is loggedin when directed to page: Yes: Update button No: Registerbutton-->
			<?php 
				if ($loggedIn) { 
			?>
				<input type="submit" name="update" value="Updaten" class="btn btn-primary" />
				<input type="button" name="backToOffer" value="Zur&uuml;ck zu den Angeboten" onClick="self.location.href='offers.php'" class="btn btn-primary"/>
			<?php 
				} else {
			?>	
				<input type="submit" name="register" value="Registrieren" class="btn btn-primary" />
			<?php 
				}
			?>	
		</form>
		<p><?php echo $error; ?></p>
		
		<!-- If not role = Dozent, Formular for Student-->
		
	<?php 
			} else if (clean($_POST['role'])=="Student"||clean($_COOKIE['role'])=="Student") { //Rolle = Student
	?>
	<h3>Datenerfassung f&uuml;r Studenten</h3>
		<?php 	
			//check if every input is set
				$error =	"";
			if(	!(isset($_POST['name']) 			&& $_POST['name'] != "" &&
				isset($_POST['matNr']) 			&& $_POST['matNr'] != "" &&
				isset($_POST['password']) 		&& $_POST['password'] != "" &&
				isset($_POST['passwordCheck']) 	&& $_POST['passwordCheck'] != "" &&
				isset($_POST['email']) 			&& $_POST['email'] != "" &&
				isset($_POST['emailCheck']) 	&& $_POST['emailCheck'] != "" &&
				isset($_POST['studyProgram']) 	&& $_POST['studyProgram'] != "")) {
					
				$error .= "F&uuml;lle bitte <b>alle</b> Felder aus!<br>";
			}
	
			$matNr = $_POST['matNr'];
			
			$email = $_POST['email'];
			$emailCheck = $_POST['emailCheck'];
			
			$password = $_POST['password'];
			$passwordCheck = $_POST['passwordCheck'];
			
			$error .= mailcheckEqual($email, $emailCheck);
			$error .= mailcheckPattern($email);
			
			//password length
			if(strlen($password)<8){
				$error .= 'Ihr Passwort ist zu kurz.</br>';	//password < 8 letters?
			}
			//same password twice
			if($password!=$passwordCheck){
				$error .= 'Ihre Passwörter stimmen nicht überein.<br/>';	//password = password?
			}
			
			//check all conditions and push into db
			if($error == "") {
				require '../DB/connection.inc.php';

				$doubleEmail = doubleEmail	($email, 	mysqli_query($db, 'SELECT email FROM JTstudent')); 	//check double email in db
				
				$error .= $doubleName;
				$temp = $doubleEmail;
				
				
				//Depending on Buttonname: Update / Insert into
				if (($_POST['register']) == "Registrieren" && $doubleEmail=="") { 
					//register query
					$SQLquery = 'INSERT INTO JTstudent SET '.
						'name ="'. 			addslashes($_POST['name']) 					.'",'.
						'matNr ="'. 		addslashes($_POST['matNr']) 				.'",'.
						'studyProgram ="'. 	addslashes($_POST['studyProgram']) 			.'",'.
						'email ="'. 		addslashes($_POST['email']) 				.'",'.
						'password ="'.		hash("md5", $_POST['password'])				.'";';
						mysqli_query($db, $SQLquery);	
						$info = "Erfolgreich registriert, bitte begeben sie sich zum Login.";	
						
				} else if ($_POST['update'] == "Updaten") {
					//update query
					$SQLquery = 'UPDATE JTstudent SET '.
						'name ="'. 			addslashes($_POST['name']) 					.'",'.
						'studyProgram ="'. 	addslashes($_POST['studyProgram']) 			.'",'.
						'email ="'. 		addslashes($_POST['email']) 				.'",'.
						'password ="'.		hash("md5", $_POST['password'])				.'" '.
						'WHERE matNr ='. 	addslashes($_POST['matNr']) 				.'; ';
						mysqli_query($db, $SQLquery);	
						$info = "Daten erfolgreich upgedatet.";
						$temp="";	
				} 

				$sqlfehler = mysqli_errno($db);
				if ($sqlfehler)
				{
					echo "<b>DB-Fehler: $sqlfehler ", mysqli_error($db), '</b>';
				}

			}
		?>
	<!-- form für den Student containing Name, MatrikelNr., studies, password, mail-->
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<input type="hidden" name="role" value="<?php echo $_POST['role']; ?>" />
			<table>				
				<tr>
					<td>Name</td>
					<td><input type="text" name="name" placeholder="Name" <?php echo ($loggedIn)?'disabled':""; ?> value="<?php echo $_POST['name']; ?>" /></td>
				</tr>
				<tr>
					<td>Matrikelnummer</td>
					<td><input type="number" name="matNr" step="1" min="0"  <?php echo ($loggedIn)?'disabled':""; ?>  value="<?php echo $_POST['matNr']; ?>" /></td>
				</tr>
				<tr>
					<td>Studiengang</td>
					<td>
						<select name="studyProgram" size="1" class="btn btn-link dropdown-toggle"/>
							<option <?php echo ($_POST['studyProgram']=="Physik") 				? "selected" : "" ?> >Physik</option>
							<option <?php echo ($_POST['studyProgram']=="Mathematik") 			? "selected" : "" ?> >Mathematik</option>
							<option <?php echo ($_POST['studyProgram']=="Nanoscience") 			? "selected" : "" ?> >Nanoscience</option>
							<option <?php echo ($_POST['studyProgram']=="Wirtschaftsinformatik")? "selected" : "" ?> >Wirtschaftsinformatik</option>
							<option <?php echo ($_POST['studyProgram']=="Medieninformatik") 	? "selected" : "" ?> >Medieninformatik</option>
							<option <?php echo ($_POST['studyProgram']=="Jura") 				? "selected" : "" ?> >Jura</option>
							<option <?php echo ($_POST['studyProgram']=="Germanistik") 			? "selected" : "" ?> >Germanistik</option>
							<option <?php echo ($_POST['studyProgram']=="Chemie") 				? "selected" : "" ?> >Chemie</option>
							<option <?php echo ($_POST['studyProgram']=="BWL") 					? "selected" : "" ?> >BWL</option>
						</select>
					</td>
			</tr>
				<tr>
					<td>Passwort</td>
					<td><input type="password" name="password" placeholder="password" /></td>
				</tr>
				<tr>
					<td>Passwort bestätigen</td>
					<td>
						<input name="passwordCheck" type="password"  placeholder ="password" />
					</td>
				</tr>
				<tr>
					<td>E-Mail</td>
					<td><input type="email" name="email" placeholder="Max@Mustermann.de" value="<?php echo $_POST['email']; ?>" /></td>
				</tr>
				<tr>
					<td>EMail bestätigen</td>
					<td>
						<input name="emailCheck" type="email" placeholder="Max@Mustermann.de" /> 
					</td>
				</tr>
			</table>
			<?php 
				if ($error != "") {
					echo $error;
				}
			
			
			//Same as for Dozent if loggedin Button = Update else Register
				if ($loggedIn) { 
			?>
				<input type="submit" name="update" value="Updaten" class="btn btn-primary" />
				<input type="button" name="backToOffer" value="Zur&uuml;ck zu den Angeboten" onClick="self.location.href='Webshop.php'" class="btn btn-primary"/>
			<?php 
				} else {
			?>	
				<input type="submit" name="register" value="Registrieren" class="btn btn-primary" />
			<?php 
				}
			?>	
		</form>
	<?php
		}
	}
	echo $info;
	?>
	
	<!-- Bootstrap -->	
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
</body>
</html>
