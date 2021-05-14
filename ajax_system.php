<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO db user
    $db = new mysqli('localhost', 'root', '', 'irc');
    $err = $db->connect_error;
    if ($err) {
        die($err);
    }

    function latestMsg($db)
    {
        $query = 'SELECT * FROM irc_sys ORDER BY id DESC LIMIT 1';
        $res  = $db->query($query);
        $msg = $res->fetch_assoc();
        return $msg;
    }

    $msg = latestMsg($db);
    $time = time();

    while (time() - $time < 5) {
        $newMsg = latestMsg($db);
        if ($newMsg && (($newMsg && !$msg) || ($newMsg['id'] !== $msg['id']))) {
            unset($newMsg['id']);
            echo json_encode($newMsg);
            break;
        }
        sleep(1);
    }
}
