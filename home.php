<?php
// Include the file that retrieves cards from the database
require_once("../pdo_connect.php");

try {
    // Query to fetch card data
    $stmt = $dbc->query('SELECT * FROM Card');
    $cards = $stmt->fetchAll(); // Fetch all card data
} catch (PDOException $e) {
    die("Error fetching cards: " . $e->getMessage());
}


// Handle filtering logic
$nameFilter = $_GET['name'] ?? '';
$colorsFilter = $_GET['colors'] ?? [];
$setFilter = $_GET['set'] ?? '';

// Filter the $cards array (modify based on how data retrieval works)
function filterCards($cards, $nameFilter, $colorsFilter, $setFilter) {
    return array_filter($cards, function($card) use ($nameFilter, $colorsFilter, $setFilter) {
        // Fuzzy name matching
        if ($nameFilter && stripos($card['name'], $nameFilter) === false) {
            return false;
        }

        // Color matching (checks if card has one of the selected colors)
        if (!empty($colorsFilter) && !array_intersect($colorsFilter, explode(',', $card['colors']))) {
            return false;
        }

        // Set matching
        if ($setFilter && stripos($card['set'], $setFilter) === false) {
            return false;
        }

        return true;
    });
}


$filteredCards = filterCards($cards, $nameFilter, $colorsFilter, $setFilter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magic: The Gathering Collection</title>
    <link rel="stylesheet" href="style.css"> <!-- Optional, for CSS -->
</head>
<body>
    <h1>Magic: The Gathering Collection</h1>

    <!-- Filter Form -->
    <form method="GET">
        <label for="name">Card Name:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($nameFilter); ?>">

        <fieldset>
            <legend>Colors</legend>
            <?php
            $colorOptions = ['Red', 'Green', 'Blue', 'White', 'Black', 'Colorless'];
            foreach ($colorOptions as $color) {
                $isChecked = in_array(strtolower($color), array_map('strtolower', $colorsFilter)) ? 'checked' : '';
                echo "<label><input type='checkbox' name='colors[]' value='$color' $isChecked> $color</label>";
            }
            ?>
        </fieldset>

        <label for="set">Card Set:</label>
        <input type="text" id="set" name="set" value="<?php echo htmlspecialchars($setFilter); ?>">

        <button type="submit">Filter</button>
    </form>

    <div class="card-container">
        <?php
        if (!empty($filteredCards)) {
            foreach ($filteredCards as $card) {
                echo '<div class="card">';
                echo '<h2>' . htmlspecialchars($card['CardName']) . '</h2>';
                echo '<p>Mana Value: ' . htmlspecialchars($card['CardManaValue']) . '</p>';
                echo '<p>Rarity: ' . htmlspecialchars($card['CardRarity']) . '</p>';
                echo '<p>Current Price: $' . htmlspecialchars($card['CardCurrentPrice']) . '</p>';
                echo '</div>';
            }
            
        } else {
            echo '<p>No cards match the filters.</p>';
        }
        ?>
    </div>
</body>
</html>
