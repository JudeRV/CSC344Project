<?php
require_once("../pdo_connect.php");
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
        input, select {display: block; width: 20%;}
        #submit {width: 10%; margin-top: 2rem; margin-left: 2%;}
        table {width: 100%; border-collapse: collapse; margin-top: 20px;}
        th, td {border: 1px solid #ddd; padding: 8px; text-align: center;}
        th {background-color: #f4f4f4;}
        .card-image img {max-width: 100px; height: auto; transition: transform 0.3s ease; z-index: 10;}
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
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["card_name"])) {
        $cardName = htmlspecialchars($_POST["card_name"]);
        $cardName = str_replace(" ", "+", trim($cardName));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.scryfall.com/cards/search?unique=prints&q=" . $cardName);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "User-Agent: MTGDatabaseApp/1.0",
            "Accept: application/json"
        ]);

        $api_response = curl_exec($ch);
        curl_close($ch);

        if ($api_response === false) {
            echo "Error fetching API data.";
        } else {
            $api_response = json_decode($api_response);

            if (!empty($api_response->data)) {
                echo <<<EOD
				<table>
					<thead>
						<tr>
							<th>Card Name</th>
							<th>Image</th>
							<th>Set</th>
							<th>CardCurrentPrice</th>
							<th>CardIndex</th>
						</tr>
					</thead>
					<tbody>
				EOD;

                foreach ($api_response->data as $index => $card) {
                    $price = isset($card->prices->usd) ? "$" . htmlspecialchars($card->prices->usd) : "N/A";
                    $set = htmlspecialchars($card->set_name);
                    $cardIndex = $index + 1;

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($card->name) . "</td>";
                    echo "<td class='card-image'><img src='" . htmlspecialchars($card->image_uris->normal) . "' alt='Card Image'></td>";
                    echo "<td>" . $set . "</td>";
                    echo "<td>" . $price . "</td>";
                    echo "<td>" . $cardIndex . "</td>";
					// I haven't been able to get this adding to deck work
                    echo "<td>
					
                    <form action='add_card.php' method='POST'>
                        <input type='hidden' name='CardSetID' value='" . htmlspecialchars($card->set) . "'>
                        <input type='hidden' name='CardIndex' value='" . $cardIndex . "'>
                        <input type='text' name='DeckName' placeholder='Deck Name' required>
                        <input type='text' name='DeckDescription' placeholder='Deck Description' required>
                        <button type='submit'>Add to Deck</button>
                    </form>
                    </td>";
                    echo "</tr>";
                }

                echo <<<EOD
					</tbody>
				</table>
				EOD;
            } else {
                echo "<p>No cards found matching the search criteria.</p>";
            }
        }
    }
    ?>
</body>
</html>
