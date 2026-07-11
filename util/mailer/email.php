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
    $from = $config['smtp']['from'] ?? ($username ?: 'noreply@localhost');

    $crlf = "\r\n";
    $timeout = 10;

    $connect_host = $host;
    $connect_port = $port;

    if ($encryption === 'ssl') {
        if ($port === 25 || $port === 587) {
            $connect_host = 'ssl://' . $host;
            $connect_port = 465;
        } else {
            $connect_host = 'ssl://' . $host;
        }
    }

    $socket = @fsockopen($connect_host, $connect_port, $errno, $errstr, $timeout);

    if (!$socket) {
        log_notice('email_smtp_connect_failed', 'smtp connect failed: ' . $errstr);
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $read_line = function () use ($socket) {
        $line = fgets($socket, 515);
        $info = stream_get_meta_data($socket);
        if ($info['timed_out']) {
            return false;
        }
        return $line;
    };

    $send_command = function ($command, $expected_code = 250) use ($socket, $read_line) {
        fwrite($socket, $command . "\r\n");
        $response = $read_line();

        if ($response === false) {
            return false;
        }

        $code = (int)substr($response, 0, 3);
        return $code === $expected_code;
    };

    $read_line();

    $send_command('EHLO localhost');
    $send_command('MAIL FROM: <' . $from . '>');
    $send_command('RCPT TO: <' . $to . '>');
    $send_command('DATA');

    $boundary = '----=_Part_' . uniqid();
    $headers = "From: " . $from . $crlf;
    $headers .= "To: " . $to . $crlf;
    $headers .= "Subject: " . $subject . $crlf;
    $headers .= "Content-Type: text/plain; charset=utf-8" . $crlf;
    $headers .= "MIME-Version: 1.0" . $crlf;

    $message = $headers . $crlf . $body;

    fwrite($socket, $message . "\r\n.\r\n");
    $read_line();

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
