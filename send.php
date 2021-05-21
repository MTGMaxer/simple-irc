<?php
define('DB_MSG_LIMIT',  10);

$inputJson = file_get_contents('php://input');
$input = json_decode($inputJson, true);

if (isset($input['nickname'], $input['message'])) {
    $dbUrl = parse_url(getenv("CLEARDB_DATABASE_URL"));

    $server = $dbUrl["host"];
    $username = $dbUrl["user"];
    $password = $dbUrl["pass"];
    $dbName = substr($dbUrl["path"], 1);

    $db = new mysqli($server, $username, $password, $dbName);
    $err = $db->connect_error;
    if ($err) {
        die($err);
    }
    $nickname =  $input['nickname'];
    $message = $input['message'];
    $color = $input['color'];

    $dbname = 'irc';
    $stmt;
    if ($message === '/quit') {
        $dbname = 'irc_sys';
        $stmt = $db->prepare('INSERT INTO irc_sys(type, nickname, event_value, time) VALUES (?, ?, ?, ?)');
        $time = round(microtime(true) * 1000);
        $type = 'USER_QUIT';
        $value = null;
        $stmt->bind_param('sssi', $type, $nickname, $value, $time);
    } else if (preg_match('/^\/nick .+$/', $message)) {
        $dbname = 'irc_sys';
        $stmt = $db->prepare('INSERT INTO irc_sys(type, nickname, event_value, time) VALUES (?, ?, ?, ?)');
        $newNick = substr($message, strpos($message, ' ') + 1);
        $time = round(microtime(true) * 1000);
        $type = 'NICKNAME_CHANGE';
        $stmt->bind_param('sssi', $type, $nickname, $newNick, $time);
    } else if (preg_match('/^\/color ([0-9]|[a-f]|[A-F]){6}$/', $message)) {
        $dbname = 'irc_sys';
        $stmt = $db->prepare('INSERT INTO irc_sys(type, nickname, event_value, time) VALUES (?, ?, ?, ?)');
        $newColor = substr($message, strpos($message, ' ') + 1);
        $time = round(microtime(true) * 1000);
        $type = 'COLOR_CHANGE';
        $stmt->bind_param('sssi', $type, $nickname, $newColor, $time);
    } else {
        $time = round(microtime(true) * 1000);
        $stmt = $db->prepare('INSERT INTO irc(nickname, color, message, time) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('sssi', $nickname, $color, $message, $time);
    }

    $stmt->execute();

    $cnt = $db->query('SELECT id FROM ' . $dbname . ' ORDER BY id DESC LIMIT 1');
    $row = $cnt->fetch_assoc();
    $id = $row['id'];
    if ($id % DB_MSG_LIMIT === 0) {
        $min = $id - (DB_MSG_LIMIT - 1);
        $db->query('DELETE FROM ' . $dbname . ' WHERE id < ' . $min);
    }
}
