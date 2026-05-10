<?php
$bdd = new PDO('mysql:host=db;dbname=group10;charset=utf8mb4', 'group10', 'secret', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$message = "";
$selected_cd = isset($_GET['cd_id']) ? (int)$_GET['cd_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cd_id = (int)$_POST['cd_id'];

    if (isset($_POST['add_song'])) {
        $track_number = trim($_POST['track_number']);
        if ($track_number === '') {
            $stmt = $bdd->prepare("SELECT COALESCE(MAX(TRACK_NUMBER), 0) + 1 FROM SONG WHERE CD_NUMBER = ?");
            $stmt->execute([$cd_id]);
            $track_number = $stmt->fetchColumn();
        }

        $stmt = $bdd->prepare("INSERT INTO SONG (CD_NUMBER, TRACK_NUMBER, TITLE, ARTIST, DURATION, GENRE) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $cd_id,
            $track_number,
            trim($_POST['title']),
            trim($_POST['artist']),
            trim($_POST['duration']),
            trim($_POST['genre']) ?: null
        ]);
        $message = "Song added.";
    } elseif (isset($_POST['update_song'])) {
        $stmt = $bdd->prepare("UPDATE SONG SET TITLE = ?, ARTIST = ?, DURATION = ?, GENRE = ? WHERE CD_NUMBER = ? AND TRACK_NUMBER = ?");
        $stmt->execute([
            trim($_POST['title']),
            trim($_POST['artist']),
            trim($_POST['duration']),
            trim($_POST['genre']) ?: null,
            $cd_id,
            (int)$_POST['track_number']
        ]);
        $message = "Song updated.";
    } elseif (isset($_POST['delete_song'])) {
        $track_number = (int)$_POST['track_number'];
        $bdd->prepare("DELETE FROM CONTAINS WHERE CD_NUMBER = ? AND TRACK_NUMBER = ?")->execute([$cd_id, $track_number]);
        $bdd->prepare("DELETE FROM SONG WHERE CD_NUMBER = ? AND TRACK_NUMBER = ?")->execute([$cd_id, $track_number]);
        $message = "Song deleted and removed from playlists.";
    }

    $selected_cd = $cd_id;
}

$cds = $bdd->query("SELECT CD_NUMBER, TITLE FROM CD ORDER BY TITLE")->fetchAll(PDO::FETCH_ASSOC);
$genres = $bdd->query("SELECT NAME FROM GENRE ORDER BY NAME")->fetchAll(PDO::FETCH_COLUMN);

$songs = [];
if ($selected_cd) {
    $stmt = $bdd->prepare("SELECT * FROM SONG WHERE CD_NUMBER = ? ORDER BY TRACK_NUMBER");
    $stmt->execute([$selected_cd]);
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CD Manager</title>
</head>
<body>
    <a href="index.php">Back to Home</a>
    <h1>CD Manager</h1>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <div style="background: #f4f4f4; padding: 15px;">
        <h3>Select a CD</h3>
        <form method="get" action="gestion_cd.php">
            <select name="cd_id" onchange="this.form.submit()">
                <option value="">-- Choose a CD --</option>
                <?php foreach ($cds as $cd): ?>
                    <option value="<?= $cd['CD_NUMBER'] ?>" <?= $selected_cd == $cd['CD_NUMBER'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cd['TITLE']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($selected_cd): ?>
        <h2>Manage Songs</h2>
        <form method="post" action="gestion_cd.php?cd_id=<?= $selected_cd ?>">
            <input type="hidden" name="cd_id" value="<?= $selected_cd ?>">
            <p>
                Track number:
                <input type="number" name="track_number" min="1" placeholder="auto">
                Title:
                <input type="text" name="title" required>
                Artist:
                <input type="text" name="artist" required>
                Duration:
                <input type="text" name="duration" placeholder="00:03:30" required>
                Genre:
                <select name="genre">
                    <option value="">-- None --</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?= htmlspecialchars($genre) ?>"><?= htmlspecialchars($genre) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" name="add_song" value="Add Song">
            </p>
        </form>

        <h3>Existing Songs</h3>
        <?php if (!$songs): ?>
            <p>No songs found for this CD.</p>
        <?php else: ?>
            <table border="1" cellpadding="8" style="border-collapse: collapse; width: 100%;">
                <tr style="background: #eee;">
                    <th>Track</th>
                    <th>Title</th>
                    <th>Artist</th>
                    <th>Duration</th>
                    <th>Genre</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($songs as $song): ?>
                    <tr>
                        <form method="post" action="gestion_cd.php?cd_id=<?= $selected_cd ?>">
                            <input type="hidden" name="cd_id" value="<?= $selected_cd ?>">
                            <input type="hidden" name="track_number" value="<?= $song['TRACK_NUMBER'] ?>">
                            <td><?= htmlspecialchars($song['TRACK_NUMBER']) ?></td>
                            <td><input type="text" name="title" value="<?= htmlspecialchars($song['TITLE']) ?>"></td>
                            <td><input type="text" name="artist" value="<?= htmlspecialchars($song['ARTIST']) ?>"></td>
                            <td><input type="text" name="duration" value="<?= htmlspecialchars($song['DURATION']) ?>" size="8"></td>
                            <td>
                                <select name="genre">
                                    <option value="">-- None --</option>
                                    <?php foreach ($genres as $genre): ?>
                                        <option value="<?= htmlspecialchars($genre) ?>" <?= $song['GENRE'] === $genre ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($genre) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="submit" name="update_song" value="Update">
                                <input type="submit" name="delete_song" value="Delete" onclick="return confirm('Delete this song?')">
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    <?php else: ?>
        <p>Please select a CD above.</p>
    <?php endif; ?>

</body>
</html>
