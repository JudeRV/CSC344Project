<?php
if (!isset($_COOKIE["user"])) {
    header("Location: login.php"); // Redirect to login if not logged in
}
require_once("../pdo_connect.php");

function validateData($data) {
    return isset($data) ? $data : null;
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
        .grid {padding: 7rem;}
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
        if (isset($_POST["search_query"])) { // Searching for cards
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
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                    EOD;
    
                    foreach ($api_response->data as $card) {                                                 
                        $cardSetID = validateData($card->set);
                        $cardIndex = validateData($card->collector_number);
                        $scryfallID = validateData($card->id);
                        $cardName = validateData($card->name);
                        $cardManaValue = validateData($card->cmc);
                        $cardRarity = validateData($card->rarity);
                        $cardCurrentPrice = validateData($card->prices->usd);
                        $cardColors = implode(",", $card->colors ?? []); // Convert colors array to string
                        $cardImageURI = validateData($card->image_uris->normal);

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($cardName) . "</td>";
                        echo "<td class='card-image'><img src='" . htmlspecialchars($cardImageURI) . "' alt='Card Image'></td>";
                        echo "<td>" . htmlspecialchars($cardSetID) . "</td>";
                        echo "<td>" . (isset($cardCurrentPrice) ? "$" . htmlspecialchars($cardCurrentPrice) : "N/A") . "</td>";
                        echo <<<EOD
                        <td>
                            <form action="add_card.php" method="POST">
                                <input type="hidden" name="CardSetID" value="$cardSetID">
                                <input type="hidden" name="CardIndex" value="$cardIndex">
                                <input type='hidden' name='ScryfallID' value='$scryfallID'>
                                <input type="hidden" name="CardName" value="$cardName">
                                <input type="hidden" name="CardManaValue" value="$cardManaValue">
                                <input type="hidden" name="CardRarity" value="$cardRarity">
                                <input type="hidden" name="CardCurrentPrice" value="$cardCurrentPrice">
                                <input type="hidden" name="Colors" value="$cardColors">
                                <input type="hidden" name="CardImageURI" value="$cardImageURI">
                                <button type="submit">Add to Deck</button>
                            </form>
                        </td>
                        EOD;
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
        } else { // Adding a card to the database
            try {
                $cardSetID = $_POST["CardSetID"];
                $scryfallID = $_POST["ScryfallID"];
                $cardIndex = $_POST["CardIndex"];
                $cardName = $_POST["CardName"];
                $cardManaValue = $_POST["CardManaValue"];
                $cardRarity = $_POST["CardRarity"];
                $cardCurrentPrice = $_POST["CardCurrentPrice"];
                $colors = $_POST["Colors"];
                $cardImageURI = $_POST["CardImageURI"];
                $userID = 2; // TODO: Replace with the logged-in user ID

                // Call the stored procedure
                $sql = "CALL InsertCardWithColors(:CardSetID, :CardIndex, :ScryfallID, :CardName, :CardManaValue, :CardRarity, :CardCurrentPrice, :UserID, :CardImageURI, :Colors)";
                $stmt = $dbc->prepare($sql);
                $stmt->bindParam(":CardSetID", $cardSetID);
                $stmt->bindParam(":ScryfallID", $scryfallID);
                $stmt->bindParam(":CardIndex", $cardIndex);
                $stmt->bindParam(":CardName", $cardName);
                $stmt->bindParam(":CardManaValue", $cardManaValue);
                $stmt->bindParam(":CardRarity", $cardRarity);
                $stmt->bindParam(":CardCurrentPrice", $cardCurrentPrice);
                $stmt->bindParam(":UserID", $userID);
                $stmt->bindParam(":CardImageURI", $cardImageURI);
                $stmt->bindParam(":Colors", $colors);

                if ($stmt->execute()) {
                    echo "<p>Card and its colors were successfully added!</p>";
                } else {
                    echo "<p>Failed to add the card.</p>";
                }
            } catch (PDOException $e) {
                echo "<p>Error: " . $e->getMessage() . "</p>";
            }
        }
    }
    ?>
    <footer>
        <div class="grid"></div>
    </footer>
</body>
</html>
