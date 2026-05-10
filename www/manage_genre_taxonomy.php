<!DOCTYPE html>
<?php
$bdd = new PDO('mysql:host=db;dbname=group10;charset=utf8mb4', 'group10', 'secret', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$message = '';
$parsed_pairs = [];
$errors = [];

function has_path($edges, $start, $target) {
    $stack = [$start];
    $visited = [];

    while ($stack) {
        $node = array_pop($stack);
        if (isset($visited[$node])) {
            continue;
        }
        $visited[$node] = true;

        if (!empty($edges[$node])) {
            foreach ($edges[$node] as $next) {
                if ($next === $target) {
                    return true;
                }
                $stack[] = $next;
            }
        }
    }

    return false;
}

if (isset($_POST['preview_data'])) {
    $lines = explode("\n", $_POST['taxonomy_text'] ?? '');

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $parts = explode(",", $line);
        if (count($parts) < 2) {
            $errors[] = "Invalid line: " . htmlspecialchars($line);
            continue;
        }

        $child = trim($parts[0]);
        $parent = trim($parts[1]);

        if ($child === '' || $parent === '') {
            $errors[] = "Invalid line: " . htmlspecialchars($line);
        } elseif (strcasecmp($child, $parent) === 0) {
            $errors[] = "A genre cannot be its own parent: " . htmlspecialchars($line);
        } else {
            $parsed_pairs[] = ['child' => $child, 'parent' => $parent];
        }
    }
}

if (isset($_POST['import_data'])) {
    $selected_pairs = $_POST['taxonomy_pairs'] ?? [];

    if (!$selected_pairs) {
        $message = "<h3 style='color:orange;'>No genres were selected for import.</h3>";
    } else {
        try {
            $existing = $bdd->query("SELECT SUBGENRE, GENRE FROM SPECIALIZES")->fetchAll(PDO::FETCH_ASSOC);
            $edges = [];

            foreach ($existing as $row) {
                $edges[$row['SUBGENRE']][] = $row['GENRE'];
            }

            $pairs = [];
            foreach ($selected_pairs as $pair) {
                list($child, $parent) = explode('|', $pair);
                $child = trim($child);
                $parent = trim($parent);

                if ($child === '' || $parent === '') {
                    continue;
                }

                if (strcasecmp($child, $parent) === 0 || has_path($edges, $parent, $child)) {
                    throw new Exception("Cycle detected with " . htmlspecialchars($child . " -> " . $parent));
                }

                $pairs[] = ['child' => $child, 'parent' => $parent];
                $edges[$child][] = $parent;
            }

            $bdd->beginTransaction();

            $stmt_genre = $bdd->prepare("INSERT IGNORE INTO GENRE (NAME) VALUES (?)");
            $stmt_spec = $bdd->prepare("INSERT IGNORE INTO SPECIALIZES (SUBGENRE, GENRE) VALUES (?, ?)");

            foreach ($pairs as $pair) {
                $stmt_genre->execute([$pair['parent']]);
                $stmt_genre->execute([$pair['child']]);
                $stmt_spec->execute([$pair['child'], $pair['parent']]);
            }

            $bdd->commit();
            $message = "<h3 style='color:green;'>Taxonomy successfully updated!</h3>";
        } catch (Exception $e) {
            if ($bdd->inTransaction()) {
                $bdd->rollBack();
            }
            $message = "<h3 style='color:red;'>Transaction failed: " . $e->getMessage() . "</h3>";
        }
    }
}
?>
<html lang="en">
<head><meta charset="UTF-8"><title>Genre Taxonomy Manager</title></head>
<body>
    <a href="index.php">Back to Home</a>
    <h1>Genre Taxonomy Manager</h1>

    <?= $message ?>

    <?php if ($errors): ?>
        <h3 style="color:red;">Input errors</h3>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="manage_genre_taxonomy.php">
        <p>Enter one child and one parent genre per line:</p>
        <textarea name="taxonomy_text" rows="8" cols="50" placeholder="POP ROCK, POP&#10;POP ROCK, ROCK&#10;GLAM ROCK, ROCK" required><?= htmlspecialchars($_POST['taxonomy_text'] ?? '') ?></textarea>
        <br><br>
        <input type="submit" name="preview_data" value="Parse and preview">
    </form>

    <hr>

    <?php if ($parsed_pairs): ?>
        <h2>Preview</h2>
        <form method="post" action="manage_genre_taxonomy.php">
            <ul style="list-style-type: none;">
                <?php foreach ($parsed_pairs as $pair): ?>
                    <li>
                        <label>
                            <input type="checkbox" name="taxonomy_pairs[]" value="<?= htmlspecialchars($pair['child'] . '|' . $pair['parent']) ?>" checked>
                            <?= htmlspecialchars($pair['child'] . " -> " . $pair['parent']) ?>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
            <input type="submit" name="import_data" value="Import selected pairs">
        </form>
    <?php elseif (isset($_POST['preview_data']) && !$errors): ?>
        <p>No valid pairs found.</p>
    <?php endif; ?>

</body>
</html>
