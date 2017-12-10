<?php
	//db connection
	require '../DB/connection.inc.php';
	
	//clean POST
	require 'regex.php';
	$_POST = cleanall($_POST);
	
	//check loginToken
	$accessGranted = false;
	//get session varibles (user input)
	$loginToken = clean($_COOKIE['loginToken']);
	$name = clean($_COOKIE['name']);
	$role = clean($_COOKIE['role']);
	//get correct login token (correct logintoken is calculated with the trusted data from the database).
	$result = mysqli_query($db, 'SELECT name, password, teacher_id FROM JTteacher WHERE name="'.$name.'";');
	$row = mysqli_fetch_array($result);
	$correctLoginToken = hash("md5",$row['name'].$row['password']);
	//compare user input and db entry 
	if($correctLoginToken == $loginToken && $role=="Dozent") {
		$accessGranted = true;
		$myTeacherID = $row['teacher_id'];
	} else {
		//redirect to login if no access could be granted
		header('Location: login.php');
		die;
	}
	
	//logout button was pressed
	if(isset($_POST['logout'])) {
		//destroy the cookies
		setcookie('name', null, 1);
		setcookie('role', null, 1);
		setcookie('loginToken', null, 1);
		header('Location: index.php');
		die;
	}	
	
	//checking the input class (POST) with regex (classpattern)
	$classpattern = '/^[^0-9_]([a-zA-Z0-9_\-\,\/\:]){4,}/';
	if(isset($_POST['createclass'])) {
		if(preg_match($classpattern, clean($_POST['class_name']))==false){
			//invalid format
			echo 'Bitte um eines Kurses. Mindestens 5 Zeichen (Am Anfang keine Zahl, nur Klein-, Großbuchstaben, Zahlen, ,_-:/!';
		}
		else{
			//valid class name by pattern
			$className = clean($_POST['class_name']);
			$SQLquery = 'INSERT INTO JTclass SET name="'.$className.'", teacher_id='.$myTeacherID.';';
			mysqli_query($db, $SQLquery);
		}
	}
	
		
	//generate table for already registered courses
	$courseTable = "<table border=1 ><thead><tr><th>Kurse</th></tr></thead><tbody>";
	//coursesRegistered neccessary for the creating button below 
	$coursesRegistered = false;
	$result = mysqli_query($db, 'SELECT name FROM JTclass WHERE teacher_id='.$myTeacherID.';');
	while($row = mysqli_fetch_array($result)) { 
		$coursesRegistered = true;
		//parse the class names to table rows
		$courseTable .= "<tr><td>".$row['name']."</td></tr>";
	}
	$courseTable .= "</tbody></table>";	
	
	
?>
<!DOCTYPE html>
<html>
<head>
	<!-- Bootstrap -->	
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
	
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
   
	<title>Kursregistrierung</title>
</head>
<body style="padding: 50px;" >
	
	<!-- Form for changing user data or logout-->
	<div style="float:right;" >
		<!-- change data -->
		<form action="registrieren.php" method="post">
			<input type="hidden" name="role" value="Student" />
			<input type="submit" name="changePersonalData" value="Deine Daten" class="btn btn-primary" />
		</form>
		<!-- back to offers button -->
		<input type="button" name="createClass" value="Zur&uuml;ck zur &Uuml;bersicht" onClick="self.location.href='offers.php'" class="btn btn-primary"/>
		<!-- logout -->
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<input type="submit" name="logout" value="LogOut" class="btn btn-primary" />
		</form>
	</div>
	<!-- show current user -->
	<p style="float:right;" >Eingeloggt als: <?php echo $name; ?> </p>
	<h1>Registriere einen Kurs</h1>
	
	<!-- registering form for a class, textarea is not connected to database --> 
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<table>
		<tr> <th>Name des Kurses</th> <td> <input type="string" name="class_name" size = 40 maxlength = 50> </td></tr>
		<tr> <th>Bemerkung zum Kurs (Alpha)</th><td><textarea rows="5" cols="40">Z.b. 300€ pro Notenschritt</textarea></td></tr>
		<tr> <td><input type="submit" name="createclass" class="btn btn-primary" value="<?php echo ($coursesRegistered)? 'Weiteren Kurs einstellen':'Kurs erstellen';?>" colspan="2"></td></tr>
	</table>
	</form>
	
	<br>
	<h3>Deine angebotenen Kurse</h3>
	<!-- print out the classes which the teacher has registerd --> 
	<?php echo ($coursesRegistered)?$courseTable:"Du bietest noch keine Kurse an!"; ?>
	
	<!-- Bootstrap -->	
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
</body>
</html>
