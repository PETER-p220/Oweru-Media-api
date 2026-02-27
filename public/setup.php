<?php
// Simple database setup script
try {
    // Create database directory if it doesn't exist
    $dbDir = dirname(__DIR__) . '/../database';
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }
    
    // Create SQLite database file
    $dbFile = $dbDir . '/database.sqlite';
    if (!file_exists($dbFile)) {
        touch($dbFile);
        chmod($dbFile, 0755);
    }
    
    // Test database connection
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS personal_access_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tokenable_type TEXT NOT NULL,
        tokenable_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        token TEXT UNIQUE NOT NULL,
        abilities TEXT,
        last_used_at DATETIME,
        expires_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        category TEXT NOT NULL,
        post_type TEXT DEFAULT 'Static',
        title TEXT NOT NULL,
        description TEXT,
        status TEXT DEFAULT 'pending',
        moderated_by INTEGER,
        moderation_note TEXT,
        ai_generated BOOLEAN DEFAULT 0,
        metadata TEXT,
        performance_score INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (moderated_by) REFERENCES users(id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS media (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        file_type TEXT NOT NULL,
        file_path TEXT,
        url TEXT,
        mime_type TEXT,
        file_size INTEGER,
        order_column INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id)
    )");
    
    echo "Database setup completed successfully!\n";
    echo "Tables created: users, personal_access_tokens, posts, media\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
