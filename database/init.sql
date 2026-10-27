CREATE TABLE users (
    id VARCHAR(100) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    is_anonymous TINYINT(1) DEFAULT 0,
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE games (
    id VARCHAR(100) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    difficulty VARCHAR(20) DEFAULT 'medium',
    estimated_time INT DEFAULT 60,
    creator_id VARCHAR(100),
    creator_name VARCHAR(255),
    play_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id)
);

CREATE TABLE waypoints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id VARCHAR(100),
    order_index INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    question TEXT,
    answer VARCHAR(255),
    lat DECIMAL(10, 8) NOT NULL,
    lng DECIMAL(11, 8) NOT NULL,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

CREATE TABLE game_sessions (
    code VARCHAR(10) PRIMARY KEY,
    game_id VARCHAR(100) NOT NULL,
    host_id VARCHAR(100) NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (host_id) REFERENCES users(id)
);

CREATE TABLE game_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100),
    game_id VARCHAR(100),
    session_code VARCHAR(10) NULL,
    waypoints_completed INT DEFAULT 0,
    completed TINYINT(1) DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (game_id) REFERENCES games(id),
    FOREIGN KEY (session_code) REFERENCES game_sessions(code)
);

CREATE TABLE friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    friend_id VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_pair (user_id, friend_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (friend_id) REFERENCES users(id)
);