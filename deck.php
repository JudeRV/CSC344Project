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
        $checkCardSQL = "SELECT 1 FROM Card WHERE CardSetID = :cardSetID AND CardIndex = :cardIndex";
        $checkStmt = $dbc->prepare($checkCardSQL);
        $checkStmt->execute([':cardSetID' => $cardSetID, ':cardIndex' => $cardIndex]);

        if ($checkStmt->rowCount() > 0) {
            
            $insertCardSQL = "INSERT INTO Includes (CardSetID, CardIndex, DeckID) VALUES (:cardSetID, :cardIndex, :deckID)";
            $stmt = $dbc->prepare($insertCardSQL);
            $stmt->execute([':cardSetID' => $cardSetID, ':cardIndex' => $cardIndex, ':deckID' => $deckID]);
            header("Location: home.php");
        } else {
            echo "<p>Card does not exist in the database. Please check the card information.</p>";
        }
    } catch (PDOException $e) {
        die("Error adding card to deck: " . $e->getMessage());
    }
}

// Ensure the deck_id is properly fetched from the URL
if (isset($_GET['deck_id'])) {
    $deckId = $_GET['deck_id'];  // Get the dynamic deck ID from URL
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



// Fetch the highest priced card for the user (if needed)
try {
    $sqlHighestPriceCard = "
        SELECT CardName, CardCurrentPrice
        FROM Card
        WHERE UserID = 2
          AND CardCurrentPrice = (
              SELECT MAX(CardCurrentPrice)
              FROM Card
              WHERE UserID = 2
          )
    ";
    $stmtHighestPrice = $dbc->prepare($sqlHighestPriceCard);
    $stmtHighestPrice->execute();
    $highestPriceCard = $stmtHighestPrice->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching highest priced card: " . $e->getMessage());
}


try {
    $userID = 2; 
    echo "<p>DeckID: $deckId</p>";  
    
    $sql = "SELECT GetDeckTotalPrice(:userID, :deckID) AS TotalPrice";
    $stmt = $dbc->prepare($sql);
    $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
    $stmt->bindParam(':deckID', $deckId, PDO::PARAM_INT); 
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_BOTH);  
    
    if ($result) {
        $totalPrice = $result['TotalPrice']; 
        echo "<p>Total Price: $" . number_format($totalPrice, 2) . "</p>";
    } else {
        echo "<p>Error fetching total price or no cards found for this deck.</p>";
    }
} catch (PDOException $e) {
    die("Error fetching deck total price: " . $e->getMessage());
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
    <nav>
        <ul>
            <li><a href="home.php">Home</a></li>
            <li><a href="add_card.php">Add Card</a></li>
            <li><a href="deck.php">Deck</a></li>
            <li><a href="login.php?logout=true">Logout</a></li>
        </ul>
    </nav>
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

   

    <!-- Display Deck Total Price -->
    <h2>Total Price of Deck</h2>
    <p>The total price of the deck is: $<?php echo number_format($totalPrice, 2); ?></p>

    <?php if ($highestPriceCard): ?>
        <h2>Highest Priced Card for UserID = 2</h2>
        <div class="highest-price-card">
            <p><strong>Card Name:</strong> <?php echo htmlspecialchars($highestPriceCard['CardName']); ?></p>
            <p><strong>Current Price:</strong> $<?php echo htmlspecialchars($highestPriceCard['CardCurrentPrice']); ?></p>
        </div>
    <?php else: ?>
        <p>No cards found for UserID = 2.</p>
    <?php endif; ?>
</body>
</html>
