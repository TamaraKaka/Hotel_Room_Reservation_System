<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') {http_response_code(404); exit;}
require_once __DIR__ . '/../../includes/auth.php';
$email=strtolower(trim((string)getenv('HOTEL_ADMIN_EMAIL')));
$password=(string)getenv('HOTEL_ADMIN_PASSWORD');
$name=trim((string)(getenv('HOTEL_ADMIN_NAME') ?: 'Hotel Administrator'));
if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<12 || strlen($name)<2) {
    fwrite(STDERR, "Set HOTEL_ADMIN_EMAIL, HOTEL_ADMIN_PASSWORD (12+ chars), HOTEL_ADMIN_NAME environment variables.\n");
    exit(1);
}
try {
    $db=authDb();
    $q=$db->prepare("INSERT INTO Customers (full_name,email,password_hash,role) VALUES (?,?,?,'Admin')");
    $q->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
    echo "Admin account created for: $email\n";
} catch(PDOException $e) {
    if (($e->errorInfo[1]??null)==1062) fwrite(STDERR,"An account already exists with that email; no changes were made.\n");
    else fwrite(STDERR,"Database error. Verify the migration and database connection.\n");
    exit(1);
}
