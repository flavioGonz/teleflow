<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tf_user']) && ($_GET['action'] ?? '') !== 'login') {
    http_response_code(403); exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'get_recordings') {
    $search = $_GET['q'] ?? '';
    try {
        $db = new PDO('mysql:host=localhost;dbname=asteriskcdrdb', 'root', 'Sildan.1329');
        $query = "SELECT calldate, clid, src, dst, duration, recordingfile FROM cdr WHERE recordingfile != ''";
        
        if ($search) {
            $query .= " AND (src LIKE :q OR dst LIKE :q OR clid LIKE :q)";
        }
        
        $query .= " ORDER BY calldate DESC LIMIT 30";
        $stmt = $db->prepare($query);
        if ($search) {
            $stmt->bindValue(':q', "%$search%");
        }
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch(Exception $e) {
        echo json_encode([]);
    }
    exit;
}
?>
