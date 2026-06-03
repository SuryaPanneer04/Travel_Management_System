<?php
/**
 * Simple SMTP Mailer function
 * Supports TLS and Authentication dynamically configured from DB
 */
function sendMail($to, $subject, $body) {
    global $pdo;

    // Default configuration (fallback)
    $host = 'smtp.gmail.com';
    $port = 587;
    $user = 'suryapanneer04@gmail.com';
    $pass = 'xlwv dltg vhou bars';
    $from = 'suryapanneer04@gmail.com';
    $fromName = 'VMS';
    $secure = 'TLS';
    $footer = 'Regards, Team VMS';

    try {
        if (!isset($pdo)) {
            require_once __DIR__ . '/db.php';
        }
        $stmt = $pdo->query("SELECT * FROM mail_settings LIMIT 1");
        $settings = $stmt->fetch();
        if ($settings) {
            $host = $settings['smtp_host'];
            $port = intval($settings['smtp_port']);
            $user = $settings['smtp_user'];
            $pass = $settings['smtp_pass'];
            $from = $settings['from_email'];
            $fromName = $settings['from_name'];
            $secure = $settings['smtp_secure'];
            $footer = $settings['global_footer'];
        }
    } catch (Exception $e) {
        error_log("SMTP settings DB load failed: " . $e->getMessage());
    }

    // Append footer if set
    if (!empty($footer)) {
        $body .= "<br><br>" . $footer;
    }

    try {
        $protocol = 'tcp';
        if (strtoupper($secure) === 'SSL') {
            $protocol = 'ssl';
        }

        $socket = stream_socket_client("$protocol://$host:$port", $errno, $errstr, 30);
        if (!$socket) throw new Exception("Could not connect to $host: $errstr");

        $getResponse = function($socket) {
            $res = "";
            while($str = fgets($socket, 515)) {
                $res .= $str;
                if(substr($str, 3, 1) == " ") break;
            }
            return $res;
        };

        $sendCommand = function($socket, $cmd) use ($getResponse) {
            fputs($socket, $cmd . "\r\n");
            $response = $getResponse($socket);
            // Log response for debugging
            error_log("SMTP Response: " . $response);
            return $response;
        };

        $getResponse($socket); // 220
        $sendCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        
        if (strtoupper($secure) === 'TLS') {
            $sendCommand($socket, "STARTTLS"); // 220
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Could not enable TLS");
            }
            $sendCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }

        $sendCommand($socket, "AUTH LOGIN"); // 334
        $sendCommand($socket, base64_encode($user)); // 334
        $sendCommand($socket, base64_encode($pass)); // 235

        $sendCommand($socket, "MAIL FROM: <$from>"); // 250
        $sendCommand($socket, "RCPT TO: <$to>"); // 250
        $sendCommand($socket, "DATA"); // 354

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $fromName <$from>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";

        $sendCommand($socket, $headers . "\r\n" . $body . "\r\n."); // 250
        $sendCommand($socket, "QUIT"); // 221
        
        fclose($socket);
        return true;
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}
?>
