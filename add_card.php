<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	if (isset($_POST["card_name"])) {
		$cardName = htmlspecialchars($_POST["card_name"]);
	}
	else {
		echo("You must provide a card name.");
		return;
	}

	$cardName = str_replace(" ", "+", trim($cardName));
	
	/*
		https://scryfall.com/docs/api
	*/
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.scryfall.com/cards/search?unique=prints&q=" . $cardName);
	curl_Setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return data instead of displaying it
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		"User-Agent: MTGDatabaseApp/1.0",
		"Accept: application/json"
	]);

	$api_response = curl_exec($ch);

	if ($api_response === false) {
		echo("Error fetching API data: " . curl_error($ch));
	}
	else {
		$api_response = json_decode($api_response);
		// echo(print_r(json_encode($data, JSON_PRETTY_PRINT), true));

		$sql = "SELECT VERSION();";
		require_once("../pdo_connect.php");
		$query = $dbc->prepare(($sql));
		$query->execute();
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add a New Card</title>
    <meta charset="utf-8"> 
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="stylesheet" href="styles.css">
    <style>
        label {display: block; float: left; width: 10%;}
        input {display: block; width: 20%;}
        #submit {width: 10%; margin-top: 2rem; margin-left: 2%;}
        table {width: 100%; border-collapse: collapse; margin-top: 20px;}
        th, td {border: 1px solid #ddd; padding: 8px; text-align: center;}
        th {background-color: #f4f4f4;}
        .card-image {position: relative;}
        .card-image img {max-width: 100px; height: auto; transition: transform 0.3s ease;}
        .card-image:hover img {transform: scale(4); z-index: 10; position: relative;}
    </style>
</head>
<body>
    <h2>Add a New Card:</h2>
    <form action="add_card.php" method="POST">
        <input type="text" name="card_name" placeholder="Card Name" id="card_name_text">
        <input type="submit" name="submit" value="Search Cards" id="submit">
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST["card_name"])) {
            $cardName = htmlspecialchars($_POST["card_name"]);
        } else {
            echo "You must provide a card name.";
            return;
        }

        $cardName = str_replace(" ", "+", trim($cardName));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.scryfall.com/cards/search?unique=prints&q=" . $cardName);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "User-Agent: MTGDatabaseApp/1.0",
            "Accept: application/json"
        ]);

        $api_response = curl_exec($ch);

        if ($api_response === false) {
            echo "Error fetching API data: " . curl_error($ch);
        } else {
            $api_response = json_decode($api_response);

            if (!empty($api_response->data)) {
                echo "<table>";
                echo "<tr><th>Card Name</th><th>Image</th><th>Set</th><th>CardCurrentPrice</th><th>CardIndex</th></tr>";

                foreach ($api_response->data as $index => $card) {
                    $price = isset($card->prices->usd) ? "$" . htmlspecialchars($card->prices->usd) : "N/A";
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($card->name) . "</td>";
                    echo "<td class='card-image'><img src='" . htmlspecialchars($card->image_uris->normal) . "' alt='Card Image'></td>";
                    echo "<td>" . htmlspecialchars($card->set_name) . "</td>";
                    echo "<td>" . $price . "</td>";
                    echo "<td>" . ($index + 1) . "</td>"; // CardIndex based on iteration
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p>No cards found matching the search criteria.</p>";
            }
        }

        curl_close($ch);
    }
    ?>
</body>
</html>
