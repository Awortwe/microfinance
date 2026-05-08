<?php
require_once 'config/init.php';

echo "<h2>Password Reset Tool</h2>";

$db = getDB();

// Delete all existing users first
$db->query("DELETE FROM users");
$db->execute();
echo "<p>✅ Old users deleted</p>";

// Create fresh users with properly hashed passwords
$users = [
    [
        'username' => 'admin',
        'password' => 'admin123',
        'full_name' => 'System Administrator',
        'email' => 'admin@nkwa.com',
        'phone' => '0240000000',
        'user_type' => 'admin'
    ],
    [
        'username' => 'employee1',
        'password' => 'employee123',
        'full_name' => 'John Doe',
        'email' => 'john@nkwa.com',
        'phone' => '0240000001',
        'user_type' => 'employee'
    ],
    [
        'username' => 'employee2',
        'password' => 'employee123',
        'full_name' => 'Jane Smith',
        'email' => 'jane@nkwa.com',
        'phone' => '0240000002',
        'user_type' => 'employee'
    ]
];

foreach ($users as $user) {
    // Hash the password properly with PHP's bcrypt
    $hashed_password = password_hash($user['password'], PASSWORD_BCRYPT, ['cost' => 10]);
    
    // Insert the user
    $db->query("INSERT INTO users (username, password, full_name, email, phone, user_type) 
                VALUES (:username, :password, :full_name, :email, :phone, :user_type)");
    $db->bindMultiple([
        ':username' => $user['username'],
        ':password' => $hashed_password,
        ':full_name' => $user['full_name'],
        ':email' => $user['email'],
        ':phone' => $user['phone'],
        ':user_type' => $user['user_type']
    ]);
    $db->execute();
    
    // Verify the password works
    $db->query("SELECT password FROM users WHERE username = :username");
    $db->bind(':username', $user['username']);
    $stored = $db->single();
    
    $verify = password_verify($user['password'], $stored['password']);
    
    echo "<p>";
    echo "User: <strong>{$user['username']}</strong><br>";
    echo "Password: <strong>{$user['password']}</strong><br>";
    echo "Hash: <code>" . substr($stored['password'], 0, 30) . "...</code><br>";
    echo "Verification: " . ($verify ? '<span style="color:green">✅ Password works!</span>' : '<span style="color:red">❌ Password FAILED!</span>');
    echo "</p>";
}

echo "<hr>";
echo "<h3>Login Credentials:</h3>";
echo "<ul>";
echo "<li><b>Admin:</b> username: <code>admin</code> | password: <code>admin123</code></li>";
echo "<li><b>Employee 1:</b> username: <code>employee1</code> | password: <code>employee123</code></li>";
echo "<li><b>Employee 2:</b> username: <code>employee2</code> | password: <code>employee123</code></li>";
echo "</ul>";

echo "<p style='color:red;'><b>⚠️ DELETE THIS FILE (reset_passwords.php) AFTER USE FOR SECURITY!</b></p>";
echo "<p><a href='index.php'>Go to Login Page</a></p>";
?>