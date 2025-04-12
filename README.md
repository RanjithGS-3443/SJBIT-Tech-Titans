# Career Roadmap Generator

A comprehensive web application that helps users plan and track their career development journey. Built with PHP, MySQL, and modern web technologies.

## Features

- **User Authentication**
  - Secure login and registration
  - Password hashing and validation
  - Session management
  - Profile management

- **Skill Assessment**
  - Evaluate current skill levels
  - Track skill progress over time
  - Categorized skill management
  - Visual skill representation

- **Career Goals**
  - Set and manage career objectives
  - Track goal progress
  - Estimated timelines
  - Salary range information

- **Personalized Roadmap**
  - Custom learning path generation
  - Weekly task organization
  - Progress tracking
  - Resource recommendations

- **Learning Resources**
  - Curated learning materials
  - Multiple resource types (videos, PDFs, courses)
  - Filter by skill and type
  - Free and premium content

- **Progress Tracking**
  - Visual progress indicators
  - Achievement tracking
  - Timeline of activities
  - Statistical insights

## Technologies Used

- **Backend**
  - PHP 7.4+
  - MySQL 5.7+
  - PDO for database operations

- **Frontend**
  - HTML5
  - CSS3 with custom properties
  - JavaScript (ES6+)
  - Bootstrap 5.1.3
  - Bootstrap Icons
  - Google Fonts (Nunito)

- **Features**
  - Responsive design
  - Modern UI/UX
  - Animations and transitions
  - Form validation
  - AJAX functionality
  - Toast notifications
  - Loading indicators

## Installation

1. **Prerequisites**
   - XAMPP (or similar PHP development environment)
   - MySQL database
   - Web browser

2. **Setup**
   ```bash
   # Clone the repository to your htdocs folder
   git clone https://github.com/yourusername/career-roadmap.git
   
   # Navigate to the project directory
   cd career-roadmap
   
   # Import the database
   mysql -u root -p < database.sql
   ```

3. **Configuration**
   - Open `config/database.php`
   - Update database credentials if needed:
     ```php
     $host = 'localhost';
     $dbname = 'career_roadmap';
     $username = 'root';
     $password = '';
     ```

4. **Access**
   - Start XAMPP (Apache and MySQL)
   - Open your browser
   - Navigate to `http://localhost/career-roadmap`

## Project Structure

```
career-roadmap/
├── config/
│   └── database.php
├── css/
│   └── style.css
├── js/
│   └── main.js
├── includes/
│   ├── header.php
│   └── footer.php
├── login.php
├── register.php
├── dashboard.php
├── skill_assessment.php
├── career_goal.php
├── roadmap.php
├── progress.php
├── resources.php
├── profile.php
├── logout.php
├── database.sql
└── README.md
```

## Database Schema

```sql
users
- id (INT, PRIMARY KEY)
- name (VARCHAR)
- email (VARCHAR, UNIQUE)
- password (VARCHAR)
- created_at (TIMESTAMP)

skills
- id (INT, PRIMARY KEY)
- skill_name (VARCHAR)
- category (VARCHAR)
- description (TEXT)

user_skills
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY)
- skill_id (INT, FOREIGN KEY)
- level (ENUM)

career_goals
- id (INT, PRIMARY KEY)
- name (VARCHAR)
- description (TEXT)
- required_skills (TEXT)
- estimated_time (VARCHAR)
- salary_range (VARCHAR)

user_career_goals
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY)
- career_goal_id (INT, FOREIGN KEY)
- target_date (DATE)
- status (ENUM)

resources
- id (INT, PRIMARY KEY)
- skill_id (INT, FOREIGN KEY)
- title (VARCHAR)
- description (TEXT)
- type (ENUM)
- url (VARCHAR)
- platform (VARCHAR)
- is_free (BOOLEAN)

roadmap
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY)
- week (INT)
- task_description (TEXT)
- resource_id (INT, FOREIGN KEY)
- status (ENUM)

progress
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY)
- roadmap_id (INT, FOREIGN KEY)
- completion_date (TIMESTAMP)
- notes (TEXT)
```

## Usage

1. **Registration/Login**
   - Create a new account or login with existing credentials
   - Fill in required information

2. **Skill Assessment**
   - Navigate to the Skill Assessment page
   - Rate your proficiency in various skills
   - Save your assessment

3. **Career Goals**
   - Set your career objectives
   - Specify target dates
   - Track progress

4. **Roadmap**
   - View your personalized learning path
   - Complete weekly tasks
   - Access recommended resources

5. **Progress Tracking**
   - Monitor your advancement
   - View achievements
   - Track completed tasks

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- Bootstrap team for the amazing framework
- Bootstrap Icons for the beautiful icon set
- Google Fonts for the Nunito font family
- XAMPP team for the development environment 