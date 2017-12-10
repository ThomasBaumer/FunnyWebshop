<?php 

	//clean POST
	require 'regex.php';
	$_POST = cleanall($_POST);

	//error response
	$response="";
	
	//case: Login button was pressed
	if($_POST['login']=="Einloggen") {
		
		//check whether all input type are set and contain a value
		if(	isset($_POST['name']) 		&& $_POST['name'] 		!= "" &&
			isset($_POST['password']) 	&& $_POST['password'] 	!= "" &&
			isset($_POST['role']) 		&& $_POST['role'] 		!= "") {
				
			//make the input secure
			$name = 	clean($_POST['name']);
			if(strlen($_POST['password'])==6) {  //password reset function
				$password = clean($_POST['password']);
			} else {
				$password = hash("md5", strip_tags($_POST['password']));
			}
			$role = 	clean($_POST['role']);	
			
			//db
			require '../DB/connection.inc.php';
			
			//login query for each role
			$SQLquery = "";
			if($_POST['role']=="Student") {
				$SQLquery = '
					SELECT matNr, password 
					FROM JTstudent
					WHERE matNr = '.$name.';';
					
			} else if ($_POST['role']=="Dozent") {
				$SQLquery = '
					SELECT name, password 
					FROM JTteacher
					WHERE name = "'.$name.'";';
			}
			$result = mysqli_query($db, $SQLquery);
			
			//if query returned a solution -> username was in the database and the role was correct
			if($result != null) {
				$row = mysqli_fetch_array($result);
				//both passwords are hashed... 
				if($row['password'] == $password) {
					$loginToken = hash("md5",$name.$password);
					
					//save role, name loginToken in COOKIES
					setcookie("loginToken", $loginToken);
					setcookie("name", $name);
					setcookie("role", $role);
					
					//forwarding (roles are considered)
					if($_POST['role']=="Student") {
						header('Location: Webshop.php');
						die;
					} else if ($_POST['role']=="Dozent") {
						header('Location: offers.php');
						die;
					}
				}
			} else {
				//name, password query was null
				$response = "Vermutlich hast du die falsche Rolle angegeben...";
			}
					
		} else {
			//login form was incomplete
			$response = "Die Logindaten waren unvollst&auml;ndig.";
		}
	}
	
	//password reset with email
	if($_POST['passwordReset']=="Passwort vergessen") {
		
		if(	isset($_POST['name']) && $_POST['name']	!= "" &&
			isset($_POST['role']) && $_POST['role']	!= "") {
				
			$name = 	addslashes(strip_tags($_POST['name']));
			$role = 	addslashes(strip_tags($_POST['role']));	
			
			//generating new password
			$resettedPassword = rand(100000, 999999);
			//insert password into database
			require '../DB/connection.inc.php';
			$SQLquery = "";
			if($role == "Student") {
				$SQLquery = 'UPDATE JTstudent SET password="'.$resettedPassword.'" WHERE matNr='.$name.';';
			} else if ($role == "Dozent") {
				$SQLquery = 'UPDATE JTteacher SET password="'.$resettedPassword.'" WHERE name="'.$name.'";';
			}
			if(!$SQLquery =="") {
				//put resetted password into the database
				mysqli_query($db, $SQLquery);

				//send mail with new password
				require "emailHelper.php";
				sendPasswordResetEmail($name, $role);
			}
		} else {
			//error response for password reset
			$response = "Es muss Name und Rolle für das Resetpasswort angegeben werden.";
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
	<!-- Bootstrap -->	
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
	
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
	<title>Login</title>
</head>
<body style="padding: 50px;" >
	<div style="float:right;" >
		<form action="registrieren.php" method="post">
			<?php setcookie('role', null, 1); ?>
			<input type="submit" name="changePersonalData" value="Registrieren" class="btn btn-primary" />
		</form>
	</div>
	<h1>Login</h1>
		<div align="center">
			<h3> Bitte geben sie Ihre Login Daten ein.</h3>
			<br/>
			<br/>
			<!-- Login Form -->
			<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
				<table border=1>
					<tr>
						<td>Username für Dozenten<br>Matrikelnummer für Studenten</td>
						<td>
							<input type="text" name="name" size="20" maxlength="20" value="<?php echo $_POST['name']; ?>" />
						</td>
					</tr>
					<tr>
						<td>Passwort </td>
						<td>
							<input type="password" name="password" size="20" maxlength="20" />
						</td>
					</tr>
					<tr>
						<td>Rolle</td>
						<td> 
							<select name="role" size=1 class="btn btn-link dropdown-toggle" /> 
								<option <?php echo ($_POST['role']=="Student")?"selected":""; ?>>Student</option> 
								<option <?php echo ($_POST['role']=="Dozent")?"selected":""; ?>>Dozent</option>
							</select>
						</td>
					</tr>
					<!-- From buttons -->
					<tr>
						<td>
							<input type="submit" name="login" value="Einloggen" class="btn btn-primary" /> 
						</td>
						<td>
							<input type="submit" name="passwordReset" value="Passwort vergessen" class="btn btn-primary" />
						</td>
					</tr>
					<tr>
						<td colspan="2">Noch kein Mitglied? <input type="button" name="register" value="Hier Registrieren" onClick="self.location.href='registrieren.php'" class="btn btn-primary"/>
						</td>
					</tr>
				</table>
			</form>
			<!-- Display area for the error responses -->
			<p><?php echo $response; ?></p>
		</div>
		
	<!-- Bootstrap -->	
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
</body>
</html>
