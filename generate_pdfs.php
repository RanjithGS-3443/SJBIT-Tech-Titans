<?php
require 'vendor/setasign/fpdf/fpdf.php';

// Create assets/resources directory if it doesn't exist
if (!file_exists('assets/resources')) {
    mkdir('assets/resources', 0777, true);
}

class ResourcePDF extends FPDF {
    public $title;

    function Header() {
        $this->SetFont('Arial', 'B', 24);
        $this->Cell(0, 20, $this->title, 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    function ChapterTitle($title) {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, $title, 0, 1, 'L');
        $this->Ln(5);
    }

    function ChapterContent($content) {
        $this->SetFont('Arial', '', 12);
        $this->MultiCell(0, 10, $content);
        $this->Ln(10);
    }
}

// Define PDF content for each resource
$pdfs = [
    // OOP PDFs
    [
        'filename' => 'design-patterns.pdf',
        'title' => 'OOP Design Patterns Guide',
        'content' => [
            'Introduction to Design Patterns' => "Design patterns are typical solutions to common problems in software design. Each pattern is like a blueprint that you can customize to solve a particular design problem in your code.\n\nDesign patterns provide tested, proven development paradigms that can speed up the development process by providing standardized approaches to solving common problems. They also improve code readability for coders and architects who are familiar with the patterns.",
            'Creational Patterns' => "Creational patterns provide various object creation mechanisms that increase flexibility and reuse of existing code:\n\n1. Singleton Pattern: Ensures a class has only one instance while providing global access point.\nUse case: Database connections, Configuration managers\n\n2. Factory Method: Provides an interface for creating objects but allows subclasses to alter the type of objects.\nUse case: Document generators, UI element creation\n\n3. Abstract Factory: Creates families of related objects without specifying concrete classes.\nUse case: Cross-platform UI toolkits, Database access layers\n\n4. Builder Pattern: Constructs complex objects step by step, allowing different representations.\nUse case: Complex object construction, DOM parsing\n\n5. Prototype Pattern: Creates new objects by cloning an existing object (prototype).\nUse case: Object copying, preserving object state",
            'Structural Patterns' => "Structural patterns explain how to assemble objects and classes into larger structures:\n\n1. Adapter Pattern: Allows incompatible interfaces to work together by wrapping an object in an adapter.\nUse case: Third-party library integration, Legacy system adaptation\n\n2. Bridge Pattern: Separates an abstraction from its implementation so both can vary independently.\nUse case: Cross-platform applications, Driver implementations\n\n3. Composite Pattern: Composes objects into tree structures to represent part-whole hierarchies.\nUse case: File systems, GUI components\n\n4. Decorator Pattern: Adds new responsibilities to objects dynamically.\nUse case: Input/Output streams, UI component enhancement\n\n5. Facade Pattern: Provides a simplified interface to a complex subsystem.\nUse case: Library wrappers, Complex system interfaces",
            'Behavioral Patterns' => "Behavioral patterns are concerned with communication between objects:\n\n1. Observer Pattern: Defines a one-to-many dependency between objects.\nUse case: Event handling, MVC architecture\n\n2. Strategy Pattern: Defines a family of algorithms and makes them interchangeable.\nUse case: Sorting algorithms, Payment methods\n\n3. Command Pattern: Encapsulates a request as an object.\nUse case: Queue management, Undo functionality\n\n4. State Pattern: Allows an object to alter its behavior when its internal state changes.\nUse case: Vending machines, Game character states\n\n5. Template Method Pattern: Defines the skeleton of an algorithm, letting subclasses override specific steps.\nUse case: Data mining operations, Document generation"
        ]
    ],
    [
        'filename' => 'clean-code-oop.pdf',
        'title' => 'Clean Code in OOP',
        'content' => [
            'Clean Code Principles' => "Clean code is not just about making code work - it's about making code understandable, maintainable, and scalable. Clean code should read like well-written prose, with each line and function telling a clear story about the program's intent.\n\nKey aspects of clean code include:\n- Clarity: Code should be easy to understand\n- Simplicity: Avoid unnecessary complexity\n- Maintainability: Easy to modify and extend\n- Testability: Easy to test and verify\n- Documentation: Self-documenting when possible",
            'Naming Conventions' => "Good naming is crucial for code readability and maintenance:\n\n1. Use Intention-Revealing Names:\n   Bad: int d; // elapsed time in days\n   Good: int elapsedTimeInDays;\n\n2. Make Meaningful Distinctions:\n   Bad: String nameString;\n   Good: String customerName;\n\n3. Use Pronounceable Names:\n   Bad: private Date genymdhms;\n   Good: private Date generationTimestamp;\n\n4. Use Searchable Names:\n   Bad: if (e == 7) {...}\n   Good: if (employeeStatus == ACTIVE) {...}\n\n5. Avoid Encodings:\n   Bad: String m_customerName;\n   Good: String customerName;",
            'Class Design' => "Effective class design is fundamental to OOP:\n\n1. Single Responsibility Principle:\n   - Each class should have only one reason to change\n   - Example: Separate data access, business logic, and presentation\n\n2. Keep Classes Small and Focused:\n   - Typically 50-500 lines\n   - Should do one thing well\n   - If you can't describe it without 'and' or 'or', split it\n\n3. Maintain High Cohesion:\n   - Methods and properties should be closely related\n   - All members should contribute to the class's purpose\n\n4. Use Proper Encapsulation:\n   - Hide implementation details\n   - Provide clear public interfaces\n   - Use access modifiers appropriately\n\n5. Favor Composition Over Inheritance:\n   - Inheritance can lead to tight coupling\n   - Composition provides more flexibility\n   - Easier to modify behavior at runtime",
            'Method Design' => "Well-designed methods are essential for maintainable code:\n\n1. Keep Methods Small:\n   - Ideally 5-15 lines\n   - Should fit on one screen\n   - Easier to understand and test\n\n2. Do One Thing:\n   - Methods should have a single responsibility\n   - Should do it well\n   - Should do it only\n\n3. Use Descriptive Names:\n   - Verb/noun combination for actions\n   - Describe what the method does\n   - Be consistent with naming patterns\n\n4. Minimize Parameters:\n   - Aim for 0-2 parameters\n   - Consider creating parameter objects\n   - Use method chaining when appropriate\n\n5. Avoid Side Effects:\n   - Methods should be predictable\n   - Document any side effects\n   - Consider using pure functions"
        ]
    ],
    [
        'filename' => 'solid-principles.pdf',
        'title' => 'SOLID Principles Handbook',
        'content' => [
            'Introduction to SOLID' => "SOLID principles, introduced by Robert C. Martin, are five fundamental principles of object-oriented programming and design. These principles make software more understandable, flexible, and maintainable.\n\nThe importance of SOLID principles:\n- Reduces code complexity\n- Facilitates testing and maintenance\n- Promotes code reusability\n- Makes code more robust\n- Enables easier refactoring",
            'Single Responsibility Principle' => "A class should have only one reason to change. This principle promotes:\n\n1. High Cohesion:\n   - Each class has a well-defined purpose\n   - All methods relate to that purpose\n\n2. Benefits:\n   - Easier to understand and maintain\n   - More testable code\n   - Reduced coupling\n\n3. Example:\nBad:\n```php
class User {
    public function saveUser() { /* ... */ }
    public function generateReport() { /* ... */ }
    public function sendEmail() { /* ... */ }
}
```
Good:\n```php
class User { public function saveUser() { /* ... */ } }
class ReportGenerator { public function generateReport() { /* ... */ } }
class EmailService { public function sendEmail() { /* ... */ } }
```",
            'Open/Closed Principle' => "Software entities should be open for extension but closed for modification. This means:\n\n1. Core Concept:\n   - Existing code shouldn't change when adding new functionality\n   - Use interfaces and abstract classes\n   - Implement new features through inheritance or composition\n\n2. Benefits:\n   - Reduces risk of bugs in existing code\n   - Promotes code reuse\n   - Improves maintainability\n\n3. Example:\n```php
// Open for extension, closed for modification
interface PaymentMethod {
    public function processPayment($amount);
}

class CreditCardPayment implements PaymentMethod {
    public function processPayment($amount) { /* ... */ }
}

class PayPalPayment implements PaymentMethod {
    public function processPayment($amount) { /* ... */ }
}
```",
            'Liskov Substitution Principle' => "Objects of a superclass should be replaceable with objects of its subclasses without affecting the correctness of the program.\n\n1. Key Concepts:\n   - Subtypes must fulfill the contracts of their base types\n   - Preserve invariants of the base class\n   - Maintain expected behavior\n\n2. Guidelines:\n   - Preconditions cannot be strengthened in subclasses\n   - Postconditions cannot be weakened in subclasses\n   - Base class invariants must be preserved\n\n3. Example:\n```php
class Bird {
    public function fly() { /* ... */ }
}

// Violates LSP
class Penguin extends Bird {
    public function fly() {
        throw new Exception('Cannot fly');
    }
}

// Better design
interface Flyable {
    public function fly();
}

class Bird implements Flyable { /* ... */ }
class Penguin { /* ... */ }
```",
            'Interface Segregation Principle' => "Clients should not be forced to depend on interfaces they do not use.\n\n1. Core Concepts:\n   - Keep interfaces small and focused\n   - Split large interfaces into smaller ones\n   - Clients should only know about methods they use\n\n2. Benefits:\n   - Reduces coupling\n   - Improves maintainability\n   - More flexible code\n\n3. Example:\n```php
// Bad
interface Worker {
    public function work();
    public function eat();
    public function sleep();
}

// Good
interface Workable {
    public function work();
}

interface Eatable {
    public function eat();
}

interface Sleepable {
    public function sleep();
}
```",
            'Dependency Inversion Principle' => "High-level modules should not depend on low-level modules. Both should depend on abstractions.\n\n1. Key Points:\n   - Depend on abstractions, not concretions\n   - High-level modules define interfaces\n   - Low-level modules implement interfaces\n\n2. Benefits:\n   - Decoupled code\n   - Easier testing\n   - More flexible architecture\n\n3. Example:\n```php
// Bad
class EmailNotifier {
    public function sendEmail() { /* ... */ }
}

class OrderProcessor {
    private $emailNotifier;
    
    public function __construct() {
        $this->emailNotifier = new EmailNotifier();
    }
}

// Good
interface NotificationService {
    public function send();
}

class EmailNotifier implements NotificationService {
    public function send() { /* ... */ }
}

class OrderProcessor {
    private $notifier;
    
    public function __construct(NotificationService $notifier) {
        $this->notifier = $notifier;
    }
}
```"
        ]
    ],
    
    // Data Structures PDFs
    [
        'filename' => 'advanced-ds.pdf',
        'title' => 'Advanced Data Structures',
        'content' => [
            'Advanced Tree Structures' => "1. Red-Black Trees\n2. AVL Trees\n3. B-Trees\n4. Trie\n5. Segment Trees",
            'Graph Structures' => "1. Adjacency Matrix\n2. Adjacency List\n3. Edge List\n4. Weighted Graphs\n5. Directed Graphs",
            'Advanced Hash Structures' => "1. Perfect Hashing\n2. Cuckoo Hashing\n3. Bloom Filters\n4. Skip Lists\n5. Distributed Hash Tables"
        ]
    ],
    [
        'filename' => 'time-complexity.pdf',
        'title' => 'Time Complexity Analysis',
        'content' => [
            'Understanding Time Complexity' => "Time complexity is a crucial concept in computer science that helps us analyze and compare the efficiency of different algorithms.",
            'Big O Notation' => "1. O(1) - Constant Time\n2. O(log n) - Logarithmic Time\n3. O(n) - Linear Time\n4. O(n log n) - Linearithmic Time\n5. O(n²) - Quadratic Time",
            'Common Operations' => "Array Operations:\n- Access: O(1)\n- Search: O(n)\n- Insert: O(n)\n- Delete: O(n)\n\nLinked List Operations:\n- Access: O(n)\n- Search: O(n)\n- Insert: O(1)\n- Delete: O(1)"
        ]
    ],
    [
        'filename' => 'ds-interview.pdf',
        'title' => 'Data Structures Interview Guide',
        'content' => [
            'Common Interview Topics' => "1. Arrays and Strings\n2. Linked Lists\n3. Trees and Graphs\n4. Hash Tables\n5. Stacks and Queues",
            'Problem-Solving Strategies' => "1. Break down the problem\n2. Consider edge cases\n3. Think about time/space complexity\n4. Consider multiple approaches\n5. Test your solution",
            'Practice Problems' => "1. Reverse a linked list\n2. Check if a binary tree is balanced\n3. Implement a hash table\n4. Find the shortest path in a graph\n5. Design an LRU cache"
        ]
    ],
    
    // Database PDFs
    [
        'filename' => 'db-optimization.pdf',
        'title' => 'Database Optimization Guide',
        'content' => [
            'Query Optimization' => "1. Use proper indexes\n2. Write efficient queries\n3. Avoid SELECT *\n4. Use EXPLAIN to analyze queries\n5. Optimize JOIN operations",
            'Database Design' => "1. Proper normalization\n2. Appropriate data types\n3. Efficient relationships\n4. Smart indexing strategies\n5. Partitioning considerations",
            'Performance Tuning' => "1. Cache optimization\n2. Memory management\n3. Disk I/O optimization\n4. Connection pooling\n5. Query cache settings"
        ]
    ],
    [
        'filename' => 'sql-best-practices.pdf',
        'title' => 'SQL Best Practices',
        'content' => [
            'Writing Efficient Queries' => "Learn how to write SQL queries that perform well and are maintainable.",
            'Indexing Strategies' => "1. Choose appropriate columns\n2. Consider cardinality\n3. Handle multiple columns\n4. Update statistics regularly\n5. Monitor index usage",
            'Common Pitfalls' => "1. Avoid SELECT *\n2. Use appropriate JOIN types\n3. Handle NULL values properly\n4. Avoid cursors when possible\n5. Use stored procedures wisely"
        ]
    ],
    [
        'filename' => 'db-security.pdf',
        'title' => 'Database Security Guide',
        'content' => [
            'Security Best Practices' => "1. Use prepared statements\n2. Implement proper authentication\n3. Regular security audits\n4. Encrypt sensitive data\n5. Monitor database access",
            'Common Threats' => "1. SQL injection\n2. Data breaches\n3. Privilege escalation\n4. Denial of service\n5. Data corruption",
            'Security Measures' => "1. Access control\n2. Data encryption\n3. Audit logging\n4. Backup strategies\n5. Security patches"
        ]
    ],
    
    // Web Development PDFs
    [
        'filename' => 'web-security.pdf',
        'title' => 'Web Security Best Practices',
        'content' => [
            'Common Security Threats' => "1. Cross-Site Scripting (XSS)\n2. SQL Injection\n3. Cross-Site Request Forgery (CSRF)\n4. Session Hijacking\n5. Man-in-the-Middle Attacks",
            'Security Measures' => "1. Input validation\n2. Output encoding\n3. Authentication\n4. Authorization\n5. Encryption",
            'Best Practices' => "1. Use HTTPS\n2. Implement CSP\n3. Secure cookies\n4. Regular updates\n5. Security headers"
        ]
    ],
    [
        'filename' => 'frontend-guide.pdf',
        'title' => 'Frontend Development Guide',
        'content' => [
            'Modern Frontend Development' => "Overview of current frontend development practices and tools.",
            'Essential Technologies' => "1. HTML5 features\n2. CSS3 capabilities\n3. Modern JavaScript\n4. Popular frameworks\n5. Build tools",
            'Best Practices' => "1. Responsive design\n2. Performance optimization\n3. Accessibility\n4. Cross-browser compatibility\n5. Code organization"
        ]
    ],
    [
        'filename' => 'web-performance.pdf',
        'title' => 'Web Performance Optimization',
        'content' => [
            'Performance Metrics' => "1. Load time\n2. Time to interactive\n3. First contentful paint\n4. Largest contentful paint\n5. Cumulative layout shift",
            'Optimization Techniques' => "1. Minimize HTTP requests\n2. Optimize images\n3. Use CDN\n4. Enable compression\n5. Browser caching",
            'Tools and Testing' => "1. Lighthouse\n2. WebPageTest\n3. Chrome DevTools\n4. GTmetrix\n5. PageSpeed Insights"
        ]
    ],
    
    // Data Analysis PDFs
    [
        'filename' => 'stats-guide.pdf',
        'title' => 'Statistical Analysis Guide',
        'content' => [
            'Descriptive Statistics' => "1. Mean, Median, Mode\n2. Standard Deviation\n3. Variance\n4. Percentiles\n5. Distribution Types",
            'Inferential Statistics' => "1. Hypothesis Testing\n2. Confidence Intervals\n3. Regression Analysis\n4. ANOVA\n5. Chi-Square Tests",
            'Statistical Tools' => "1. Python (NumPy, SciPy)\n2. R Programming\n3. SPSS\n4. Excel\n5. Tableau"
        ]
    ],
    [
        'filename' => 'data-cleaning.pdf',
        'title' => 'Data Cleaning Techniques',
        'content' => [
            'Data Quality Issues' => "1. Missing values\n2. Duplicates\n3. Inconsistent formats\n4. Outliers\n5. Invalid data",
            'Cleaning Methods' => "1. Imputation techniques\n2. Standardization\n3. Normalization\n4. Outlier detection\n5. Data validation",
            'Tools and Libraries' => "1. Pandas\n2. OpenRefine\n3. Trifacta\n4. Python scripts\n5. SQL cleaning"
        ]
    ],
    [
        'filename' => 'ml-basics.pdf',
        'title' => 'Machine Learning Basics',
        'content' => [
            'Introduction to ML' => "Basic concepts and types of machine learning algorithms.",
            'Supervised Learning' => "1. Classification\n2. Regression\n3. Decision Trees\n4. Random Forests\n5. Neural Networks",
            'Unsupervised Learning' => "1. Clustering\n2. Dimensionality Reduction\n3. Association Rules\n4. Anomaly Detection\n5. Principal Component Analysis"
        ]
    ]
];

// Generate PDFs
foreach ($pdfs as $pdf_info) {
    $pdf = new ResourcePDF();
    $pdf->title = $pdf_info['title'];
    $pdf->AddPage();
    
    foreach ($pdf_info['content'] as $title => $content) {
        $pdf->ChapterTitle($title);
        $pdf->ChapterContent($content);
    }
    
    $pdf->Output('F', 'assets/resources/' . $pdf_info['filename']);
    echo "Generated: " . $pdf_info['filename'] . "\n";
}

echo "All PDFs generated successfully!\n";
?> 