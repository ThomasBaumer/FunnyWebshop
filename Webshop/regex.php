<?php 
	
	function mailcheckPattern($emailadresse) //Vergleicht EMailadresse mit 2. Eingabe + Untersucht eingegebene EMailadresse
		{
			$pattern = '/^[^0-9_]([.]{0,1}[a-zA-Z0-9_]+)*[@]([a-zA-Z0-9_]+[.]*)+[.][a-zA-Z0-9]{2,4}$/';
			
			if(preg_match($pattern, $emailadresse)==false){
				return 'Ungültige Email Adresse, bitte um erneute Eingabe<br>';
			}
			return "";
			
		}
		
	function mailcheckEqual($emailadresse,$emailadresse2) { //Kontrolliert ob die wiederholte EMailadresse der orginalen entspricht
		if($emailadresse!=$emailadresse2) {
			return 'Ihre Emailadressen stimmen nicht überein<br>';
		}
		return "";
	}
		
	
	function doubleName($name, $b){ 		//sucht nach Namen in Datenbank, ob der Name bereits vergeben ist
	
		while($namecheck = mysqli_fetch_array($b)){
			if($namecheck['name']== $name){
				return 'Dieser Name ist bereits vergeben, bitte um erneute Eingabe</br>';
			}
		}
		return "";
	}
				
	function doubleEmail($email, $b) {			// s.o. nur mit Mails
		while($emailaddr = mysqli_fetch_array($b)){
			if($emailaddr['email'] == $email){
				return 'Diese EMail Adresse wird bereits benutzt</br>';
			}
		}
		return "";
	}	
	
	function changemail($email, $b, $myEmail) {		//s.o. nur im Falle eines Userupdates - Bei gleichlassen der Mailadresse träte sonst ein Fehler auf
		while($emailaddr = mysqli_fetch_array($b)){
			if($emailaddr['email'] == $email && $myEmail != $emailaddr['email']){
				return 'Diese EMail Adresse wird bereits benutzt</br>';
			}
		}
		return "";
	}	

	function clean($string){		//Um injektions vorzubeugen
		return htmlspecialchars(addslashes(strip_tags($string)));
	}
	
	function cleanall($array) {	//Hallo $_POST[] du siehst heute aber sauber aus!
		foreach ($array as &$value) {
			$value = clean($value);
		}
		return $array;
	}
?>
			
		
