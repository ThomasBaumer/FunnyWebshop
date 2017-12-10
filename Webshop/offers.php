<?php
	//db connection
	require '../DB/connection.inc.php';
	
	//clean POST
	require 'regex.php';
	$_POST = cleanall($_POST);
	
	//check loginToken
	$accessGranted = false;
	//get cookie varibles (user input)
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
		//false logintoken redirects to the login page
		header('Location: login.php');
		die;
	}
	
	//logout button was pressed, all cookies will be destroyed, redirect to index.php
	if(isset($_POST['logout'])) {
		setcookie('name', null, 1);
		setcookie('role', null, 1);
		setcookie('loginToken', null, 1);
		header('Location: index.php');
		die;
	}
	
	//checks all buttons which could have been clicked and reads out the offer of the button
	$result = mysqli_query($db, 'SELECT offer_id, matNr, expire FROM JToffers WHERE (JToffers.status="open" OR JToffers.status="extended") AND teacher_id='.$myTeacherID.';');
	while($row = mysqli_fetch_array($result)) {
		//set the status by deciding which button was pressed
		$status = "";
		if (isset($_POST["accept".$row['offer_id']])) {
			$status = "accepted";
		} else if (isset($_POST["decline".$row['offer_id']])) {
			$status = "declined";
		} else if (isset($_POST["extend".$row['offer_id']])) {
			$status = "extended";
		}
		
		if($status) {
			//update db
			mysqli_query($db, 'UPDATE JToffers SET status="'.$status.'" WHERE offer_id='.$row['offer_id'].';');
			
			//update db when expiring date was extended
			if($status == "extended") {
				//forward the expire date 
				$expire = date_format(date_modify(date_create($row['expire']), '+3 day'), 'Y-m-d');
				mysqli_query($db, 'UPDATE JToffers SET expire="'.$expire.'" WHERE offer_id='.$row['offer_id'].';');
			}

			//send email (to student and teacher)
			require "emailHelper.php";
			sendAnswerToOfferEmail($row['offer_id']);
		}
	}
	
	//read out open offers db
	$activeOffers = "";
	$SQLquery = '
		SELECT offer_id, JTclass.name AS class, JToffers.amount, JToffers.grade, JTstudent.name, JTstudent.studyProgram, JToffers.expire
		FROM JToffers, JTclass, JTstudent
		WHERE 
		JToffers.MatNr = JTstudent.MatNr AND 
		JToffers.class_id = JTclass.class_id AND 
		JToffers.teacher_id	= '.$myTeacherID.' AND 
		(JToffers.status="open" OR JToffers.status="extended")';
			
	$result = mysqli_query($db, $SQLquery);
	while($row = mysqli_fetch_array($result)) {
		//write one row which cotains one offer its data, buttons for sending the offers in different colors
		$activeOffers .= 
			"<tr>
				<td style='padding: 10px;'>"	.$row['offer_id']		."</td>
				<td style='padding: 10px;'>"	.$row['class']			."</td>
				<td style='padding: 10px;'>"	.$row['amount']			." € f&uuml;r eine:
										<br>"	.$row['grade']			."</td>	
				<td style='padding: 10px;'>"	.$row['name']			."</td>									
				<td style='padding: 10px;'>"	.$row['studyProgram']	."</td>
				<td style='padding: 10px;'>L&auml;uft am ".date_format(date_create($row['expire']), 'd.m.Y')	." ab</td>
				<td style='padding: 10px;'>		
					<input type='submit' name='accept"	.$row['offer_id']."' class='btn btn-primary' 	value='Akzeptieren' />		<br>
					<input type='submit' name='decline"	.$row['offer_id']."' class='btn btn-danger' 	value='Ablehnen' />		<br>
					<input type='submit' name='extend"	.$row['offer_id']."' class='btn btn-warning' 	value='Verl&auml;ngern' />
				</td>
			</tr>";
	}
	
	
	//read out accepted offers db
	$acceptedOffers = "";
	$SQLquery = '
		SELECT offer_id, JTclass.name AS class, JToffers.amount, JToffers.grade, JTstudent.name, JTstudent.studyProgram
		FROM JToffers, JTclass, JTstudent
		WHERE 
		JToffers.MatNr = JTstudent.MatNr AND 
		JToffers.class_id = JTclass.class_id AND 
		JToffers.teacher_id	= '.$myTeacherID.' AND 
		JToffers.status="accepted"';
			
	$result = mysqli_query($db, $SQLquery);
	while($row = mysqli_fetch_array($result)) {
		//write one row which cotains one offer its data
		$acceptedOffers .= 
			"<tr>
				<td style='padding: 10px;'>"	.$row['offer_id']		."</td>
				<td style='padding: 10px;'>"	.$row['class']			."</td>
				<td style='padding: 10px;'>"	.$row['amount']			." € f&uuml;r eine:
										<br>"	.$row['grade']			."</td>	
				<td style='padding: 10px;'>"	.$row['name']			."</td>									
				<td style='padding: 10px;'>"	.$row['studyProgram']	."</td>
			</tr>";
	}
	
	//read out declined offers db
	$declinedOffers = "";
	$SQLquery = '
		SELECT offer_id, JTclass.name AS class, JToffers.amount, JToffers.grade, JTstudent.name, JTstudent.studyProgram
		FROM JToffers, JTclass, JTstudent
		WHERE 
		JToffers.MatNr = JTstudent.MatNr AND 
		JToffers.class_id = JTclass.class_id AND 
		JToffers.teacher_id	= '.$myTeacherID.' AND 
		JToffers.status="declined"';
			
	$result = mysqli_query($db, $SQLquery);
	while($row = mysqli_fetch_array($result)) {
		//write one row which cotains one offer its data
		$declinedOffers .= 
			"<tr>
				<td style='padding: 10px;'>"	.$row['offer_id']		."</td>
				<td style='padding: 10px;'>"	.$row['class']			."</td>
				<td style='padding: 10px;'>"	.$row['amount']			." € f&uuml;r eine:
										<br>"	.$row['grade']			."</td>	
				<td style='padding: 10px;'>"	.$row['name']			."</td>									
				<td style='padding: 10px;'>"	.$row['studyProgram']	."</td>
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
	
	<!-- form for changing the user data, logout or generating a new class -->
	<div style="float:right;">
		<!-- update data -->
		<form action="registrieren.php"  method="post">
			<input type="hidden" name="role" value="Dozent" />
			<input type="submit" name="changePersonalData" value="Deine Daten" class="btn btn-primary" />
		</form>
		<!-- create a new class -->
		<input type="button" name="createClass" value="Biete einen Kurs an" onClick="self.location.href='createClass.php'" class="btn btn-primary"/>
		<!-- logout -->
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<input type="submit" name="logout" value="LogOut" class="btn btn-primary" />
		</form>
	</div>
	<!-- display the current user -->
	<p style="float:right;" >Eingeloggt als: <?php echo $name; ?> </p>
	
	<h1>Angebote aus der Dozentensicht</h1>
	<!-- display the open offers from the students. If no offers are open some string will be shown -->
	<h3>Deine Angebote</h3>
	 <?php if($activeOffers!=""){?>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table style="padding: 5px;" border=1 >
			<col width="100" >
			<col width="300" >
			<col width="200" >
			<col width="100" >
			<col width="200" >
			<thead>
				<th>Angebotsnummer</th>
				<th>Kurs</th>
				<th>Angebot</th>
				<th>Angebotssteller</th>
				<th>Studiengang</th>
				<th>Verbleibende Zeit</th>
				<th>Aktion</th>
			</thead>
			<tbody>
				<?php echo $activeOffers; ?>
			</tbody>
		</table>
	</form>
	<?php
	} 
	else {echo 'Aktuell keine Angebote vorhanden. Noten - Billiger - Tipp: Prüfungen schwerer machen! ;- )';}
	?>
	
	<h3>Angenommene Auftr&auml;ge</h3>
	<!-- display the accepted offers from the students. If no offers are open some string will be shown -->
	<?php if($acceptedOffers!=""){?>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table style="padding: 5px;" border=1 >
			<col width="100" >
			<col width="300" >
			<col width="200" >
			<col width="100" >
			<col width="200" >
			<thead>
				<th>Angebotsnummer</th>
				<th>Kurs</th>
				<th>Angebot</th>
				<th>Angebotssteller</th>
				<th>Studiengang</th>
			</thead>
			<tbody>
				<?php echo $acceptedOffers; ?>
			</tbody>
		</table>
	</form>
	<?php
	}
	else {echo 'Bisher keine Angebote angenommen. Zeit wirds!';}
	?>
	
	<h3>Abgelehnte Auftr&auml;ge</h3>
	<!-- display the declined offers from the students. If no offers are open some string will be shown -->
	<?php 	if($declinedOffers!=""){ ?>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table style="padding: 5px;" border=1 >
			<col width="100" >
			<col width="300" >
			<col width="200" >
			<col width="100" >
			<col width="200" >
				<thead>
					<th>Angebotsnummer</th>
					<th>Kurs</th>
					<th>Angebot</th>
					<th>Angebotssteller</th>
					<th>Studiengang</th>
				</thead>
			<tbody>
				<?php echo $declinedOffers; ?>
			</tbody>
		</table>
	</form>
	<?php
	}
	else{echo 'Bisher keine Angebote abgelehnt. - Weiter so! Du machst alles richtig.';}
	?>
	
	<!-- Bootstrap -->	
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
</body>
</html>
