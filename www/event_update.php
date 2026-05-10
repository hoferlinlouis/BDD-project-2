<?php
try {
    $bdd = new PDO('mysql:host=db;dbname=group10;charset=utf8mb4', 'group10', 'secret', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Connection failed: ' . htmlspecialchars($e->getMessage()));
}

$today = date('Y-m-d');
$message = '';
$message_class = '';
$selectedEvent = null;

$themes = $bdd->query('SELECT NAME FROM THEME ORDER BY NAME')->fetchAll(PDO::FETCH_COLUMN);
$locations = $bdd->query('SELECT ID, CITY, STREET FROM LOCATION ORDER BY CITY, STREET')->fetchAll(PDO::FETCH_ASSOC);
$clients = $bdd->query("SELECT CLIENT_NUMBER, CONCAT(FIRST_NAME, ' ', LAST_NAME) AS name FROM CLIENT ORDER BY LAST_NAME, FIRST_NAME")->fetchAll(PDO::FETCH_ASSOC);
$djs = $bdd->query("SELECT ID, CONCAT(FIRSTNAME, ' ', LASTNAME) AS name FROM EMPLOYEE WHERE ID IN (SELECT ID FROM DJ) ORDER BY LASTNAME, FIRSTNAME")->fetchAll(PDO::FETCH_ASSOC);
$planners = $bdd->query("SELECT ID, CONCAT(FIRSTNAME, ' ', LASTNAME) AS name FROM EMPLOYEE WHERE ID IN (SELECT ID FROM EVENTPLANNER) ORDER BY LASTNAME, FIRSTNAME")->fetchAll(PDO::FETCH_ASSOC);
$managers = $bdd->query("SELECT ID, CONCAT(FIRSTNAME, ' ', LASTNAME) AS name FROM EMPLOYEE WHERE ID IN (SELECT ID FROM MANAGER) ORDER BY LASTNAME, FIRSTNAME")->fetchAll(PDO::FETCH_ASSOC);
$playlists = $bdd->query('SELECT NAME FROM PLAYLIST ORDER BY NAME')->fetchAll(PDO::FETCH_COLUMN);

$futureEvents = $bdd->prepare('SELECT ID, NAME, DATE FROM EVENT WHERE DATE >= :today ORDER BY DATE DESC, NAME ASC');
$futureEvents->execute([':today' => $today]);
$futureEvents = $futureEvents->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $eventId = (int)$_POST['event_id'];
    $eventDate = $_POST['event_date'];
    $eventName = trim($_POST['event_name']);
    $description = trim($_POST['description']);
    $theme = $_POST['theme'] ?: null;
    $type = trim($_POST['type']);
    $location = !empty($_POST['location']) ? (int)$_POST['location'] : null;
    $rentalFee = $_POST['rental_fee'] !== '' ? (int)$_POST['rental_fee'] : null;
    $client = !empty($_POST['client']) ? (int)$_POST['client'] : null;
    $manager = !empty($_POST['manager']) ? (int)$_POST['manager'] : null;
    $dj = !empty($_POST['dj']) ? (int)$_POST['dj'] : null;
    $planner = !empty($_POST['planner']) ? (int)$_POST['planner'] : null;
    $playlist = $_POST['playlist'] ?: null;

    $stmt = $bdd->prepare('SELECT COUNT(*) FROM EVENT WHERE ID = :id AND DATE >= :today');
    $stmt->execute([':id' => $eventId, ':today' => $today]);
    $isEditableFutureEvent = $stmt->fetchColumn() > 0;

    if (!$isEditableFutureEvent) {
        $message = 'Cannot update past events. Only future events may be modified.';
        $message_class = 'error';
    } elseif ($eventDate < $today) {
        $message = 'Cannot update past events. Only future events may be modified.';
        $message_class = 'error';
    } else {
        $errors = [];
        if ($dj) {
            $stmt = $bdd->prepare('SELECT COUNT(*) FROM EVENT WHERE DATE = :date AND DJ = :dj AND ID != :id');
            $stmt->execute([':date' => $eventDate, ':dj' => $dj, ':id' => $eventId]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'This DJ already has an event on that date.';
            }
        }
        if ($planner) {
            $stmt = $bdd->prepare('SELECT COUNT(*) FROM EVENT WHERE DATE = :date AND EVENT_PLANNER = :planner AND ID != :id');
            $stmt->execute([':date' => $eventDate, ':planner' => $planner, ':id' => $eventId]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'This event planner already has an event on that date.';
            }
        }
        if ($playlist) {
            $stmt = $bdd->prepare(
                'SELECT playlist_cds.CD_NUMBER, C.COPIES, COUNT(DISTINCT E.ID) AS used_count
                 FROM (
                     SELECT DISTINCT CD_NUMBER
                     FROM CONTAINS
                     WHERE PLAYLIST = :playlist
                 ) AS playlist_cds
                 JOIN CD C ON C.CD_NUMBER = playlist_cds.CD_NUMBER
                 LEFT JOIN CONTAINS other_contains ON other_contains.CD_NUMBER = playlist_cds.CD_NUMBER
                 LEFT JOIN EVENT E
                    ON E.PLAYLIST = other_contains.PLAYLIST
                   AND E.DATE = :date
                   AND E.ID != :id
                 GROUP BY playlist_cds.CD_NUMBER, C.COPIES'
            );
            $stmt->execute([':date' => $eventDate, ':id' => $eventId, ':playlist' => $playlist]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['used_count'] >= $row['COPIES']) {
                    $errors[] = 'CD ' . htmlspecialchars($row['CD_NUMBER']) . ' is unavailable on that date.';
                }
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $bdd->prepare(
                    'UPDATE EVENT SET NAME = :name, DATE = :date, DESCRIPTION = :description, THEME = :theme,
                        TYPE = :type, LOCATION = :location, RENTAL_FEE = :rental_fee, CLIENT = :client,
                        MANAGER = :manager, DJ = :dj, EVENT_PLANNER = :planner, PLAYLIST = :playlist
                     WHERE ID = :id'
                );
                $stmt->execute([
                    ':name' => $eventName,
                    ':date' => $eventDate,
                    ':description' => $description,
                    ':theme' => $theme,
                    ':type' => $type,
                    ':location' => $location,
                    ':rental_fee' => $rentalFee,
                    ':client' => $client,
                    ':manager' => $manager,
                    ':dj' => $dj,
                    ':planner' => $planner,
                    ':playlist' => $playlist,
                    ':id' => $eventId
                ]);
                $message = 'Event updated successfully.';
                $message_class = 'success';
                $futureEvents = $bdd->prepare('SELECT ID, NAME, DATE FROM EVENT WHERE DATE >= :today ORDER BY DATE DESC, NAME ASC');
                $futureEvents->execute([':today' => $today]);
                $futureEvents = $futureEvents->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $message = 'Database error: ' . htmlspecialchars($e->getMessage());
                $message_class = 'error';
            }
        } else {
            $message = implode('<br>', array_map('htmlspecialchars', $errors));
            $message_class = 'error';
        }
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'select' && !empty($_POST['event_selection'])) {
    $selectedId = (int)$_POST['event_selection'];
    $stmt = $bdd->prepare('SELECT * FROM EVENT WHERE ID = :id AND DATE >= :today');
    $stmt->execute([':id' => $selectedId, ':today' => $today]);
    $selectedEvent = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Update</title>
    <style>
        body { margin: 20px; }
        .section { background: #f9f9f9; padding: 15px; margin: 15px 0; border: 1px solid #ddd; }
        .form-group { margin: 12px 0; }
        label { display: block; font-weight: bold; margin-bottom: 4px; }
        input, select, textarea { padding: 6px; width: 100%; max-width: 520px; box-sizing: border-box; }
        textarea { height: 80px; }
        button { padding: 8px 14px; margin-top: 8px; }
        .message { padding: 10px; margin: 12px 0; border: 1px solid #ccc; }
        .success { background: #e6ffed; }
        .error, .error-box { background: #ffe6e6; }
        .info-box { background: #f4f4f4; padding: 10px; margin: 12px 0; border: 1px solid #ddd; }
        .required { color: red; }
    </style>
</head>
<body>
    <a href="index.php">Back to Home</a>
    <h1>Event Update</h1>
    <p>Select and update information for future events.</p>

    <?php if ($message): ?>
        <div class="message <?= $message_class ?>"><?= $message ?></div>
    <?php endif; ?>

    <div class="section">
        <h2>Select an Event</h2>
        <form method="post">
            <input type="hidden" name="action" value="select">
            <div class="form-group">
                <label for="event_selection">Choose a Future Event:</label>
                <select name="event_selection" id="event_selection" onchange="this.form.submit()">
                    <option value="">-- Select an event --</option>
                    <?php foreach ($futureEvents as $event): ?>
                        <option value="<?= $event['ID'] ?>" <?= $selectedEvent && $selectedEvent['ID'] == $event['ID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($event['DATE']) ?> - <?= htmlspecialchars($event['NAME']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <?php if (empty($futureEvents)): ?>
            <div class="error-box">No future events available to manage.</div>
        <?php endif; ?>
    </div>

    <?php if ($selectedEvent): ?>
    <div class="section">
        <h2>Update Event Details</h2>
        <div class="info-box">
            <strong>Rules:</strong>
            <ul>
                <li>Only one job per day for each DJ</li>
                <li>Only one job per day for each Event Planner</li>
                <li>Selected CDs must be available on the event date</li>
            </ul>
        </div>
        
        <form method="post">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="event_id" value="<?= $selectedEvent['ID'] ?>">
            
            <div class="form-group">
                <label for="event_date">Event Date:</label>
                <input type="date" name="event_date" id="event_date" value="<?= $selectedEvent['DATE'] ?>" required>
                <small>Changing the date will check the constraints again.</small>
            </div>
            
            <div class="form-group">
                <label for="event_name">Event Name:</label>
                <input type="text" name="event_name" id="event_name" value="<?= htmlspecialchars($selectedEvent['NAME']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="type">Event Type:</label>
                <input type="text" name="type" id="type" value="<?= htmlspecialchars($selectedEvent['TYPE']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea name="description" id="description"><?= htmlspecialchars($selectedEvent['DESCRIPTION'] ?? '') ?></textarea>
            </div>

            <h3>Participants & Resources</h3>
            
            <div class="form-group">
                <label for="client">Client:</label>
                <select name="client" id="client">
                    <option value="">-- None --</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['CLIENT_NUMBER'] ?>" <?= $selectedEvent['CLIENT'] == $client['CLIENT_NUMBER'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($client['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="manager">Manager:</label>
                <select name="manager" id="manager">
                    <option value="">-- None --</option>
                    <?php foreach ($managers as $manager): ?>
                        <option value="<?= $manager['ID'] ?>" <?= $selectedEvent['MANAGER'] == $manager['ID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($manager['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="dj">DJ: <strong class="required">*</strong></label>
                <select name="dj" id="dj">
                    <option value="">-- None --</option>
                    <?php foreach ($djs as $dj_option): ?>
                        <option value="<?= $dj_option['ID'] ?>" <?= $selectedEvent['DJ'] == $dj_option['ID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dj_option['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>DJ cannot have more than 1 job per day.</small>
            </div>

            <div class="form-group">
                <label for="planner">Event Planner: <strong class="required">*</strong></label>
                <select name="planner" id="planner">
                    <option value="">-- None --</option>
                    <?php foreach ($planners as $planner_option): ?>
                        <option value="<?= $planner_option['ID'] ?>" <?= $selectedEvent['EVENT_PLANNER'] == $planner_option['ID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($planner_option['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Event planner cannot have more than 1 job per day.</small>
            </div>

            <h3>Location & Theme</h3>
            
            <div class="form-group">
                <label for="location">Location:</label>
                <select name="location" id="location">
                    <option value="">-- None --</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc['ID'] ?>" <?= $selectedEvent['LOCATION'] == $loc['ID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loc['CITY']) ?> - <?= htmlspecialchars($loc['STREET']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="theme">Theme:</label>
                <select name="theme" id="theme">
                    <option value="">-- None --</option>
                    <?php foreach ($themes as $theme_option): ?>
                        <option value="<?= $theme_option ?>" <?= $selectedEvent['THEME'] == $theme_option ? 'selected' : '' ?>>
                            <?= htmlspecialchars($theme_option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <h3>Playlist & Financial</h3>
            
            <div class="form-group">
                <label for="playlist">Playlist: <strong class="required">*</strong></label>
                <select name="playlist" id="playlist">
                    <option value="">-- None --</option>
                    <?php foreach ($playlists as $pl): ?>
                        <option value="<?= $pl ?>" <?= $selectedEvent['PLAYLIST'] == $pl ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pl) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Selected CDs must be available on the event date.</small>
            </div>

            <div class="form-group">
                <label for="rental_fee">Rental Fee (€):</label>
                <input type="number" name="rental_fee" id="rental_fee" value="<?= $selectedEvent['RENTAL_FEE'] ?? '' ?>" min="0">
            </div>

            <button type="submit" onclick="return confirm('Are you sure you want to update this event?');">
                Save Changes
            </button>
            <button type="reset" class="button-secondary">Reset Form</button>
        </form>
    </div>
    <?php endif; ?>

</body>
</html>
