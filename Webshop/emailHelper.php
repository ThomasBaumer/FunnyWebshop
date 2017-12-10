<?php
	//Hier werden die ganzen email schreiben als funktion aufgesetzt
	
	function sendOfferEmail($amount, $grade, $status, $expire, $myMatNr, $class_id, $teacher_id) {
		require '../DB/connection.inc.php';
		
		//get information of the student
		$SQLquery = 'SELECT email, name, studyProgram FROM JTstudent WHERE matNr ='.$myMatNr.';';
		$mailResStudent = mysqli_fetch_array(mysqli_query($db, $SQLquery));
		//student information
		$studEmail = $mailResStudent['email'];
		$studName = $mailResStudent['name'];
		$studStudyProgram = $mailResStudent['studyProgram'];
		
		//get information of the class and teacher
		$SQLquery = '	SELECT email, JTclass.name AS class, JTteacher.name AS teacher 
						FROM JTclass, JTteacher 
						WHERE JTclass.teacher_id = JTteacher.teacher_id AND
						JTclass.class_id = '.$class_id.';';
		$mailResClass = mysqli_fetch_array(mysqli_query($db, $SQLquery));
		//teacher and class information
		$teacherEmail = $mailResClass['email'];
		$teacherName = $mailResClass['teacher'];
		$className = $mailResClass['class'];		
	
		
		//EMAIL, DASS ANGEBOT ERSTELLT WURDE an Dozi und Studi
		$Dmessage = "Sehr geehrte/r $teacherName, \r\n Sie haben soeben ein Angebot von $studentName über $amount mit Note $grade für die Vorlesung $className erhalten.\r\n\r\n Sie haben nun 3 Tage Zeit, um das Angebot \r\n\r\n - anzunehmen \r\n -abzulehnen \r\n Für weitere Überlegungen zu verlängern. Sollten Sie noch Fragen haben so melden Sie sich bitte unter wirantwortenehnicht@duliestnochweiter?omg \r\n\r\n Mit freundlichen Grüßen, \r\n dein Noten Billiger Team\r\n\r\n\r\n\r\n\r\n\r\n\r\n\r\n Und hier gehts zum login: http://xxxx.xxxx.xxxx.xxxx.php";
		$Smessage = "Sehr geehrte/r $studentName,\r\n $teacherName hat ihr Angebot über $amount mit Note $grade für die Vorlesung $className soeben erhalten. Er hat nun 3 Tage Zeit das Angebot zu bearbeiten. Anschließend erhalten sie eine erneute Email über den aktuellen Status. \r\n\r\n Sollten Sie noch Fragen haben so melden Sie sich bitte unter wirantwortenehnicht@duliestnochweiter?omg \r\n\r\n Vielen Dank für deinen Auftrag, mit freundlichen Grüßen, <br/> dein Noten Billiger Team";
		$subject = 'Angebot erstellt!';
		$header = "Content-type: text/plain; charset=utf-8";		
		mail($teacherEmail, $subject, $Dmessage, $header);
		mail($studEmail, $subject, $Smessage, $header);
		

	}
	
	
	function sendAnswerToOfferEmail($offer_id) {
		//Anfang für den Vorschlag der SQL query
		require '../DB/connection.inc.php';
		
		$SQLquery = '	SELECT JTteacher.email AS teacherEmail, JTteacher.name AS teacher, JTstudent.name AS student, JTstudent.email AS studentEmail, amount, grade, expire, status, JTclass.name AS class
						FROM JToffers, JTstudent, JTteacher, JTclass
						WHERE 
							offer_id = '.$offer_id.' AND
							JToffers.class_id = JTclass.class_id AND
							JToffers.teacher_id = JTteacher.teacher_id AND
							JToffers.matNr = JTstudent.matNr;';
		
		$mailRes = mysqli_fetch_array(mysqli_query($db, $SQLquery));
		
		//Speichern der Variablen, die für Mails gebraucht werden
		$teacherEmail = $mailRes['teacherEmail'];
		$teacherName = $mailRes['teacher'];
		$studentEmail = $mailRes['studentEmail'];
		$studentName = $mailRes['student'];
		$amount = $mailRes['amount'];
		$grade = $mailRes['grade'];
		$expire = $mailRes['expire'];
		$status = $mailRes['status'];
		$className = $mailRes['class'];
		switch($status){
			case 'declined': $sofferfiller= 'abgelehnt'; $dooferfiller = 'abgelehnt.'; $subject='Angebotsstatus: Abgelehnt'; break;
			case 'accepted': $dooferfiller = 'angenommen. Ihr Geld wir in den nächsten Tagen von dem Konto des Angeboterstellers abgebucht und Ihnen überwiesen'; $sofferfiller='angenommen. In den folgenden Tagen wird die von Ihnen angebotene Summe von Ihrem Konto abgebucht.'; $subject='Angebotsstatus: Angenommen'; break;
			case 'extended': $sofferfiller = 'um 3 Tage verlängert. Ihr Angebot läuft also am' .$expire.' ab.'; $dooferfiller = 'um 3 Tage verlängert. Ihr Angebot läuft also am' .$expire.' ab.'; $subject='Angebotsstatus: Verlängert'; break;
		}
		//accepted declined extended Nachricht an Dozent
		$Dmessage = "Sehr geehrte/r $teacherName, \r\n Sie haben soeben das Angebot von $studentName über $amount mit Note $grade für die Vorlesung $className $dooferfiller.\r\n\r\nSollten Sie noch Fragen haben so melden Sie sich bitte unter wirantwortenehnicht@duliestnochweiter?omg \r\n\r\n Mit freundlichen Grüßen, \r\n dein Nutten Billiger Team";
		//accepted declined extended Nachricht an Student
		$Smessage = "Sehr geehrte/r $studentName,\r\n $teacherName hat ihr Angebot über $amount mit Note $grade für die Vorlesung $className soeben $sofferfiller. \r\n\r\n Sollten Sie noch Fragen haben so melden Sie sich bitte unter wirantwortenehnicht@duliestnochweiter?omg \r\n\r\n Mit freundlichen Grüßen, <br/> dein Nutten Billiger Team";

		
		$header = "Content-type: text/plain; charset=utf-8";
		
		// Material um damit zu arbeiten: Mailadresse von Student, Lehrer / Deren Namen / gewünschte Note / Angebot / Ablaufsdatum / status / kursname
		mail($teacherEmail, $subject, $Dmessage, $header);
		mail($studentEmail, $subject, $Smessage, $header);
		
	}
	
	function sendPasswordResetEmail($name, $role) {
		//annotation for query
		//role for selecting the right table
		//student: name = matNr
		//teacher: name = name
		
		if($role == "Student") {
			require '../DB/connection.inc.php';
		
			$SQLquery = '	SELECT email, name, password 
							FROM JTstudent
							WHERE matNr='.$name.';'; //Einholen der wichtigen Daten aus der Datenbank die in der message s.u. verwurstet werden.
			
			$mailRes = mysqli_fetch_array(mysqli_query($db, $SQLquery)); 
			
			$studEmail = $mailRes['email'];
			$studPassword = $mailRes['password'];
			$studName = $mailRes['name'];

			$Smessage = "Hallo Looser, \r\nalso Passwortlooser natürlich. Fürchte dich nicht, mit unserem supersicherensiegfried Algorithmus haben wir dir eine neue Einloggpin erstellt.\r\nPin: $teacherPassword \r\n Bitte so schnell wie möglich updaten unter 'Deine Daten'. \r\n\r\nMit freundlichen Grüßen, \r\n dein Noten Billiger Team";
			$subject = 'Your way back into lov.. Noten billiger';
			$header = "Content-type: text/plain; charset=utf-8";		
			mail($studEmail, $subject, $Smessage, $header);
		
		} else if ($role == "Dozent"){
			require '../DB/connection.inc.php';
		
			$SQLquery = '	SELECT email, password
							FROM JTteacher
							WHERE name="'.$name.'";';//Einholen der wichtigen Daten aus der Datenbank die in der message s.u. verwurstet werden. (füR Dozent)
			
			$mailRes = mysqli_fetch_array(mysqli_query($db, $SQLquery));
			
			$teacherEmail = $mailRes['email'];
			$teacherPassword = $mailRes['password'];
			$teacherName = $name;
			
			
			$Smessage = "Hallo Looser, \r\nalso Passwortlooser natürlich. Fürchte dich nicht, mit unserem supersicherensiegfried Algorithmus haben wir dir eine neue Einloggpin erstellt.\r\nPin: $teacherPassword \r\n Bitte so schnell wie möglich updaten unter 'Deine Daten'. \r\n\r\nMit freundlichen Grüßen, \r\n dein Noten Billiger Team";
			$subject = 'Your way back into lov.. Noten billiger';
			$header = "Content-type: text/plain; charset=utf-8";		
			mail($teacherEmail, $subject, $Smessage, $header);

		}
	}
?>
