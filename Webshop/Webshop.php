<?php
	//db connection
	require '../DB/connection.inc.php';
	require 'regex.php';
	cleanall($_POST);
	
	//check loginToken
	$accessGranted = false;
	//get session varibles (user input)
	$loginToken = clean($_COOKIE['loginToken']);
	$name = clean($_COOKIE['name']);
	$role = clean($_COOKIE['role']);
	//get correct login token (correct logintoken is calculated with the trusted data from the database).
	$result = mysqli_query($db, 'SELECT matNr, password FROM JTstudent WHERE matNr='.$name.';');
	$row = mysqli_fetch_array($result);
	$correctLoginToken = hash("md5",$row['matNr'].$row['password']);
	//compare user input and db entry
	if($correctLoginToken == $loginToken && $role=="Student") {
		$accessGranted = true;
		$myMatNr = $name;
	} else {
		//redirect to login if the logintoken was incorrect
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
		
	//check all button if an offer was made
	$result = mysqli_query($db, 'SELECT class_id, teacher_id FROM JTclass;');
	while($row = mysqli_fetch_array($result)) {
		
		if (isset($_POST["offer".$row['class_id']])) {

			//gather the offer information
			$amount = $_POST["amount".$row['class_id']];
			$grade = $_POST["grade".$row['class_id']];
			$status = "open"; 
			$expire = date_format(date_modify(date_create(null), '+1 month'), 'Y-m-d'); //today in one month
			$class_id = $row['class_id'];
			$teacher_id = $row['teacher_id'];
			$myMatNr = $myMatNr;
				
			//build the query for inserting the offer into the db 
			$SQLquery='INSERT INTO JToffers SET 
							amount='.$amount.', 
							grade='.$grade.', 
							status="'.$status.'", 
							expire="'.$expire.'", 
							matNr='.$myMatNr.', 
							class_id='.$class_id.', 
							teacher_id='.$teacher_id.';';

			mysqli_query($db, $SQLquery);
			
			//send email with the information about the made offer to student and teacher
			require "emailHelper.php";
			sendOfferEmail($amount, $grade, $status, $expire, $myMatNr, $class_id, $teacher_id);
		}
	}
	
	//get filter options (teacher names) and parse it to the dropdown options, takes care of the previous selected option
	$teacherOptions = "";
	$result = mysqli_query($db, 'SELECT name FROM JTteacher;');
	while($row = mysqli_fetch_array($result)) {	
		$teacherOptions .= "<option";
		if($_POST['selectedTeacher']==$row['name']) {
			$teacherOptions .= " selected ";
		}
		$teacherOptions .= ">".$row['name']."</option>";
	}
	
	//read out possibleClasses or the selected ones
	$possibleClasses = "";
	//case all teachers are selected or no selection at all
	if(!isset($_POST['selectedTeacher']) || $_POST['selectedTeacher'] == "Alle") {
		$SQLquery = '
			SELECT JTclass.name AS class, JTteacher.name AS teacher, corruptionLevel, JTteacher.teacher_id, JTclass.class_id 
			FROM JTteacher, JTclass 
			WHERE JTclass.teacher_id = JTteacher.teacher_id;';
	} else { //a selection was applied
		$selectedTeacher = addslashes($_POST['selectedTeacher']);
		$SQLquery = '
			SELECT JTclass.name AS class, JTteacher.name AS teacher, corruptionLevel, JTteacher.teacher_id, JTclass.class_id 
			FROM JTteacher, JTclass 
			WHERE JTclass.teacher_id = JTteacher.teacher_id AND JTteacher.name="'.$selectedTeacher.'";';
	}
	//perform the query
	$result = mysqli_query($db, $SQLquery);
	while($row = mysqli_fetch_array($result)) {
		
		//radio buttons are disabled and showing in graphic way the corruption level
		$radios = "";
		for($i = 1; $i <= 10; $i ++) {
			if($row['corruptionLevel'] >= $i) {
				$radios .= '<input type="radio" checked="checked" disabled />';
			} else {
				$radios .= '<input type="radio" disabled />';
			}
		}
		
		//build the html code for the offer making with its button and data
		$possibleClasses .= 
			"<tr>
				<td style='padding: 10px;'>"	.$row['class']			."</td>
				<td style='padding: 10px;'>"	.$row['teacher']		."</td>
				<td style='padding: 10px;'>"	.$radios				."</td>
				<td>
					Ich biete <input type='number' name='amount".$row['class_id']."' min='0' step='10' value='100' style='width: 5em;' /> € 
					f&uuml;r die Note 
					<select size='1' name='grade".$row['class_id']."' class='btn btn-link dropdown-toggle' >
						<option selected>1.0</option>
						<option>1.3</option>
						<option>1.7</option>
						<option>2.0</option>
						<option>2.3</option>
						<option>2.7</option>
						<option>3.0</option>
						<option>3.3</option>
						<option>3.7</option>
						<option>4.0</option>
					</select>
				</td>								
				<td style='padding: 10px;'>		
					<input type='submit' name='offer".$row['class_id']."' class='btn btn-primary' value='Anbieten' />
				</td>
			</tr>";
	}
	
	//display the already sent offers 
	//build the query
	$sentOffers = "";
	$SQLquery = '
		SELECT JTclass.name AS class, JTteacher.name AS teacher, corruptionLevel, amount, grade, status, expire
		FROM JToffers, JTclass, JTteacher
		WHERE 
		JToffers.class_id = JTclass.class_id AND
		JToffers.teacher_id = JTteacher.teacher_id AND
		JToffers.MatNr = '."$myMatNr".' AND
		(status = "open" OR status = "extended");';
			
	$result = mysqli_query($db, $SQLquery);
	while($row = mysqli_fetch_array($result)) {
		//radio buttons for visualizing the corruption level
		$radios = "";
		for($i = 1; $i <= 10; $i ++) {
			if($row['corruptionLevel'] >= $i) {
				$radios .= '<input type="radio" checked="checked" disabled />';
			} else {
				$radios .= '<input type="radio" disabled />';
			}
		}
		
		//build one offer in HTML
		$sentOffers .= 
			"<tr>
				<td style='padding: 10px;'>"	.$row['class']			."</td>
				<td style='padding: 10px;'>"	.$row['teacher']		."</td>
				<td style='padding: 10px;'>"	.$radios				."</td>					
				<td style='padding: 10px;'>"	.$row['amount']			." € f&uuml;r eine:
										<br>"	.$row['grade']			."</td>	
				<td style='padding: 10px;'>"	.$row['status']			."</td>							
				<td style='padding: 10px;'>L&auml;uft am ".date_format(date_create($row['expire']), 'd.m.Y')	." ab</td>
			</tr>";
	}
	
	
	//display the already accepted offers 
	//build the query
	$acceptedOffers = "";
	$SQLquery = '
		SELECT JTclass.name AS class, JTteacher.name AS teacher, corruptionLevel, amount, grade, status
		FROM JToffers, JTclass, JTteacher
		WHERE 
		JToffers.class_id = JTclass.class_id AND
		JToffers.teacher_id = JTteacher.teacher_id AND
		JToffers.MatNr = '."$myMatNr".' AND
		status = "accepted";';
			
	$result = mysqli_query($db, $SQLquery);
	while($row = mysqli_fetch_array($result)) {
		//radio buttons for visualizing the corruption level
		$radios = "";
		for($i = 1; $i <= 10; $i ++) {
			if($row['corruptionLevel'] >= $i) {
				$radios .= '<input type="radio" checked="checked" disabled />';
			} else {
				$radios .= '<input type="radio" disabled />';
			}
		}
		//build one offer in HTML
		$acceptedOffers .= 
			"<tr>
				<td style='padding: 10px;'>"	.$row['class']			."</td>
				<td style='padding: 10px;'>"	.$row['teacher']		."</td>
				<td style='padding: 10px;'>"	.$radios				."</td>					
				<td style='padding: 10px;'>"	.$row['amount']			." € f&uuml;r eine:
										<br>"	.$row['grade']			."</td>	
				<td style='padding: 10px;'>"	.$row['status']			."</td>							
			</tr>";
	}
	
	
	//display the already declined offers 
	//build the query
	$declinedOffers = "";
	$SQLquery = '
		SELECT JTclass.name AS class, JTteacher.name AS teacher, corruptionLevel, amount, grade, status
		FROM JToffers, JTclass, JTteacher
		WHERE 
		JToffers.class_id = JTclass.class_id AND
		JToffers.teacher_id = JTteacher.teacher_id AND
		JToffers.MatNr = '."$myMatNr".' AND
		status = "declined";';
			
	$result = mysqli_query($db, $SQLquery);
	while($row = mysqli_fetch_array($result)) {
		//radio buttons for visualizing the corruption level
		$radios = "";
		for($i = 1; $i <= 10; $i ++) {
			if($row['corruptionLevel'] >= $i) {
				$radios .= '<input type="radio" checked="checked" disabled />';
			} else {
				$radios .= '<input type="radio" disabled />';
			}
		}
		
		//build one offer in HTML
		$declinedOffers .= 
			"<tr>
				<td style='padding: 10px;'>"	.$row['class']			."</td>
				<td style='padding: 10px;'>"	.$row['teacher']		."</td>
				<td style='padding: 10px;'>"	.$radios				."</td>					
				<td style='padding: 10px;'>"	.$row['amount']			." € f&uuml;r eine:
										<br>"	.$row['grade']			."</td>	
				<td style='padding: 10px;'>"	.$row['status']			."</td>							
			</tr>";
	}
?>
<!DOCTYPE html>
<html>
<head>
	<!-- Bootstrap -->	
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
	
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
   
	<title>Deine Angebote</title>
</head>
<body style="padding: 50px;" >
	
	<!-- Form for changing user data or logout-->
	<div style="float:right;" >
		<!-- change data -->
		<form action="registrieren.php" method="post">
			<input type="hidden" name="role" value="Student" />
			<input type="submit" name="changePersonalData" value="Deine Daten" class="btn btn-primary" />
		</form>
		<!-- logout -->
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<input type="submit" name="logout" value="LogOut" class="btn btn-primary" />
		</form>
	</div>
	<!-- show current user -->
	<p style="float:right;" >Eingeloggt mit Matrikelnummer: <?php echo $name; ?> </p>
	<h1>Angebot erstellen</h1>
	<h3>M&ouml;gliche Angebote</h3>
	
	<!-- Form for selecting a special teacher -->
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		Filter
		<select size='1' name='selectedTeacher' class='btn btn-link dropdown-toggle' >
			<option <?php echo ($_POST['selectedTeacher']=="Alle"||!isset($_POST['selectedTeacher']))?"selected":""; ?>>Alle</option>
			<?php echo $teacherOptions; ?>
		</select>
		<input type="submit" name="selectTeacher" value="Anwenden" class="btn btn-primary" />
	</form><br>
	
	<!-- Form for making an offer -->
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table style="padding: 5px;" border=1 >
			<col width="300" >
			<col width="100" >
			<col width="200" >
			<col width="200" >
			<col width="100" >
				<thead>
					<th>Kurs</th>
					<th>Dozent</th>
					<th>Korruptionslevel</th>
					<th>Dein Angebot</th>
					<th>Aktion</th>
				</thead>
			<tbody>
				<?php echo $possibleClasses; ?>
			</tbody>
		</table>
	</form>
	
	<br>
	<br>
	<br>
	
	<h1>Deine Angebote</h1>
	<!-- Shows already sent offers (open or extended) -->
	<h3>Gesendete Angebote</h3>
	 <?php if($sentOffers!=""){?>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table style="padding: 5px;" border=1 >
			<col width="300" >
			<col width="100" >
			<col width="200" >
			<col width="200" >
			<col width="100" >
			<col width="150" >
				<thead>
					<th>Kurs</th>
					<th>Dozent</th>
					<th>Korruptionslevel</th>
					<th>Angebot</th>
					<th>Status</th>
					<th>Ablaufdatum</th>
				</thead>
			<tbody>
				<?php echo $sentOffers; ?>
			</tbody>
		</table>
	</form>
	<?php
	} 
	else {echo 'Bist du einfach so ein Genie, das einfach alles weiß ? Hab ich auch nicht gedacht..';}
	?>
	
	<br>
	<h3>Akzeptierte Angebote</h3>
	<!-- Shows already accepted offers (accepted) -->
	<?php if($acceptedOffers!=""){?>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table style="padding: 5px;" border=1 >
			<col width="300" >
			<col width="100" >
			<col width="200" >
			<col width="200" >
			<col width="100" >
				<thead>
					<th>Kurs</th>
					<th>Dozent</th>
					<th>Korruptionslevel</th>
					<th>Angebot</th>
					<th>Status</th>
				</thead>
			<tbody>
				<?php echo $acceptedOffers; ?>
			</tbody>
		</table>
	</form>
	<?php
	}
	else {echo 'Oy oy oy....';}
	?>
	
	<br>
	<h3>Abgelehnte Angebote</h3>
	<!-- Shows already declined offers (declined) -->
	<?php 	if($declinedOffers!=""){ ?>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table style="padding: 5px;" border=1 >
			<col width="300" >
			<col width="100" >
			<col width="200" >
			<col width="200" >
			<col width="100" >
				<thead>
					<th>Kurs</th>
					<th>Dozent</th>
					<th>Korruptionslevel</th>
					<th>Angebot</th>
					<th>Status</th>
				</thead>
			<tbody>
				<?php echo $declinedOffers; ?>
			</tbody>
		</table>
	</form>
	<?php
	}
	else{echo 'Bisher keine Angebote abgelehnt. - Du kennst deine Dozenten gut und weißt manchmal kostets einfach.. Weiter so!';}
	?>

	<!-- Bootstrap -->	
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
</body>
</html>
