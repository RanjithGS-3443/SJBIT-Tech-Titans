<?php
require_once 'database.php';

try {
    // Add profile_photo column to users table
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT 'assets/img/default-profile.png'");
    echo "Added profile_photo column to users table\n";
    
    // Update existing users
    $pdo->exec("UPDATE users SET profile_photo = 'assets/img/default-profile.png' WHERE profile_photo IS NULL");
    echo "Updated existing users with default profile photo\n";
    
    // Add resume_path column to users table
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS resume_path VARCHAR(255) NULL");
    echo "Added resume_path column to users table\n";

    // Create skills table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        skill_name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Created skills table\n";

    // Create user_skills table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        skill_id INT NOT NULL,
        level ENUM('beginner', 'intermediate', 'advanced') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
    )");
    echo "Created user_skills table\n";
    
    // Create quiz_questions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS quiz_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        skill_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type ENUM('multiple_choice', 'true_false') NOT NULL,
        correct_answer VARCHAR(255) NOT NULL,
        options TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
    )");
    echo "Created quiz_questions table\n";
    
    // Create quiz_results table
    $pdo->exec("CREATE TABLE IF NOT EXISTS quiz_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        skill_id INT NOT NULL,
        score INT NOT NULL,
        total_questions INT NOT NULL,
        percentage DECIMAL(5,2) NOT NULL,
        level ENUM('beginner', 'intermediate', 'advanced') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
    )");
    echo "Created quiz_results table\n";
    
    // Create portfolio_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS portfolio_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_url VARCHAR(255),
        project_url VARCHAR(255),
        completion_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Created portfolio_items table\n";
    
    // Create portfolio_skills table
    $pdo->exec("CREATE TABLE IF NOT EXISTS portfolio_skills (
        portfolio_id INT NOT NULL,
        skill_id INT NOT NULL,
        PRIMARY KEY (portfolio_id, skill_id),
        FOREIGN KEY (portfolio_id) REFERENCES portfolio_items(id) ON DELETE CASCADE,
        FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
    )");
    echo "Created portfolio_skills table\n";
    
    // Create user_courses table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_name VARCHAR(255) NOT NULL,
        institution VARCHAR(255) NOT NULL,
        completion_date DATE NOT NULL,
        certificate_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Created user_courses table\n";

    // Create resources table
    $pdo->exec("CREATE TABLE IF NOT EXISTS resources (
        id INT AUTO_INCREMENT PRIMARY KEY,
        skill_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        type ENUM('video', 'pdf') NOT NULL,
        url VARCHAR(255) NOT NULL,
        thumbnail_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
    )");
    echo "Created resources table\n";

    // Add some sample engineering skills if skills table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM skills");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO skills (skill_name, category, description) VALUES
            ('Object-Oriented Programming', 'Programming', 'Understanding of OOP concepts and principles'),
            ('Data Structures', 'Programming', 'Knowledge of fundamental data structures'),
            ('Algorithms', 'Programming', 'Understanding of algorithm design and analysis'),
            ('Database Design', 'Database', 'Skills in designing and optimizing databases'),
            ('Web Development', 'Web', 'Experience in building web applications'),
            ('Data Analysis', 'Data Science', 'Skills in analyzing and interpreting complex data sets')
        ");
        echo "Added sample engineering skills\n";
    }
    
    // Add some sample engineering quiz questions
    $stmt = $pdo->query("SELECT COUNT(*) FROM quiz_questions");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO quiz_questions (skill_id, question_text, question_type, correct_answer, options) VALUES
            -- Object-Oriented Programming Questions
            (1, 'What is encapsulation in OOP?', 'multiple_choice', 'Bundling data and methods that operate on that data within a single unit', '{\"options\": [\"Bundling data and methods that operate on that data within a single unit\", \"Creating multiple classes\", \"Writing clean code\", \"Using global variables\"]}'),
            (1, 'What is inheritance in OOP?', 'multiple_choice', 'A mechanism that allows a class to inherit properties and methods from another class', '{\"options\": [\"A mechanism that allows a class to inherit properties and methods from another class\", \"Creating objects\", \"Writing functions\", \"Using variables\"]}'),
            (1, 'What is polymorphism in OOP?', 'multiple_choice', 'The ability of different classes to be treated as instances of the same class through base class inheritance', '{\"options\": [\"The ability of different classes to be treated as instances of the same class through base class inheritance\", \"Creating multiple objects\", \"Using different variables\", \"Writing multiple functions\"]}'),
            (1, 'What is abstraction in OOP?', 'multiple_choice', 'Hiding complex implementation details and showing only necessary features of an object', '{\"options\": [\"Hiding complex implementation details and showing only necessary features of an object\", \"Using concrete classes\", \"Creating objects\", \"Writing code\"]}'),
            (1, 'What is a constructor?', 'multiple_choice', 'A special method that is automatically called when an object is created', '{\"options\": [\"A special method that is automatically called when an object is created\", \"A regular method\", \"A variable\", \"A class name\"]}'),
            (1, 'What is method overriding?', 'multiple_choice', 'Providing a specific implementation of a method in a subclass that is already defined in its parent class', '{\"options\": [\"Providing a specific implementation of a method in a subclass that is already defined in its parent class\", \"Creating new methods\", \"Deleting methods\", \"Renaming methods\"]}'),
            (1, 'What is an interface in OOP?', 'multiple_choice', 'A contract that specifies what methods a class must implement', '{\"options\": [\"A contract that specifies what methods a class must implement\", \"A concrete class\", \"A variable type\", \"A method name\"]}'),
            (1, 'What is the difference between composition and inheritance?', 'multiple_choice', 'Composition is a has-a relationship while inheritance is an is-a relationship', '{\"options\": [\"Composition is a has-a relationship while inheritance is an is-a relationship\", \"They are the same thing\", \"Composition is faster than inheritance\", \"Inheritance is newer than composition\"]}'),
            (1, 'What is a static method?', 'multiple_choice', 'A method that belongs to the class rather than an instance of the class', '{\"options\": [\"A method that belongs to the class rather than an instance of the class\", \"A private method\", \"A public method\", \"An instance method\"]}'),
            (1, 'What is the purpose of the final keyword in OOP?', 'multiple_choice', 'To prevent inheritance and method overriding', '{\"options\": [\"To prevent inheritance and method overriding\", \"To create objects\", \"To declare variables\", \"To write methods\"]}'),

            -- Data Structures Questions
            (2, 'What is the time complexity of searching in a binary search tree?', 'multiple_choice', 'O(log n)', '{\"options\": [\"O(log n)\", \"O(n)\", \"O(1)\", \"O(n^2)\"]}'),
            (2, 'What is a hash table collision?', 'multiple_choice', 'When two different keys hash to the same index', '{\"options\": [\"When two different keys hash to the same index\", \"When a key is not found\", \"When the table is full\", \"When the hash function fails\"]}'),
            (2, 'What is the main advantage of a linked list over an array?', 'multiple_choice', 'Dynamic size and efficient insertion/deletion', '{\"options\": [\"Dynamic size and efficient insertion/deletion\", \"Faster access time\", \"Less memory usage\", \"Better cache performance\"]}'),
            (2, 'What is a stack used for?', 'multiple_choice', 'Managing function calls and undo operations', '{\"options\": [\"Managing function calls and undo operations\", \"Sorting data\", \"Searching elements\", \"Storing data permanently\"]}'),
            (2, 'What is the time complexity of quicksort in the worst case?', 'multiple_choice', 'O(n^2)', '{\"options\": [\"O(n^2)\", \"O(n)\", \"O(log n)\", \"O(n log n)\"]}'),
            (2, 'What is a queue used for?', 'multiple_choice', 'Managing tasks in order of arrival', '{\"options\": [\"Managing tasks in order of arrival\", \"Storing sorted data\", \"Quick searching\", \"Memory management\"]}'),
            (2, 'What is a binary heap?', 'multiple_choice', 'A complete binary tree with heap property', '{\"options\": [\"A complete binary tree with heap property\", \"A linked list\", \"A sorting algorithm\", \"A search tree\"]}'),
            (2, 'What is the space complexity of a binary search tree?', 'multiple_choice', 'O(n)', '{\"options\": [\"O(n)\", \"O(1)\", \"O(log n)\", \"O(n^2)\"]}'),
            (2, 'What is a graph used for?', 'multiple_choice', 'Representing relationships between objects', '{\"options\": [\"Representing relationships between objects\", \"Storing sorted data\", \"Quick searching\", \"Memory management\"]}'),
            (2, 'What is the difference between BFS and DFS?', 'multiple_choice', 'BFS explores breadth-wise while DFS explores depth-wise', '{\"options\": [\"BFS explores breadth-wise while DFS explores depth-wise\", \"They are the same\", \"BFS is always faster\", \"DFS uses less memory\"]}'),

            -- Algorithms Questions
            (3, 'What is dynamic programming?', 'multiple_choice', 'Solving complex problems by breaking them into simpler subproblems', '{\"options\": [\"Solving complex problems by breaking them into simpler subproblems\", \"Writing dynamic code\", \"Using multiple loops\", \"Creating dynamic arrays\"]}'),
            (3, 'What is the difference between greedy and dynamic programming algorithms?', 'multiple_choice', 'Greedy makes locally optimal choices while dynamic programming finds global optimal', '{\"options\": [\"Greedy makes locally optimal choices while dynamic programming finds global optimal\", \"They are the same\", \"Greedy is always faster\", \"Dynamic programming uses less memory\"]}'),
            (3, 'What is the time complexity of binary search?', 'multiple_choice', 'O(log n)', '{\"options\": [\"O(log n)\", \"O(n)\", \"O(1)\", \"O(n^2)\"]}'),
            (3, 'What is a divide and conquer algorithm?', 'multiple_choice', 'An algorithm that breaks a problem into smaller subproblems, solves them, and combines the results', '{\"options\": [\"An algorithm that breaks a problem into smaller subproblems, solves them, and combines the results\", \"An algorithm that uses loops\", \"A sorting algorithm\", \"A searching algorithm\"]}'),
            (3, 'What is the purpose of Big O notation?', 'multiple_choice', 'To describe the upper bound of the growth rate of an algorithm', '{\"options\": [\"To describe the upper bound of the growth rate of an algorithm\", \"To measure code quality\", \"To count lines of code\", \"To measure memory usage\"]}'),
            (3, 'What is recursion?', 'multiple_choice', 'A function calling itself to solve a smaller instance of the same problem', '{\"options\": [\"A function calling itself to solve a smaller instance of the same problem\", \"Using multiple loops\", \"Creating objects\", \"Writing iterative code\"]}'),
            (3, 'What is memoization?', 'multiple_choice', 'Storing results of expensive function calls to avoid redundant computations', '{\"options\": [\"Storing results of expensive function calls to avoid redundant computations\", \"Writing comments\", \"Using memory\", \"Creating variables\"]}'),
            (3, 'What is the time complexity of bubble sort?', 'multiple_choice', 'O(n^2)', '{\"options\": [\"O(n^2)\", \"O(n)\", \"O(log n)\", \"O(n log n)\"]}'),
            (3, 'What is an in-place algorithm?', 'multiple_choice', 'An algorithm that transforms input using no extra space or O(1) extra space', '{\"options\": [\"An algorithm that transforms input using no extra space or O(1) extra space\", \"An algorithm that uses extra space\", \"A fast algorithm\", \"A recursive algorithm\"]}'),
            (3, 'What is the difference between stable and unstable sorting algorithms?', 'multiple_choice', 'Stable sort maintains relative order of equal elements while unstable sort might not', '{\"options\": [\"Stable sort maintains relative order of equal elements while unstable sort might not\", \"Stable sort is faster\", \"Unstable sort uses less memory\", \"They are the same\"]}'),

            -- Database Design Questions
            (4, 'What is normalization in database design?', 'multiple_choice', 'The process of organizing data to minimize redundancy', '{\"options\": [\"The process of organizing data to minimize redundancy\", \"Creating tables\", \"Writing queries\", \"Adding indexes\"]}'),
            (4, 'What is a primary key?', 'multiple_choice', 'A column or combination of columns that uniquely identifies each row', '{\"options\": [\"A column or combination of columns that uniquely identifies each row\", \"Any column in a table\", \"A foreign key\", \"An index\"]}'),
            (4, 'What is a foreign key?', 'multiple_choice', 'A column that creates a relationship between two tables', '{\"options\": [\"A column that creates a relationship between two tables\", \"A primary key\", \"An index\", \"A constraint\"]}'),
            (4, 'What is an index in a database?', 'multiple_choice', 'A data structure that improves the speed of data retrieval', '{\"options\": [\"A data structure that improves the speed of data retrieval\", \"A table column\", \"A primary key\", \"A foreign key\"]}'),
            (4, 'What is ACID in database transactions?', 'multiple_choice', 'Atomicity, Consistency, Isolation, Durability', '{\"options\": [\"Atomicity, Consistency, Isolation, Durability\", \"A type of query\", \"A database design pattern\", \"A constraint type\"]}'),
            (4, 'What is denormalization?', 'multiple_choice', 'Adding redundant data to improve query performance', '{\"options\": [\"Adding redundant data to improve query performance\", \"Removing data\", \"Normalizing data\", \"Creating indexes\"]}'),
            (4, 'What is a stored procedure?', 'multiple_choice', 'A prepared SQL code that can be saved and reused', '{\"options\": [\"A prepared SQL code that can be saved and reused\", \"A table type\", \"A column constraint\", \"A data type\"]}'),
            (4, 'What is a trigger in a database?', 'multiple_choice', 'A special procedure that automatically runs when certain events occur', '{\"options\": [\"A special procedure that automatically runs when certain events occur\", \"A type of query\", \"A table constraint\", \"A data validation\"]}'),
            (4, 'What is the difference between INNER and LEFT JOIN?', 'multiple_choice', 'INNER JOIN returns matching rows while LEFT JOIN includes all rows from the left table', '{\"options\": [\"INNER JOIN returns matching rows while LEFT JOIN includes all rows from the left table\", \"They are the same\", \"INNER JOIN is faster\", \"LEFT JOIN uses less memory\"]}'),
            (4, 'What is database sharding?', 'multiple_choice', 'Splitting a database into smaller parts across multiple servers', '{\"options\": [\"Splitting a database into smaller parts across multiple servers\", \"Creating backups\", \"Adding indexes\", \"Writing queries\"]}'),

            -- Web Development Questions
            (5, 'What is responsive web design?', 'multiple_choice', 'Design that adapts to different screen sizes and devices', '{\"options\": [\"Design that adapts to different screen sizes and devices\", \"Fast loading websites\", \"Using JavaScript\", \"Writing HTML\"]}'),
            (5, 'What is the difference between HTTP and HTTPS?', 'multiple_choice', 'HTTPS is secure and encrypted while HTTP is not', '{\"options\": [\"HTTPS is secure and encrypted while HTTP is not\", \"They are the same\", \"HTTP is newer\", \"HTTPS is faster\"]}'),
            (5, 'What is AJAX?', 'multiple_choice', 'Asynchronous JavaScript and XML for updating parts of a web page', '{\"options\": [\"Asynchronous JavaScript and XML for updating parts of a web page\", \"A programming language\", \"A database\", \"A web server\"]}'),
            (5, 'What is CSS flexbox?', 'multiple_choice', 'A layout model for arranging elements in a flexible way', '{\"options\": [\"A layout model for arranging elements in a flexible way\", \"A JavaScript framework\", \"A database type\", \"A programming language\"]}'),
            (5, 'What is the purpose of a REST API?', 'multiple_choice', 'To provide a standardized way for applications to communicate over HTTP', '{\"options\": [\"To provide a standardized way for applications to communicate over HTTP\", \"To create databases\", \"To style web pages\", \"To write JavaScript\"]}'),
            (5, 'What is the difference between GET and POST methods?', 'multiple_choice', 'GET retrieves data while POST submits data', '{\"options\": [\"GET retrieves data while POST submits data\", \"They are the same\", \"GET is faster\", \"POST is more secure\"]}'),
            (5, 'What is Cross-Origin Resource Sharing (CORS)?', 'multiple_choice', 'A security feature that controls how web pages access resources from different domains', '{\"options\": [\"A security feature that controls how web pages access resources from different domains\", \"A programming language\", \"A database type\", \"A web server\"]}'),
            (5, 'What is the purpose of localStorage?', 'multiple_choice', 'To store data persistently in a web browser', '{\"options\": [\"To store data persistently in a web browser\", \"To create databases\", \"To style web pages\", \"To write JavaScript\"]}'),
            (5, 'What is a web socket?', 'multiple_choice', 'A protocol for full-duplex communication between client and server', '{\"options\": [\"A protocol for full-duplex communication between client and server\", \"A database connection\", \"A CSS property\", \"An HTML element\"]}'),
            (5, 'What is the purpose of a CDN?', 'multiple_choice', 'To deliver content to users from the nearest server location', '{\"options\": [\"To deliver content to users from the nearest server location\", \"To create websites\", \"To write code\", \"To store data\"]}'),

            -- Data Analysis Questions
            (6, 'What is the purpose of exploratory data analysis (EDA)?', 'multiple_choice', 'To understand data patterns and relationships before formal modeling', '{\"options\": [\"To understand data patterns and relationships before formal modeling\", \"To clean data only\", \"To create visualizations only\", \"To build machine learning models\"]}'),
            (6, 'Which measure of central tendency is most sensitive to outliers?', 'multiple_choice', 'Mean', '{\"options\": [\"Mean\", \"Median\", \"Mode\", \"Range\"]}'),
            (6, 'What is the purpose of data normalization?', 'multiple_choice', 'To scale features to a similar range for fair comparison', '{\"options\": [\"To scale features to a similar range for fair comparison\", \"To remove data\", \"To create new features\", \"To fix missing values\"]}'),
            (6, 'What is a correlation coefficient used for?', 'multiple_choice', 'To measure the strength and direction of relationship between variables', '{\"options\": [\"To measure the strength and direction of relationship between variables\", \"To predict future values\", \"To clean data\", \"To create graphs\"]}'),
            (6, 'What is the difference between variance and standard deviation?', 'multiple_choice', 'Standard deviation is the square root of variance', '{\"options\": [\"Standard deviation is the square root of variance\", \"They are the same thing\", \"Variance is always larger\", \"Standard deviation is always larger\"]}'),
            (6, 'What is the purpose of hypothesis testing?', 'multiple_choice', 'To make statistical decisions using experimental data', '{\"options\": [\"To make statistical decisions using experimental data\", \"To clean data\", \"To create visualizations\", \"To build models\"]}'),
            (6, 'What is the difference between population and sample?', 'multiple_choice', 'A sample is a subset of the population', '{\"options\": [\"A sample is a subset of the population\", \"They are the same thing\", \"Population is smaller than sample\", \"Sample contains all data\"]}'),
            (6, 'What is the purpose of a box plot?', 'multiple_choice', 'To show distribution, median, quartiles, and potential outliers', '{\"options\": [\"To show distribution, median, quartiles, and potential outliers\", \"To show only mean values\", \"To predict future values\", \"To clean data\"]}'),
            (6, 'What is the difference between categorical and numerical data?', 'multiple_choice', 'Categorical data represents groups while numerical data represents quantities', '{\"options\": [\"Categorical data represents groups while numerical data represents quantities\", \"They are the same\", \"Categorical data is always numeric\", \"Numerical data cannot be counted\"]}'),
            (6, 'What is the purpose of feature engineering?', 'multiple_choice', 'To create new meaningful features from existing data', '{\"options\": [\"To create new meaningful features from existing data\", \"To remove features\", \"To only clean data\", \"To create visualizations\"]}')
        ");
        echo "Added sample quiz questions\n";
    }
    
    // Add sample resources
    $stmt = $pdo->query("SELECT COUNT(*) FROM resources");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO resources (skill_id, title, description, type, url, thumbnail_url) VALUES
            -- Object-Oriented Programming Resources
            (1, 'Object-Oriented Programming in Python', 'Complete guide to OOP concepts in Python', 'video', 'https://www.youtube.com/watch?v=ZDa-Z5JzLYM', 'https://img.youtube.com/vi/ZDa-Z5JzLYM/0.jpg'),
            (1, 'Java OOP Made Easy', 'Learn OOP concepts with practical examples', 'video', 'https://www.youtube.com/watch?v=pTB0EiLXUC8', 'https://img.youtube.com/vi/pTB0EiLXUC8/0.jpg'),
            (1, 'C++ Object-Oriented Programming', 'Master OOP in C++ with real-world examples', 'video', 'https://www.youtube.com/watch?v=wN0x9eZLix4', 'https://img.youtube.com/vi/wN0x9eZLix4/0.jpg'),
            (1, 'SOLID Principles Made Easy', 'Understanding SOLID principles in OOP', 'video', 'https://www.youtube.com/watch?v=rtmFCcjEgEw', 'https://img.youtube.com/vi/rtmFCcjEgEw/0.jpg'),
            (1, 'Design Patterns Tutorial', 'Learn common design patterns in OOP', 'video', 'https://www.youtube.com/watch?v=v9ejT8FO-7I', 'https://img.youtube.com/vi/v9ejT8FO-7I/0.jpg'),
            (1, 'OOP Design Patterns Guide', 'Comprehensive PDF guide to design patterns', 'pdf', 'assets/resources/design-patterns.pdf', null),
            (1, 'Clean Code in OOP', 'Best practices for writing clean OOP code', 'pdf', 'assets/resources/clean-code-oop.pdf', null),
            (1, 'SOLID Principles Handbook', 'Detailed guide to SOLID principles', 'pdf', 'assets/resources/solid-principles.pdf', null),
            
            -- Data Structures Resources
            (2, 'Data Structures Fundamentals', 'Learn basic data structures implementation', 'video', 'https://www.youtube.com/watch?v=RBSGKlAvoiM', 'https://img.youtube.com/vi/RBSGKlAvoiM/0.jpg'),
            (2, 'Binary Trees and BST', 'Understanding tree data structures', 'video', 'https://www.youtube.com/watch?v=fAAZixBzIAI', 'https://img.youtube.com/vi/fAAZixBzIAI/0.jpg'),
            (2, 'Hash Tables Explained', 'Deep dive into hash tables and hashing', 'video', 'https://www.youtube.com/watch?v=shs0KM3wKv8', 'https://img.youtube.com/vi/shs0KM3wKv8/0.jpg'),
            (2, 'Graph Data Structures', 'Complete guide to graph implementations', 'video', 'https://www.youtube.com/watch?v=tWVWeAqZ0WU', 'https://img.youtube.com/vi/tWVWeAqZ0WU/0.jpg'),
            (2, 'Advanced Data Structures', 'Deep dive into complex data structures', 'pdf', 'assets/resources/advanced-ds.pdf', null),
            (2, 'Time Complexity Analysis', 'Guide to analyzing data structure operations', 'pdf', 'assets/resources/time-complexity.pdf', null),
            (2, 'Data Structures Interview Guide', 'Common interview problems and solutions', 'pdf', 'assets/resources/ds-interview.pdf', null),
            
            -- Algorithms Resources
            (3, 'Introduction to Algorithms', 'Basic algorithm concepts and analysis', 'video', 'https://www.youtube.com/watch?v=0IAPZzGSbME', 'https://img.youtube.com/vi/0IAPZzGSbME/0.jpg'),
            (3, 'Sorting Algorithms Visualized', 'Visual guide to common sorting algorithms', 'video', 'https://www.youtube.com/watch?v=kPRA0W1kECg', 'https://img.youtube.com/vi/kPRA0W1kECg/0.jpg'),
            (3, 'Dynamic Programming', 'Master dynamic programming techniques', 'video', 'https://www.youtube.com/watch?v=oBt53YbR9Kk', 'https://img.youtube.com/vi/oBt53YbR9Kk/0.jpg'),
            (3, 'Graph Algorithms', 'Understanding graph traversal and shortest paths', 'video', 'https://www.youtube.com/watch?v=tWVWeAqZ0WU', 'https://img.youtube.com/vi/tWVWeAqZ0WU/0.jpg'),
            (3, 'Algorithm Design Manual', 'Complete guide to algorithm design', 'pdf', 'assets/resources/algo-design.pdf', null),
            (3, 'Dynamic Programming Guide', 'Comprehensive guide to DP problems', 'pdf', 'assets/resources/dp-guide.pdf', null),
            (3, 'Algorithm Analysis Handbook', 'Deep dive into algorithm complexity', 'pdf', 'assets/resources/algo-analysis.pdf', null),
            
            -- Database Resources
            (4, 'SQL Database Design', 'Learn database design principles', 'video', 'https://www.youtube.com/watch?v=HXV3zeQKqGY', 'https://img.youtube.com/vi/HXV3zeQKqGY/0.jpg'),
            (4, 'NoSQL Databases', 'Understanding NoSQL database systems', 'video', 'https://www.youtube.com/watch?v=xQnIN9bW0og', 'https://img.youtube.com/vi/xQnIN9bW0og/0.jpg'),
            (4, 'Database Indexing', 'Master database indexing strategies', 'video', 'https://www.youtube.com/watch?v=HubXt90MLfE', 'https://img.youtube.com/vi/HubXt90MLfE/0.jpg'),
            (4, 'Database Transactions', 'Understanding ACID properties', 'video', 'https://www.youtube.com/watch?v=5ZjhNTM8XU8', 'https://img.youtube.com/vi/5ZjhNTM8XU8/0.jpg'),
            (4, 'Database Optimization Guide', 'Tips and tricks for database optimization', 'pdf', 'assets/resources/db-optimization.pdf', null),
            (4, 'SQL Best Practices', 'Guide to writing efficient SQL queries', 'pdf', 'assets/resources/sql-best-practices.pdf', null),
            (4, 'Database Security Guide', 'Comprehensive guide to database security', 'pdf', 'assets/resources/db-security.pdf', null),
            
            -- Web Development Resources
            (5, 'Modern Web Development', 'Full stack web development tutorial', 'video', 'https://www.youtube.com/watch?v=Q33KBiDriJY', 'https://img.youtube.com/vi/Q33KBiDriJY/0.jpg'),
            (5, 'React.js Crash Course', 'Learn React.js fundamentals', 'video', 'https://www.youtube.com/watch?v=w7ejDZ8SWv8', 'https://img.youtube.com/vi/w7ejDZ8SWv8/0.jpg'),
            (5, 'Node.js Tutorial', 'Backend development with Node.js', 'video', 'https://www.youtube.com/watch?v=Oe421EPjeBE', 'https://img.youtube.com/vi/Oe421EPjeBE/0.jpg'),
            (5, 'REST API Design', 'Best practices for API development', 'video', 'https://www.youtube.com/watch?v=fgTGADljAeg', 'https://img.youtube.com/vi/fgTGADljAeg/0.jpg'),
            (5, 'Web Security Best Practices', 'Guide to securing web applications', 'pdf', 'assets/resources/web-security.pdf', null),
            (5, 'Frontend Development Guide', 'Modern frontend development practices', 'pdf', 'assets/resources/frontend-guide.pdf', null),
            (5, 'Web Performance Optimization', 'Techniques for faster web apps', 'pdf', 'assets/resources/web-performance.pdf', null),
            
            -- Data Analysis Resources
            (6, 'Data Analysis with Python', 'Learn data analysis using Python', 'video', 'https://www.youtube.com/watch?v=r-uOLxNrNk8', 'https://img.youtube.com/vi/r-uOLxNrNk8/0.jpg'),
            (6, 'Pandas Tutorial', 'Master data manipulation with Pandas', 'video', 'https://www.youtube.com/watch?v=vmEHCJofslg', 'https://img.youtube.com/vi/vmEHCJofslg/0.jpg'),
            (6, 'Data Visualization', 'Creating effective data visualizations', 'video', 'https://www.youtube.com/watch?v=a9UrKTVEeZA', 'https://img.youtube.com/vi/a9UrKTVEeZA/0.jpg'),
            (6, 'Statistical Analysis', 'Understanding statistical methods', 'video', 'https://www.youtube.com/watch?v=xxpc-HPKN28', 'https://img.youtube.com/vi/xxpc-HPKN28/0.jpg'),
            (6, 'Statistical Analysis Guide', 'Comprehensive guide to statistical analysis', 'pdf', 'assets/resources/stats-guide.pdf', null),
            (6, 'Data Cleaning Techniques', 'Guide to preparing data for analysis', 'pdf', 'assets/resources/data-cleaning.pdf', null),
            (6, 'Machine Learning Basics', 'Introduction to ML concepts', 'pdf', 'assets/resources/ml-basics.pdf', null)
        ");
        echo "Added sample resources\n";
    }
    
    echo "\nDatabase updated successfully!";
} catch(PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
    // Print the SQL state and error code for debugging
    echo "SQL State: " . $e->errorInfo[0] . "\n";
    echo "Error Code: " . $e->errorInfo[1] . "\n";
}
?> 