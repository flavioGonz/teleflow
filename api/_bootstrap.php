<?php
// api/_bootstrap.php — helper compartido para consolidar dup entre endpoints.
//
// USO:  require_once __DIR__ . '/_bootstrap.php';
//       tf_bootstrap(['auth' => 'admin']);   // 'admin' | 'agent' | 'any' | null
//
// Efectos:
//   - session_start() con params correctos (Lax, 8h)
//   - Content-Type: application/json; charset=utf-8
//   - Cache-Control: no-store
//   - Bloquea si no cumple auth (401 + json + exit)
//   - CORS opcional para llamadas desde apps mobile

if (defined("TF_BOOTSTRAPPED")) return;
define("TF_BOOTSTRAPPED", true);

function tf_json_out($payload, $status = 200) {
    http_response_code($status);
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    echo json_encode($payload);
    exit;
}

function tf_bootstrap($opts = []) {
    $opts = array_merge(["auth" => null, "cors" => false], $opts);

    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            "lifetime" => 28800,
            "path"     => "/",
            "samesite" => "Lax",
            "httponly" => true,
        ]);
        session_start();
    }

    if ($opts["cors"]) {
        $origin = $_SERVER["HTTP_ORIGIN"] ?? "";
        if ($origin) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Allow-Headers: Content-Type");
        }
        if (($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS") { http_response_code(204); exit; }
    }

    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store, no-cache, must-revalidate");

    switch ($opts["auth"]) {
        case "admin":
            if (empty($_SESSION["tf_user"])) tf_json_out(["ok" => false, "error" => "auth"], 401);
            break;
        case "agent":
            if (empty($_SESSION["agent_user"])) tf_json_out(["ok" => false, "error" => "sin_sesion"], 401);
            break;
        case "any":
            if (empty($_SESSION["tf_user"]) && empty($_SESSION["agent_user"])) tf_json_out(["ok" => false, "error" => "auth"], 401);
            break;
        case null:
            break;
        default:
            tf_json_out(["ok" => false, "error" => "invalid auth mode"], 500);
    }
}
