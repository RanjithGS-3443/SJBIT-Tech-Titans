<?php
require_once 'config/database.php';

try {
    // Start transaction
    $pdo->beginTransaction();

    // 1. Ensure Data Analyst career goal exists
    $stmt = $pdo->prepare("SELECT id FROM career_goals WHERE name = 'Data Analyst'");
    $stmt->execute();
    $goal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$goal) {
        $stmt = $pdo->prepare("INSERT INTO career_goals (name, description, required_skills, estimated_time, salary_range) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'Data Analyst',
            'Analyze and interpret complex data sets to drive business decisions',
            'Python,Data Analysis',
            '4-8 months',
            '$45,000 - $85,000'
        ]);
        $goal_id = $pdo->lastInsertId();
    } else {
        $goal_id = $goal['id'];
    }

    // 2. Ensure required skills exist
    $required_skills = ['Python', 'Data Analysis'];
    foreach ($required_skills as $skill_name) {
        $stmt = $pdo->prepare("SELECT id FROM skills WHERE skill_name = ?");
        $stmt->execute([$skill_name]);
        $skill = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$skill) {
            $stmt = $pdo->prepare("INSERT INTO skills (skill_name, category, description) VALUES (?, ?, ?)");
            $stmt->execute([
                $skill_name,
                'Data Science',
                $skill_name === 'Python' ? 'Programming language for data analysis' : 'Analyzing and interpreting complex data sets'
            ]);
            $skill_id = $pdo->lastInsertId();
        } else {
            $skill_id = $skill['id'];
        }

        // 3. Add resources for each skill
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM resources WHERE skill_id = ?");
        $stmt->execute([$skill_id]);
        $resource_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($resource_count === 0) {
            if ($skill_name === 'Python') {
                $resources = [
                    [
                        'Python Basics',
                        'Introduction to Python programming',
                        'course',
                        'https://www.codecademy.com/learn/learn-python',
                        'Codecademy',
                        true
                    ],
                    [
                        'Python for Data Analysis',
                        'Learn data analysis with Python',
                        'course',
                        'https://www.datacamp.com/courses/python-for-data-science',
                        'DataCamp',
                        true
                    ]
                ];
            } else {
                $resources = [
                    [
                        'Data Analysis Fundamentals',
                        'Learn the basics of data analysis',
                        'course',
                        'https://www.coursera.org/learn/data-analysis',
                        'Coursera',
                        true
                    ],
                    [
                        'Advanced Data Analysis',
                        'Advanced techniques in data analysis',
                        'course',
                        'https://www.udacity.com/course/data-analyst-nanodegree',
                        'Udacity',
                        true
                    ]
                ];
            }

            $stmt = $pdo->prepare("INSERT INTO resources (skill_id, title, description, type, url, platform, is_free) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($resources as $resource) {
                $stmt->execute(array_merge([$skill_id], $resource));
            }
        }
    }

    // Commit transaction
    $pdo->commit();
    echo "Data Analyst goal and requirements set up successfully!";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
} 