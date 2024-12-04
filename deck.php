<?php

require_once("../pdo_connect.php");

// Handle deck creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_deck'])) {
    $deckName = $_POST['deck_name'];
    $deckDescription = $_POST['deck_description'];

    try {
        // Insert new deck and automatically assign DeckID
        $insertDeckSQL = "INSERT INTO Deck (DeckName, DeckDescription) VALUES (:deckName, :deckDescription)";
        $stmt = $dbc->prepare($insertDeckSQL);
        $stmt->execute([':deckName' => $deckName, ':deckDescription' => $deckDescription]);
        echo "<p>Deck '$deckName' created successfully!</p>";
    } catch (PDOException $e) {
        die("Error creating deck: " . $e->getMessage());
    }
}

// Handle adding a card to the deck
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_id'], $_POST['deck_id'])) {
    $cardSetID = $_POST['card_set_id'];
    $cardIndex = $_POST['card_id'];
    $deckID = $_POST['deck_id'];

    try {
        $insertCardSQL = "INSERT INTO Includes (CardSetID, CardIndex, DeckID) VALUES (:cardSetID, :cardIndex, :deckID)";
        $stmt = $dbc->prepare($insertCardSQL);
        $stmt->execute([':cardSetID' => $cardSetID, ':cardIndex' => $cardIndex, ':deckID' => $deckID]);
        echo "<p>Card added to the deck successfully!</p>";
    } catch (PDOException $e) {
        die("Error adding card to deck: " . $e->getMessage());
    }
}

// Fetch deck details if deck_id is provided
if (isset($_GET['deck_id'])) {
    $deckId = $_GET['deck_id'];
    try {
        $sql = "
            SELECT c.CardName, c.CardManaValue, c.CardRarity, c.CardCurrentPrice 
            FROM Card AS c
            INNER JOIN Includes AS i ON c.CardSetID = i.CardSetID AND c.CardIndex = i.CardIndex
            WHERE i.DeckID = :deckId
        ";
        $stmt = $dbc->prepare($sql);
        $stmt->bindParam(':deckId', $deckId, PDO::PARAM_INT);
        $stmt->execute();
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching deck: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deck Details</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>
    <h1>Create a New Deck</h1>
    <form method="POST">
        <label for="deck_name">Deck Name:</label>
        <input type="text" id="deck_name" name="deck_name" required>

        <label for="deck_description">Deck Description:</label>
        <textarea id="deck_description" name="deck_description" required></textarea>

        <button type="submit" name="create_deck">Create Deck</button>
    </form>

    <h1>Deck Details</h1>
    <div class="deck-container">
        <?php if (!empty($cards)): ?>
            <?php foreach ($cards as $card): ?>
                <div class="card">
                    <h2><?php echo htmlspecialchars($card['CardName']); ?></h2>
                    <p>Mana Value: <?php echo htmlspecialchars($card['CardManaValue']); ?></p>
                    <p>Rarity: <?php echo htmlspecialchars($card['CardRarity']); ?></p>
                    <p>Current Price: $<?php echo htmlspecialchars($card['CardCurrentPrice']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No cards found in this deck.</p>
        <?php endif; ?>
    </div>
</body>
</html>
