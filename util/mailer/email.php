<?php

function send_email($to, $subject, $body)
{
    $config = config('email');

    if (empty($config) || empty($config['driver'])) {
        log_notice('email_send_failed', 'email config not found, email not sent: ' . $to);
        return false;
    }

    $driver = $config['driver'];

    if ($driver === 'smtp') {
        return send_email_smtp($config, $to, $subject, $body);
    }

    if ($driver === 'mail') {
        return send_email_mail($config, $to, $subject, $body);
    }

    if ($driver === 'log') {
        return send_email_log($config, $to, $subject, $body);
    }

    log_notice('email_unsupported_driver', 'unsupported email driver: ' . $driver);
    return false;
}

function send_email_smtp($config, $to, $subject, $body)
{
    if (empty($config['smtp']['host'])) {
        log_notice('email_smtp_not_configured', 'smtp host not configured');
        return false;
    }

    $host = $config['smtp']['host'];
    $port = $config['smtp']['port'] ?? 25;
    $username = $config['smtp']['username'] ?? '';
    $password = $config['smtp']['password'] ?? '';
    $encryption = $config['smtp']['encryption'] ?? '';
    $from = $config['smtp']['from'] ?? $config['from'] ?? $username ?: 'noreply@localhost';
    $from_name = $config['smtp']['from_name'] ?? $config['from_name'] ?? '';

    $crlf = "\r\n";
    $timeout = 10;

    $connect_host = $host;
    $connect_port = $port;

    if ($encryption === 'ssl') {
        $connect_host = 'ssl://' . $host;
    } elseif ($encryption === 'tls') {
        $connect_host = 'tls://' . $host;
    }

    $socket = @fsockopen($connect_host, $connect_port, $errno, $errstr, $timeout);

    if (!$socket) {
        log_notice('email_smtp_connect_failed', 'smtp connect failed: ' . $errstr);
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $read_all = function () use ($socket) {
        $lines = '';
        while ($line = fgets($socket, 515)) {
            $info = stream_get_meta_data($socket);
            if ($info['timed_out']) {
                return false;
            }
            $lines .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $lines;
    };

    $send_command = function ($command, $expected_code = 250) use ($socket, $read_all) {
        fwrite($socket, $command . "\r\n");
        $response = $read_all();

        if ($response === false) {
            return false;
        }

        $code = (int)substr($response, 0, 3);
        return $code === $expected_code;
    };

    $read_all();

    if (!$send_command('EHLO localhost')) {
        $send_command('HELO localhost');
    }

    if (!empty($username) && !empty($password)) {
        if (!$send_command('AUTH LOGIN', 334)) {
            log_notice('email_smtp_auth_failed', 'smtp AUTH LOGIN not accepted');
            fclose($socket);
            return false;
        }

        if (!$send_command(base64_encode($username), 334)) {
            log_notice('email_smtp_auth_failed', 'smtp username rejected');
            fclose($socket);
            return false;
        }

        if (!$send_command(base64_encode($password), 235)) {
            log_notice('email_smtp_auth_failed', 'smtp password rejected');
            fclose($socket);
            return false;
        }
    }

    $send_command('MAIL FROM: <' . $from . '>');
    $send_command('RCPT TO: <' . $to . '>');
    $send_command('DATA');

    $from_display = $from_name ? $from_name . ' <' . $from . '>' : $from;
    $headers = "From: " . $from_display . $crlf;
    $headers .= "To: " . $to . $crlf;
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=" . $crlf;
    $headers .= "Content-Type: text/plain; charset=utf-8" . $crlf;
    $headers .= "Content-Transfer-Encoding: base64" . $crlf;
    $headers .= "MIME-Version: 1.0" . $crlf;

    $message = $headers . $crlf . chunk_split(base64_encode($body));

    fwrite($socket, $message . "\r\n.\r\n");
    $read_all();

    $send_command('QUIT', 221);
    fclose($socket);

    return true;
}

function send_email_mail($config, $to, $subject, $body)
{
    $from = $config['from'] ?? 'noreply@localhost';
    $from_name = $config['from_name'] ?? '';

    $headers = 'From: ' . ($from_name ? $from_name . ' <' . $from . '>' : $from) . "\n";
    $headers .= 'Content-Type: text/plain; charset=utf-8' . "\n";

    $result = @mail($to, $subject, $body, $headers);

    if (!$result) {
        log_notice('email_mail_failed', 'mail() function failed for: ' . $to);
    }

    return $result;
}

function send_email_log($config, $to, $subject, $body)
{
    $log_path = $config['log_path'] ?? '/tmp/email.log';
    $log_message = '[' . datetime() . '] To: ' . $to . ' | Subject: ' . $subject . ' | Body: ' . $body . "\n";

    $result = @file_put_contents($log_path, $log_message, FILE_APPEND | LOCK_EX);

    if ($result === false) {
        log_notice('email_log_failed', 'cannot write to email log: ' . $log_path);
    }

    return true;
}
