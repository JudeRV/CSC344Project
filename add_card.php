<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	if (isset($_POST["card_name"])) {
		$cardName = htmlspecialchars($_POST["card_name"]);
	}
	else {
		echo("You must provide a card name.");
		return;
	}
	echo($cardName);
	$cardName = str_replace(" ", "+", trim($cardName));
	echo($cardName);
	/*
		https://scryfall.com/docs/api
	*/
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.scryfall.com/cards/search?unique=prints&q=" . $cardName); // TODO: configure this url properly
	curl_Setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return data instead of displaying it
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		"User-Agent: MTGDatabaseApp/1.0",
		"Accept: application/json"
	]);

	$response = curl_exec($ch);

	if ($response === false) {
		echo("Error fetching API data: " . curl_error($ch));
	}
	else {
		$data = json_decode($response, true);
		echo(json_encode($data, JSON_PRETTY_PRINT));
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add a New Card</title>
	<meta charset ="utf-8"> 
	<link rel="icon" type="image/x-icon" href="/images/favicon.ico">
	<style>
		label {display: block; float:left; width:10%;}
		input {display: block; width: 20%}
		#submit {width: 10%; margin-top: 2rem; margin-left: 2%;}
	</style>
</head>
<body>
	<h2>Add a New Card:</h2>
	
	<form action="add_card.php" method="POST">
		<input type="text" name="card_name" value="Card Name" id = "card_name_text">
        <input type="submit" name="submit" value="Search Cards" id = "submit">
	</form>

    <script>

    </script>
</body>
</html>