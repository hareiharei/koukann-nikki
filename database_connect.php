<?php
function dbc()
{
    global $pdo;
    global $error_message;
    //データベース接続　*部分にはユーザー名、パスワード、データベース名を入力
    try {
        $dsn = 'mysql:dbname=**********;host=localhost';
        $user = '*********';
        $password = '**********';
        $pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    } catch (PDOException $e) {
        $error_message = "データベース接続中にエラーが発生しました";
    }
}
