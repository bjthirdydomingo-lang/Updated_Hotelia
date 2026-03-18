
<?php
// Per-File Security Checker for PHP Capstone (Level 2)

// target file
$file = __DIR__ . '/modules/guest/rooms.php';

// security checks with context hints
$checks = [
    'Prepared Statements' => [
        'pattern' => '/(->prepare\s*\(|prepare\s*\()/i',
        'expected' => 'Should exist if this file queries the database.'
    ],
    'Password Hashing' => [
        'pattern' => '/password_hash\s*\(/i',
        'expected' => 'Should exist only in registration or password reset pages.'
    ],
    'Password Verify' => [
        'pattern' => '/password_verify\s*\(/i',
        'expected' => 'Should exist only in login pages.'
    ],
    'CSRF Token' => [
        'pattern' => '/\$_SESSION\[.*csrf.*\]/i',
        'expected' => 'Should exist if this file handles form submissions that change data.'
    ],
    'Session Regenerate' => [
        'pattern' => '/session_regenerate_id\s*\(/i',
        'expected' => 'Should exist only in login files after successful authentication.'
    ],
    'Input Sanitization' => [
        'pattern' => '/htmlspecialchars\s*\(/i',
        'expected' => 'Should exist if this file outputs user data into HTML.'
    ],
    'Role-based Access' => [
        'pattern' => '/\$_SESSION\[.*role.*\]/i',
        'expected' => 'Should exist if this file restricts access to staff/admin.'
    ],
];

// check file
if (!file_exists($file)) {
    die("File not found: $file" . PHP_EOL);
}

$content = file_get_contents($file);

echo "Checking: " . $file . PHP_EOL;

foreach ($checks as $label => $data) {
    if (preg_match($data['pattern'], $content)) {
        echo "  [OK] Found $label" . PHP_EOL;
    } else {
        echo "  [??] Missing $label — " . $data['expected'] . PHP_EOL;
    }
}
?>
