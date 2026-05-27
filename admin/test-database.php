<?php
// Quick database test - put this in admin folder temporarily
require_once 'auth_check.php';

echo "<h2>Database Connection Test</h2>";

// Test 1: Check if news_articles table exists
$result = $conn->query("SHOW TABLES LIKE 'news_articles'");
if ($result->num_rows > 0) {
    echo "✅ news_articles table EXISTS<br>";
} else {
    echo "❌ news_articles table DOES NOT EXIST<br>";
    echo "<strong>You need to run the news_table.sql file in phpMyAdmin!</strong><br>";
}

// Test 2: Check table structure
$result = $conn->query("DESCRIBE news_articles");
if ($result) {
    echo "<br><strong>Table Structure:</strong><br>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 3: Check if any articles exist
$result = $conn->query("SELECT COUNT(*) as count FROM news_articles");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<br><strong>Total articles in database:</strong> " . $row['count'] . "<br>";
}

// Test 4: Try a simple insert
echo "<br><h3>Testing Simple Insert...</h3>";
$test_title = "Test Article " . time();
$test_slug = "test-article-" . time();
$test_category = "General";
$test_excerpt = "This is a test excerpt";
$test_content = "<p>This is test content</p>";
$test_author = "Test Admin";
$test_date = date('Y-m-d');
$test_published = 1;
$test_image = "test-image.jpg";

$stmt = $conn->prepare("INSERT INTO news_articles (title, slug, category, excerpt, content, featured_image, author, published_date, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    echo "❌ Prepare failed: " . $conn->error . "<br>";
} else {
    $stmt->bind_param("ssssssssi", $test_title, $test_slug, $test_category, $test_excerpt, $test_content, $test_image, $test_author, $test_date, $test_published);
    
    if ($stmt->execute()) {
        echo "✅ Test insert SUCCESSFUL! New article ID: " . $stmt->insert_id . "<br>";
        echo "Now check the news_articles table in phpMyAdmin to see if the test article was added.<br>";
    } else {
        echo "❌ Execute failed: " . $stmt->error . "<br>";
        echo "Error code: " . $conn->errno . "<br>";
    }
    $stmt->close();
}

echo "<br><br><a href='manage-news.php'>Go to Manage News</a> | <a href='dashboard.php'>Dashboard</a>";
?>
