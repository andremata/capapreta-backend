<?php

$plain_password = '123';
$new_hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

echo "Generated Hash: " . $new_hashed_password . "<br>";

if (password_verify($plain_password, $new_hashed_password)) {
    echo "Password verification successful with newly generated hash!";
} else {
    echo "Password verification failed with newly generated hash.";
}

?>