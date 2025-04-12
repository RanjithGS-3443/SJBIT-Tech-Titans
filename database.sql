-- Create the database
CREATE DATABASE IF NOT EXISTS career_roadmap;
USE career_roadmap;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Skills table
CREATE TABLE IF NOT EXISTS skills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    skill_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT
);

-- User skills table
CREATE TABLE IF NOT EXISTS user_skills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    level ENUM('beginner', 'intermediate', 'advanced') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

-- Career goals table
CREATE TABLE IF NOT EXISTS career_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    required_skills TEXT,
    estimated_time VARCHAR(50),
    salary_range VARCHAR(100)
);

-- User career goals table
CREATE TABLE IF NOT EXISTS user_career_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    career_goal_id INT NOT NULL,
    target_date DATE,
    status ENUM('active', 'completed', 'abandoned') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (career_goal_id) REFERENCES career_goals(id) ON DELETE CASCADE
);

-- Resources table
CREATE TABLE IF NOT EXISTS resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    skill_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('video', 'pdf', 'link', 'course') NOT NULL,
    url VARCHAR(255) NOT NULL,
    platform VARCHAR(100),
    is_free BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

-- Roadmap table
CREATE TABLE IF NOT EXISTS roadmap (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    week INT NOT NULL,
    task_description TEXT NOT NULL,
    resource_id INT,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE SET NULL
);

-- Progress table
CREATE TABLE IF NOT EXISTS progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    roadmap_id INT NOT NULL,
    completion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (roadmap_id) REFERENCES roadmap(id) ON DELETE CASCADE
);

-- Create chat_history table
CREATE TABLE IF NOT EXISTS `chat_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `response` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `chat_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample skills
INSERT INTO skills (skill_name, category, description) VALUES
('HTML', 'Web Development', 'Hypertext Markup Language for creating web pages'),
('CSS', 'Web Development', 'Cascading Style Sheets for styling web pages'),
('JavaScript', 'Web Development', 'Programming language for web interactivity'),
('Python', 'Programming', 'General-purpose programming language'),
('Data Analysis', 'Data Science', 'Analyzing and interpreting data'),
('Machine Learning', 'Data Science', 'Building and training ML models');

-- Insert some sample career goals
INSERT INTO career_goals (name, description, required_skills, estimated_time, salary_range) VALUES
('Web Developer', 'Front-end and back-end web development', 'HTML,CSS,JavaScript', '6-12 months', '$50,000 - $100,000'),
('Data Analyst', 'Data analysis and visualization', 'Python,Data Analysis', '4-8 months', '$45,000 - $85,000'),
('Machine Learning Engineer', 'Building ML models and systems', 'Python,Machine Learning', '12-18 months', '$80,000 - $150,000'); 