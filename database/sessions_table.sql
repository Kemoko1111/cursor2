-- Sessions table for scheduling mentor-mentee sessions
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    mentee_id INT NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME NOT NULL,
    duration INT NOT NULL DEFAULT 60 COMMENT 'Duration in minutes',
    topic VARCHAR(255) NOT NULL,
    notes TEXT,
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    meeting_link VARCHAR(500),
    meeting_notes TEXT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mentor_sessions (mentor_id, session_date, status),
    INDEX idx_mentee_sessions (mentee_id, session_date, status),
    INDEX idx_session_datetime (session_date, session_time),
    INDEX idx_status (status)
);