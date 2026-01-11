-- Rustnite Database Setup
-- Run this SQL to set up your database with sample data

-- Clear existing data
DELETE FROM user_progress;
DELETE FROM lessons;
DELETE FROM badges;

-- Create demo user
INSERT INTO users (username, email, password, xp, level, created_at) VALUES
('demo', 'demo@rustnite.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 1, NOW());

-- Rust Book Lessons - Chapter 1: Getting Started
INSERT INTO lessons (title, description, content, code_template, expected_output, difficulty, xp_reward, order_num) VALUES
('Hello, World!', 'Your first Rust program - the traditional starting point', 'Welcome to Rust! Let''s start with the classic "Hello, World!" program.

In Rust, we use the `println!` macro to print text to the console. The exclamation mark indicates it''s a macro, not a function.

Key concepts:
- `fn main()` is the entry point of every Rust program
- `println!` is a macro for printing to stdout
- Rust uses curly braces `{}` for code blocks
- Statements end with semicolons

Your task: Write a program that prints "Hello, Rustnite!" to the console.', 'fn main() {
    // Write your code here
    
}', 'Hello, Rustnite!', 'beginner', 100, 1),

('Variables and Mutability', 'Learn about Rust''s variable system and mutability', 'In Rust, variables are immutable by default. This is one of Rust''s key features that helps you write safe code.

Key concepts:
- Variables are immutable by default
- Use `let` to declare variables
- Use `mut` keyword to make variables mutable
- Rust has strong type inference

Example:
```rust
let x = 5;        // immutable
let mut y = 10;   // mutable
y = 15;           // this works
// x = 6;         // this would cause an error
```

Your task: Create variables and demonstrate mutability.', 'fn main() {
    let x = 5;
    let mut y = 10;
    
    // Change y to 15
    
    // Print both values
    println!("x = {}", x);
    
}', 'x = 5
y = 15', 'beginner', 150, 2),

('Data Types', 'Explore Rust''s type system', 'Rust is a statically typed language, which means it must know the types of all variables at compile time.

Scalar Types:
- Integers: i8, i16, i32, i64, i128, isize, u8, u16, u32, u64, u128, usize
- Floating-point: f32, f64
- Boolean: bool (true/false)
- Character: char (Unicode scalar values)

Compound Types:
- Tuples: (i32, f64, u8)
- Arrays: [i32; 5]

Your task: Create variables of different types and print them.', 'fn main() {
    let integer: i32 = 42;
    let float: f64 = 3.14;
    let boolean: bool = true;
    let character: char = ''R'';
    
    // Print all variables using println!
    
}', '42
3.14
true
R', 'beginner', 150, 3),

('Functions', 'Write your first Rust function', 'Functions are prevalent in Rust code. The most important function is `main`, which is the entry point of many programs.

Function syntax:
- Declared with `fn` keyword
- Parameters must have type annotations
- Return type specified with `->`
- Return value is the final expression (no semicolon)

Example:
```rust
fn add_numbers(x: i32, y: i32) -> i32 {
    x + y  // no semicolon = return value
}
```

Your task: Create a function that adds two numbers and call it from main.', 'fn main() {
    let result = add(5, 3);
    println!("5 + 3 = {}", result);
}

// Write your add function here
fn add(x: i32, y: i32) -> i32 {
    // Return the sum of x and y
    
}', '5 + 3 = 8', 'beginner', 200, 4),

('Comments and Documentation', 'Learn to document your Rust code', 'Good documentation is crucial for maintainable code. Rust has several types of comments:

Types of comments:
- Line comments: `// This is a comment`
- Block comments: `/* This is a block comment */`
- Doc comments: `/// This documents the next item`
- Module doc comments: `//! This documents the enclosing item`

Doc comments support Markdown and can include code examples that are tested!

Your task: Add comments and documentation to a simple function.', 'fn main() {
    let result = calculate_area(5.0, 3.0);
    println!("Area: {}", result);
}

/// Calculate the area of a rectangle
/// 
/// # Arguments
/// 
/// * `length` - The length of the rectangle
/// * `width` - The width of the rectangle
/// 
/// # Examples
/// 
/// ```
/// let area = calculate_area(5.0, 3.0);
/// assert_eq!(area, 15.0);
/// ```
fn calculate_area(length: f64, width: f64) -> f64 {
    // TODO: Implement the calculation
    
}', 'Area: 15', 'beginner', 150, 5),

-- Chapter 3: Common Programming Concepts
('Control Flow - if/else', 'Learn conditional logic in Rust', 'Control flow constructs allow you to control the order in which code runs. The most common are `if` expressions.

Key points:
- `if` is an expression, not a statement
- Conditions must be `bool` type (no truthy/falsy values)
- You can use `if` in `let` statements
- Arms must return the same type

Example:
```rust
let number = 6;
if number % 4 == 0 {
    println!("divisible by 4");
} else if number % 3 == 0 {
    println!("divisible by 3");
} else {
    println!("not divisible by 4 or 3");
}
```

Your task: Write a program that checks if a number is positive, negative, or zero.', 'fn main() {
    let number = -5;
    
    // Write if/else logic to check if number is positive, negative, or zero
    // Print appropriate messages
    
}', 'The number is negative', 'beginner', 175, 6),

('Loops', 'Master Rust''s looping constructs', 'Rust has three kinds of loops: `loop`, `while`, and `for`.

Loop types:
- `loop` - infinite loop until `break`
- `while` - conditional loop
- `for` - iterator loop (most common)

Examples:
```rust
// loop
let mut counter = 0;
loop {
    counter += 1;
    if counter == 10 { break; }
}

// while
while counter > 0 {
    counter -= 1;
}

// for
for number in 1..4 {
    println!("{}", number);
}
```

Your task: Use a for loop to print numbers 1 through 5.', 'fn main() {
    // Use a for loop to print numbers 1 through 5
    
}', '1
2
3
4
5', 'beginner', 200, 7),

-- Chapter 4: Understanding Ownership
('Ownership Basics', 'Understanding Rust''s ownership system', 'Ownership is Rust''s most unique feature. It enables memory safety without garbage collection.

Ownership Rules:
1. Each value has a single owner
2. When the owner goes out of scope, the value is dropped
3. There can only be one owner at a time

Key concepts:
- Stack vs Heap memory
- Move semantics
- Copy trait
- Drop trait

Example:
```rust
let s1 = String::from("hello");
let s2 = s1; // s1 is moved to s2, s1 is no longer valid
// println!("{}", s1); // This would cause an error
println!("{}", s2); // This works
```

Your task: Demonstrate ownership with String types.', 'fn main() {
    let s1 = String::from("Hello");
    
    // Move s1 to s2
    
    // Try to print s2 (this should work)
    
    // What happens if you try to print s1?
}', 'Hello', 'intermediate', 250, 8),

('References and Borrowing', 'Learn about borrowing in Rust', 'References allow you to use a value without taking ownership of it. This is called "borrowing".

Key concepts:
- `&` creates a reference (borrowing)
- References are immutable by default
- `&mut` creates a mutable reference
- You can have either one mutable reference OR multiple immutable references

Rules:
- References must always be valid
- No dangling references
- Data race prevention at compile time

Your task: Create a function that borrows a string and returns its length.', 'fn main() {
    let s = String::from("Rustnite");
    let len = calculate_length(&s);
    
    println!("The length of ''{}'' is {}.", s, len);
}

// Write your function here that takes a string reference
// and returns its length
fn calculate_length(s: &String) -> usize {
    
}', 'The length of ''Rustnite'' is 8.', 'intermediate', 250, 9),

('The Slice Type', 'Working with slices in Rust', 'Slices let you reference a contiguous sequence of elements in a collection rather than the whole collection.

String slices:
- `&str` is a string slice
- `&s[0..5]` creates a slice of first 5 characters
- `&s[..]` creates a slice of the entire string

Array slices:
- `&[i32]` is a slice of integers
- `&arr[1..3]` creates a slice of elements 1 and 2

Slices are references, so they don''t have ownership.

Your task: Write a function that returns the first word of a string.', 'fn main() {
    let sentence = String::from("Hello Rust world");
    let word = first_word(&sentence);
    println!("First word: {}", word);
}

// Write a function that returns the first word of a string
// Hint: find the first space and return a slice up to that point
fn first_word(s: &String) -> &str {
    
}', 'First word: Hello', 'intermediate', 275, 10),

-- Chapter 5: Using Structs
('Defining Structs', 'Define custom data structures', 'Structs let you create custom data types that are meaningful for your domain. They''re similar to objects in other languages.

Struct syntax:
```rust
struct User {
    username: String,
    email: String,
    sign_in_count: u64,
    active: bool,
}
```

Creating instances:
```rust
let user1 = User {
    email: String::from("someone@example.com"),
    username: String::from("someusername123"),
    active: true,
    sign_in_count: 1,
};
```

Your task: Define a User struct and create an instance.', 'struct User {
    // Define the fields: username (String), email (String), active (bool)
    
}

fn main() {
    let user = User {
        // Initialize the fields
        
    };
    
    println!("User: {}", user.username);
}', 'User: rustacean', 'intermediate', 300, 11),

('Method Syntax', 'Add methods to structs', 'Methods are functions defined within the context of a struct. They''re defined in `impl` blocks.

Method types:
- Instance methods: `&self`, `&mut self`, or `self`
- Associated functions: no `self` parameter (like constructors)

Example:
```rust
impl Rectangle {
    fn area(&self) -> u32 {
        self.width * self.height
    }
    
    fn new(width: u32, height: u32) -> Rectangle {
        Rectangle { width, height }
    }
}
```

Your task: Implement methods for a Rectangle struct.', 'struct Rectangle {
    width: u32,
    height: u32,
}

impl Rectangle {
    // Implement an area method that returns width * height
    
    // Implement a constructor function called new
    
}

fn main() {
    let rect = Rectangle::new(30, 50);
    println!("Area: {}", rect.area());
}', 'Area: 1500', 'intermediate', 325, 12),

-- Chapter 6: Enums and Pattern Matching
('Defining Enums', 'Master Rust''s powerful enums', 'Enums allow you to define a type by enumerating its possible variants. Rust''s enums are more powerful than in many languages.

Basic enum:
```rust
enum IpAddrKind {
    V4,
    V6,
}
```

Enums with data:
```rust
enum IpAddr {
    V4(u8, u8, u8, u8),
    V6(String),
}
```

The `Option` enum:
```rust
enum Option<T> {
    Some(T),
    None,
}
```

Your task: Create a Message enum with different variants.', 'enum Message {
    // Define variants: Quit, Move { x: i32, y: i32 }, Write(String), ChangeColor(i32, i32, i32)
    
}

fn main() {
    let msg = Message::Write(String::from("Hello"));
    
    // We''ll learn to handle this with match in the next lesson
    println!("Message created!");
}', 'Message created!', 'intermediate', 275, 13),

('The match Control Flow', 'Pattern matching with match', 'The `match` control flow construct is extremely powerful in Rust. It allows you to compare a value against patterns and execute code based on which pattern matches.

Match syntax:
```rust
match coin {
    Coin::Penny => 1,
    Coin::Nickel => 5,
    Coin::Dime => 10,
    Coin::Quarter => 25,
}
```

Match is exhaustive - you must handle all possible cases or use `_` as a catch-all.

Your task: Use match to handle different Message variants.', 'enum Message {
    Quit,
    Move { x: i32, y: i32 },
    Write(String),
    ChangeColor(i32, i32, i32),
}

fn main() {
    let msg = Message::Write(String::from("Hello"));
    
    match msg {
        // Handle each variant and print appropriate messages
        
    }
}', 'Processing: Hello', 'advanced', 350, 14),

('Concise Control Flow with if let', 'Simplify match with if let', 'The `if let` syntax lets you handle values that match one pattern while ignoring the rest. It''s syntactic sugar for a `match` that runs code when the value matches one pattern and ignores all other values.

Instead of:
```rust
match some_option {
    Some(3) => println!("three"),
    _ => (),
}
```

You can write:
```rust
if let Some(3) = some_option {
    println!("three");
}
```

Your task: Use if let to handle Option values.', 'fn main() {
    let some_number = Some(7);
    let some_string = Some("a string");
    let absent_number: Option<i32> = None;
    
    // Use if let to handle some_number if it contains 7
    
    // Use if let to handle some_string if it contains any value
    
    // Use if let to handle absent_number
    
}', 'Got 7!
Got a string!', 'advanced', 300, 15),

-- Chapter 8: Common Collections
('Vectors', 'Work with dynamic arrays', 'Vectors allow you to store more than one value in a single data structure that puts all the values next to each other in memory. Vectors can only store values of the same type.

Creating vectors:
```rust
let v: Vec<i32> = Vec::new();
let v = vec![1, 2, 3];
```

Common operations:
- `push()` to add elements
- `pop()` to remove last element
- Index with `v[0]` or `v.get(0)`
- Iterate with `for` loops

Your task: Create a vector, add elements, and iterate through them.', 'fn main() {
    let mut numbers = Vec::new();
    
    // Add numbers 1 through 5 to the vector
    
    // Print each number using a for loop
    
}', '1
2
3
4
5', 'intermediate', 250, 16),

('Hash Maps', 'Store key-value pairs', 'Hash maps store data in key-value pairs. They''re useful when you want to look up data by using a key rather than an index.

Creating hash maps:
```rust
use std::collections::HashMap;

let mut scores = HashMap::new();
scores.insert(String::from("Blue"), 10);
scores.insert(String::from("Yellow"), 50);
```

Common operations:
- `insert()` to add key-value pairs
- `get()` to retrieve values
- `entry()` for conditional insertion

Your task: Create a hash map to store team scores.', 'use std::collections::HashMap;

fn main() {
    let mut scores = HashMap::new();
    
    // Insert team scores: Blue = 10, Red = 50
    
    // Print the score for Blue team
    
}', '10', 'intermediate', 275, 17),

-- Chapter 9: Error Handling
('Unrecoverable Errors with panic!', 'Handle program crashes', 'Sometimes, bad things happen in your code, and there''s nothing you can do about it. Rust has the `panic!` macro for these cases.

When `panic!` is called:
1. Program prints a failure message
2. Unwinds and cleans up the stack
3. Quits the program

Example:
```rust
panic!("crash and burn");
```

You can also cause panics by accessing invalid array indices or unwrapping None values.

Your task: Write code that demonstrates controlled panic handling.', 'fn main() {
    let v = vec![1, 2, 3];
    
    // This will panic - accessing invalid index
    // Uncomment the next line to see panic in action:
    // v[99];
    
    println!("This runs before any panic");
    
    // Use panic! macro with a custom message
    
}', 'This runs before any panic', 'advanced', 200, 18),

('Recoverable Errors with Result', 'Handle errors the Rust way', 'Most errors aren''t serious enough to require the program to stop entirely. The `Result` enum handles recoverable errors.

Result definition:
```rust
enum Result<T, E> {
    Ok(T),
    Err(E),
}
```

Common methods:
- `unwrap()` - get value or panic
- `expect()` - unwrap with custom panic message
- `match` - handle both cases
- `?` operator - propagate errors

Your task: Parse strings to numbers and handle errors.', 'fn main() {
    let numbers = vec!["42", "not_a_number", "100"];
    
    for num_str in numbers {
        // Parse each string to i32 and handle the Result
        // Print success or error messages
        
    }
}', 'Parsed: 42
Error parsing: not_a_number
Parsed: 100', 'advanced', 400, 19),

-- Chapter 10: Generic Types, Traits, and Lifetimes
('Generic Data Types', 'Write flexible, reusable code', 'Generics allow you to write code that works with multiple types. They''re Rust''s way of achieving code reuse without sacrificing performance.

Generic functions:
```rust
fn largest<T>(list: &[T]) -> &T {
    // implementation
}
```

Generic structs:
```rust
struct Point<T> {
    x: T,
    y: T,
}
```

Generic enums (like Option<T> and Result<T, E>)

Your task: Create a generic function that finds the largest item in a slice.', 'fn largest<T: PartialOrd + Copy>(list: &[T]) -> T {
    // Implement finding the largest element
    // Hint: start with the first element and compare with the rest
    
}

fn main() {
    let number_list = vec![34, 50, 25, 100, 65];
    let result = largest(&number_list);
    println!("The largest number is {}", result);
    
    let char_list = vec![''y'', ''m'', ''a'', ''q''];
    let result = largest(&char_list);
    println!("The largest char is {}", result);
}', 'The largest number is 100
The largest char is y', 'advanced', 450, 20);

-- Enhanced badges with more variety
INSERT INTO badges (name, description, icon, requirement_type, requirement_value) VALUES
-- Learning Milestones
('First Steps', 'Complete your first lesson', 'fas fa-baby', 'lessons_completed', 1),
('Getting Started', 'Complete 5 lessons', 'fas fa-walking', 'lessons_completed', 5),
('Dedicated Learner', 'Complete 10 lessons', 'fas fa-graduation-cap', 'lessons_completed', 10),
('Rust Apprentice', 'Complete 15 lessons', 'fas fa-hammer', 'lessons_completed', 15),
('Rust Journeyman', 'Complete 20 lessons', 'fas fa-wrench', 'lessons_completed', 20),

-- XP Achievements
('XP Hunter', 'Earn 1000 XP', 'fas fa-star', 'xp_earned', 1000),
('XP Master', 'Earn 3000 XP', 'fas fa-trophy', 'xp_earned', 3000),
('XP Legend', 'Earn 5000 XP', 'fas fa-crown', 'xp_earned', 5000),
('XP Overlord', 'Earn 10000 XP', 'fas fa-gem', 'xp_earned', 10000),

-- Level Achievements
('Level 5 Warrior', 'Reach level 5', 'fas fa-shield-alt', 'level_reached', 5),
('Level 10 Champion', 'Reach level 10', 'fas fa-crown', 'level_reached', 10),
('Level 15 Master', 'Reach level 15', 'fas fa-fire', 'level_reached', 15),
('Level 20 Legend', 'Reach level 20', 'fas fa-dragon', 'level_reached', 20),

-- Streak Achievements
('Consistent Learner', 'Maintain a 3-day learning streak', 'fas fa-calendar-check', 'streak_days', 3),
('Dedicated Student', 'Maintain a 7-day learning streak', 'fas fa-fire', 'streak_days', 7),
('Learning Machine', 'Maintain a 14-day learning streak', 'fas fa-bolt', 'streak_days', 14),
('Unstoppable Force', 'Maintain a 30-day learning streak', 'fas fa-rocket', 'streak_days', 30),

-- Difficulty Achievements
('Beginner Graduate', 'Complete all beginner lessons', 'fas fa-seedling', 'difficulty_complete', 1),
('Intermediate Expert', 'Complete all intermediate lessons', 'fas fa-tree', 'difficulty_complete', 2),
('Advanced Master', 'Complete all advanced lessons', 'fas fa-mountain', 'difficulty_complete', 3),

-- Social Achievements
('Code Sharer', 'Share your first code snippet', 'fas fa-share-alt', 'code_shared', 1),
('Helpful Coder', 'Share 5 code snippets', 'fas fa-hands-helping', 'code_shared', 5),
('Community Leader', 'Share 10 code snippets', 'fas fa-users', 'code_shared', 10),

-- Speed Achievements
('Quick Learner', 'Complete 3 lessons in one day', 'fas fa-tachometer-alt', 'daily_lessons', 3),
('Speed Demon', 'Complete 5 lessons in one day', 'fas fa-lightning-bolt', 'daily_lessons', 5),
('Learning Tornado', 'Complete 10 lessons in one day', 'fas fa-tornado', 'daily_lessons', 10),

-- Special Achievements
('Early Bird', 'Complete a lesson before 8 AM', 'fas fa-sun', 'special', 1),
('Night Owl', 'Complete a lesson after 10 PM', 'fas fa-moon', 'special', 2),
('Weekend Warrior', 'Complete lessons on both Saturday and Sunday', 'fas fa-calendar-weekend', 'special', 3),
('Perfect Week', 'Complete at least one lesson every day for a week', 'fas fa-star-of-life', 'special', 4),

-- Rank Achievements
('Top 100', 'Reach top 100 on the leaderboard', 'fas fa-medal', 'rank_achieved', 100),
('Top 50', 'Reach top 50 on the leaderboard', 'fas fa-award', 'rank_achieved', 50),
('Top 10', 'Reach top 10 on the leaderboard', 'fas fa-trophy', 'rank_achieved', 10),
('Elite Rustacean', 'Reach top 5 on the leaderboard', 'fas fa-crown', 'rank_achieved', 5),
('Rust Champion', 'Reach #1 on the leaderboard', 'fas fa-chess-king', 'rank_achieved', 1);

-- Add social features to users table
ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN github_username VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN twitter_username VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN website VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN location VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN public_profile TINYINT(1) DEFAULT 0;

-- Create code sharing table
CREATE TABLE IF NOT EXISTS code_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    code TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    likes_count INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_lesson (lesson_id)
);

-- Create code likes table
CREATE TABLE IF NOT EXISTS code_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code_share_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (code_share_id) REFERENCES code_shares(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, code_share_id)
);

-- Create user following system
CREATE TABLE IF NOT EXISTS user_follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id),
    INDEX idx_follower (follower_id),
    INDEX idx_following (following_id)
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('follow', 'like', 'badge_earned', 'level_up', 'lesson_completed') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON DEFAULT NULL,
    read_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, read_at),
    INDEX idx_created (created_at)
);