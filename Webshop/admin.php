<?php 
	//db connection
	require '../DB/connection.inc.php';
	
	//read out student db
	$students = "";
	$SQLquery = 'SELECT * FROM JTstudent';
	$result = mysqli_query($db, $SQLquery);
	while($row = mysqli_fetch_array($result)) {
		$students .= 
			"<tr>
				<td>".$row['matNr']			."</td>
				<td>".$row['name']			."</td>
				<td>".$row['password']		."</td>
				<td>".$row['email']			."</td>
				<td>".$row['studyProgram']	."</td>
			</tr>";
	}
	
	//read out teacher db
	$teachers = "";
	$SQLquery = 'SELECT * FROM JTteacher';
	$result = mysqli_query($db, $SQLquery);
	while($row = mysqli_fetch_array($result)) {
		$teachers .= 
			"<tr>
				<td>".$row['teacher_id']		."</td>
				<td>".$row['name']				."</td>
				<td>".$row['password']			."</td>
				<td>".$row['email']				."</td>
				<td>".$row['iban']				."</td>
				<td>".$row['corruptionLevel']	."</td>
			</tr>";
	}
	
	//read out class db
	$classes = "";
	$SQLquery = 'SELECT * FROM JTclass';
	$result = mysqli_query($db, $SQLquery);
	while($row = mysqli_fetch_array($result)) {
		$classes .= 
			"<tr>
				<td>".$row['class_id']		."</td>
				<td>".$row['name']			."</td>
				<td>".$row['teacher_id']	."</td>
			</tr>";
	}
	
	//read out offers db
	$offers = "";
	$SQLquery = 'SELECT * FROM JToffers';
	$result = mysqli_query($db, $SQLquery);
	while($row = mysqli_fetch_array($result)) {
		$offers .= 
			"<tr>
				<td>".$row['offer_id']	."</td>
				<td>".$row['amount']	."</td>
				<td>".$row['grade']		."</td>
				<td>".$row['status']	."</td>	
				<td>".$row['expire']	."</td>									
				<td>".$row['matNr']		."</td>
				<td>".$row['class_id']	."</td>
				<td>".$row['teacher_id']."</td>
			</tr>";
	}
?>

<html>
<head>
	<!-- Bootstrap -->	
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
	
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
   
	<title>Datenanzeige</title>
</head>

<!-- Selbsterklärend: Die Tabellen die oben ausgelesen wurden, werden nun in Tables in html ausgegeben. 
Dafür wurden die Tables schon in php vorbereitet unter $offer $teacher etc.-->


<body style="padding: 50px;" >
	<h1>Anzeige der Daten aus der Studentenrelation</h1>	
		<table border="1">						
			<thead>
				<tr>
					<th>matNr</th>
					<th>name</th>
					<th>password</th>
					<th>email</th>
					<th>studyProgram</th>
				</tr>
			</thead>
			<tbody>
				<?php echo $students; ?>
			</tbody>
		</table>
		
	<h1>Anzeige der Daten aus der Dozentenrelation</h1>
		<table border="1">
			<thead>
				<tr>				
					<th>teacher_id</th>
					<th>name</th>
					<th>password</th>
					<th>email</th>
					<th>iban</th>
					<th>corruptionLevel</th>
			</thead>
			<tbody>
				<?php echo $teachers; ?>
			</tbody>
		</table>
		
	<h1>Anzeige der Daten aus der Kursrelation</h1>
		<table border="1">
			<thead>
				<tr>				
					<th>class_id</th>
					<th>name</th>
					<th>teacher_id</th>
			</thead>
			<tbody>
				<?php echo $classes; ?>
			</tbody>
		</table>
		
	<h1>Anzeige der Daten aus der Angebotsrelation</h1>
		<table border="1">
			<thead>
				<tr>
					<th>offer_id</th>
					<th>amount</th>
					<th>grade</th>
					<th>status</th>
					<th>expire</th>
					<th>matNr</th>
					<th>class_id</th>
					<th>teacher_id</th>	
			</thead>
			<tbody>
				<?php echo $offers; ?>
			</tbody>
		</table
		
	<!-- Bootstrap -->	
	<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
</body>
</html>
