<?php
require_once("../pdo_connect.php");

function validateData($data) {
    if (isset($data)) {
        return $data;
    }
    else return null;
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
        <input type="text" name="search_query" placeholder="Card Name" id="search_query_text">
        <input type="submit" name="submit" value="Search Cards" id="submit">
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST["search_query"])) { // If searching for a new card to add
            $cardName = htmlspecialchars($_POST["search_query"]);
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
                                <th>CardIndex</th><th>Deck</th>
                            </tr>
                        </thead>
                        <tbody>
                    EOD;
    
                    foreach ($api_response->data as $index => $card) {                                                
                        // For database
                        $cardSetID = validateData($card->set);
                        $cardIndex = validateData($card->collector_number);
                        $scryfallID = validateData($card->id);
                        $cardName = validateData($card->name);
                        $cardManaValue = validateData($card->cmc);
                        $cardRarity = validateData($card->rarity);
                        $cardCurrentPrice = validateData($card->prices->usd);
                        $cardImageURI = validateData($card->image_uris->normal);

                        // For display
                        $setName = htmlspecialchars($card->set_name);
                        $price = isset($cardCurrentPrice) ? "$" . htmlspecialchars($cardCurrentPrice) : "N/A";
    
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($cardName) . "</td>";
                        echo "<td class='card-image'><img src='" . htmlspecialchars($card->image_uris->normal) . "' alt='Card Image'></td>";
                        echo "<td>" . $setName . " (" . strtoupper($cardSetID) . ")</td>";
                        echo "<td>" . $price . "</td>";
                        echo "<td>" . $cardIndex . "</td>";
                        // I haven't been able to get this adding to deck work
                        echo <<<EOD
                        <td>
                            <form action='add_card.php' method='POST'>
                                <input type='hidden' name='CardSetID' value='$cardSetID'>
                                <input type='hidden' name='CardIndex' value='$cardIndex'>
                                <input type='hidden' name='ScryfallID' value='$scryfallID'>
                                <input type='hidden' name='CardName' value='$cardName'>
                                <input type='hidden' name='CardManaValue' value='$cardManaValue'>
                                <input type='hidden' name='CardRarity' value='$cardRarity'>
                                <input type='hidden' name='CardCurrentPrice' value='$cardCurrentPrice'>
                                <input type='hidden' name='CardImageURI' value='$cardImageURI'>                                
                                <button type='submit'>Add to Deck</button>
                            </form>
                        </td>
                        EOD;
                        // <input type='text' name='DeckName' placeholder='DeckID' required>
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
        else { // If attempting to add a card to the database from search results
            try {
                $cardSetID = $_POST["CardSetID"];
                $cardIndex = $_POST['CardIndex'];
                $scryfallID = $_POST["ScryfallID"];
                $cardName = $_POST["CardName"];
                $cardManaValue = intval($_POST["CardManaValue"]);
                $cardRarity = $_POST["CardRarity"];
                $cardCurrentPrice = $_POST["CardCurrentPrice"];
                $userID = 2; // TODO: Make this not hard-coded anymore
                $cardImageURI = $_POST["CardImageURI"];
            }
            catch (Exception $e) {
                echo("Error: " . $e->getMessage());
                echo("Trace: " . print_r($e->getTrace(), true));
            }

            try {
                $insertStatement = "INSERT INTO Card (CardSetID, CardIndex, ScryfallID, CardName, CardManaValue, CardRarity, CardCurrentPrice, UserID, CardImageURI) VALUES (:setid, :index, :scryfall, :name, :manavalue, :rarity, :price, :userid, :image)";
                $stmt = $dbc->prepare($insertStatement);

                $stmt->bindParam(":setid", $cardSetID, PDO::PARAM_STR);
                $stmt->bindParam(":index", $cardIndex, PDO::PARAM_STR);
                $stmt->bindParam(":scryfall", $scryfallID, PDO::PARAM_STR);
                $stmt->bindParam(":name", $cardName, PDO::PARAM_STR);
                $stmt->bindParam(":manavalue", $cardManaValue, PDO::PARAM_INT);
                $stmt->bindParam(":rarity", $cardRarity, PDO::PARAM_STR);
                $stmt->bindParam(":price", $cardCurrentPrice, PDO::PARAM_STR);
                $stmt->bindParam(":userid", $userID, PDO::PARAM_INT);
                $stmt->bindParam(":image", $cardImageURI, PDO::PARAM_STR);
    
                if ($stmt->execute()) { // Execute statement and check for success
                    echo("Card inserted successfully!!!");
                }
                else {
                    echo("Failed to insert card (no exceptions thrown tho)");
                }
            }
            catch (PDOException $e) {
                echo("Error: " . $e->getMessage());
                echo("Trace: " . print_r($e->getTrace(), true));
            }
        }
    }
    ?>
</body>
</html>
