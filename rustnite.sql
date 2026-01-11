-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 11, 2026 at 02:58 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rustnite`
--

-- --------------------------------------------------------

--
-- Table structure for table `badges`
--

CREATE TABLE `badges` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `requirement_type` enum('lessons_completed','xp_earned','streak_days','level_reached','difficulty_complete','code_shared','daily_lessons','rank_achieved','special') NOT NULL,
  `requirement_value` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `badges`
--

INSERT INTO `badges` (`id`, `name`, `description`, `icon`, `requirement_type`, `requirement_value`) VALUES
(81, 'First Steps', 'Complete your first lesson', 'fas fa-baby', 'lessons_completed', 1),
(82, 'Getting Started', 'Complete 5 lessons', 'fas fa-walking', 'lessons_completed', 5),
(83, 'Dedicated Learner', 'Complete 10 lessons', 'fas fa-graduation-cap', 'lessons_completed', 10),
(84, 'Rust Apprentice', 'Complete 15 lessons', 'fas fa-hammer', 'lessons_completed', 15),
(85, 'Rust Journeyman', 'Complete 20 lessons', 'fas fa-wrench', 'lessons_completed', 20),
(86, 'XP Hunter', 'Earn 1000 XP', 'fas fa-star', 'xp_earned', 1000),
(87, 'XP Master', 'Earn 3000 XP', 'fas fa-trophy', 'xp_earned', 3000),
(88, 'XP Legend', 'Earn 5000 XP', 'fas fa-crown', 'xp_earned', 5000),
(89, 'XP Overlord', 'Earn 10000 XP', 'fas fa-gem', 'xp_earned', 10000),
(90, 'Level 5 Warrior', 'Reach level 5', 'fas fa-shield-alt', 'level_reached', 5),
(91, 'Level 10 Champion', 'Reach level 10', 'fas fa-crown', 'level_reached', 10),
(92, 'Level 15 Master', 'Reach level 15', 'fas fa-fire', 'level_reached', 15),
(93, 'Level 20 Legend', 'Reach level 20', 'fas fa-dragon', 'level_reached', 20),
(94, 'Consistent Learner', 'Maintain a 3-day learning streak', 'fas fa-calendar-check', 'streak_days', 3),
(95, 'Dedicated Student', 'Maintain a 7-day learning streak', 'fas fa-fire', 'streak_days', 7),
(96, 'Learning Machine', 'Maintain a 14-day learning streak', 'fas fa-bolt', 'streak_days', 14),
(97, 'Unstoppable Force', 'Maintain a 30-day learning streak', 'fas fa-rocket', 'streak_days', 30),
(98, 'Beginner Graduate', 'Complete all beginner lessons', 'fas fa-seedling', 'difficulty_complete', 1),
(99, 'Intermediate Expert', 'Complete all intermediate lessons', 'fas fa-tree', 'difficulty_complete', 2),
(100, 'Advanced Master', 'Complete all advanced lessons', 'fas fa-mountain', 'difficulty_complete', 3),
(101, 'Code Sharer', 'Share your first code snippet', 'fas fa-share-alt', 'code_shared', 1),
(102, 'Helpful Coder', 'Share 5 code snippets', 'fas fa-hands-helping', 'code_shared', 5),
(103, 'Community Leader', 'Share 10 code snippets', 'fas fa-users', 'code_shared', 10),
(104, 'Quick Learner', 'Complete 3 lessons in one day', 'fas fa-tachometer-alt', 'daily_lessons', 3),
(105, 'Speed Demon', 'Complete 5 lessons in one day', 'fas fa-lightning-bolt', 'daily_lessons', 5),
(106, 'Learning Tornado', 'Complete 10 lessons in one day', 'fas fa-tornado', 'daily_lessons', 10),
(107, 'Early Bird', 'Complete a lesson before 8 AM', 'fas fa-sun', 'special', 1),
(108, 'Night Owl', 'Complete a lesson after 10 PM', 'fas fa-moon', 'special', 2),
(109, 'Weekend Warrior', 'Complete lessons on both Saturday and Sunday', 'fas fa-calendar-weekend', 'special', 3),
(110, 'Perfect Week', 'Complete at least one lesson every day for a week', 'fas fa-star-of-life', 'special', 4),
(111, 'Top 100', 'Reach top 100 on the leaderboard', 'fas fa-medal', 'rank_achieved', 100),
(112, 'Top 50', 'Reach top 50 on the leaderboard', 'fas fa-award', 'rank_achieved', 50),
(113, 'Top 10', 'Reach top 10 on the leaderboard', 'fas fa-trophy', 'rank_achieved', 10),
(114, 'Elite Rustacean', 'Reach top 5 on the leaderboard', 'fas fa-crown', 'rank_achieved', 5),
(115, 'Rust Champion', 'Reach #1 on the leaderboard', 'fas fa-chess-king', 'rank_achieved', 1);

-- --------------------------------------------------------

--
-- Table structure for table `code_likes`
--

CREATE TABLE `code_likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code_share_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `code_shares`
--

CREATE TABLE `code_shares` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `code` text NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `likes_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `content` text NOT NULL,
  `code_template` text DEFAULT NULL,
  `expected_output` text DEFAULT NULL,
  `difficulty` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `xp_reward` int(11) DEFAULT 100,
  `order_num` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `title`, `description`, `content`, `code_template`, `expected_output`, `difficulty`, `xp_reward`, `order_num`, `created_at`) VALUES
(1, 'Hello, World!', 'Your first Rust program - the traditional starting point', 'Welcome to Rust! Let\'s start with the classic \"Hello, World!\" program.\r\n\r\nIn Rust, we use the `println!` macro to print text to the console. The exclamation mark indicates it\'s a macro, not a function.\r\n\r\nKey concepts:\r\n- `fn main()` is the entry point of every Rust program\r\n- `println!` is a macro for printing to stdout\r\n- Rust uses curly braces `{}` for code blocks\r\n- Statements end with semicolons\r\n\r\nYour task: Write a program that prints \"Hello, Rustnite!\" to the console.', 'fn main() {\r\n    // Write your code here\r\n    \r\n}', 'Hello, Rustnite!', 'beginner', 100, 1, '2026-01-11 12:45:05'),
(2, 'Variables and Mutability', 'Learn about Rust\'s variable system and mutability', 'In Rust, variables are immutable by default. This is one of Rust\'s key features that helps you write safe code.\r\n\r\nKey concepts:\r\n- Variables are immutable by default\r\n- Use `let` to declare variables\r\n- Use `mut` keyword to make variables mutable\r\n- Rust has strong type inference\r\n\r\nExample:\r\n```rust\r\nlet x = 5;        // immutable\r\nlet mut y = 10;   // mutable\r\ny = 15;           // this works\r\n// x = 6;         // this would cause an error\r\n```\r\n\r\nYour task: Create variables and demonstrate mutability.', 'fn main() {\r\n    let x = 5;\r\n    let mut y = 10;\r\n    \r\n    // Change y to 15\r\n    \r\n    // Print both values\r\n    println!(\"x = {}\", x);\r\n    \r\n}', 'x = 5\r\ny = 15', 'beginner', 150, 2, '2026-01-11 12:45:05'),
(3, 'Data Types', 'Explore Rust\'s type system', 'Rust is a statically typed language, which means it must know the types of all variables at compile time.\r\n\r\nScalar Types:\r\n- Integers: i8, i16, i32, i64, i128, isize, u8, u16, u32, u64, u128, usize\r\n- Floating-point: f32, f64\r\n- Boolean: bool (true/false)\r\n- Character: char (Unicode scalar values)\r\n\r\nCompound Types:\r\n- Tuples: (i32, f64, u8)\r\n- Arrays: [i32; 5]\r\n\r\nYour task: Create variables of different types and print them.', 'fn main() {\r\n    let integer: i32 = 42;\r\n    let float: f64 = 3.14;\r\n    let boolean: bool = true;\r\n    let character: char = \'R\';\r\n    \r\n    // Print all variables using println!\r\n    \r\n}', '42\r\n3.14\r\ntrue\r\nR', 'beginner', 150, 3, '2026-01-11 12:45:05'),
(4, 'Functions', 'Write your first Rust function', 'Functions are prevalent in Rust code. The most important function is `main`, which is the entry point of many programs.\r\n\r\nFunction syntax:\r\n- Declared with `fn` keyword\r\n- Parameters must have type annotations\r\n- Return type specified with `->`\r\n- Return value is the final expression (no semicolon)\r\n\r\nExample:\r\n```rust\r\nfn add_numbers(x: i32, y: i32) -> i32 {\r\n    x + y  // no semicolon = return value\r\n}\r\n```\r\n\r\nYour task: Create a function that adds two numbers and call it from main.', 'fn main() {\r\n    let result = add(5, 3);\r\n    println!(\"5 + 3 = {}\", result);\r\n}\r\n\r\n// Write your add function here\r\nfn add(x: i32, y: i32) -> i32 {\r\n    // Return the sum of x and y\r\n    \r\n}', '5 + 3 = 8', 'beginner', 200, 4, '2026-01-11 12:45:05'),
(5, 'Comments and Documentation', 'Learn to document your Rust code', 'Good documentation is crucial for maintainable code. Rust has several types of comments:\r\n\r\nTypes of comments:\r\n- Line comments: `// This is a comment`\r\n- Block comments: `/* This is a block comment */`\r\n- Doc comments: `/// This documents the next item`\r\n- Module doc comments: `//! This documents the enclosing item`\r\n\r\nDoc comments support Markdown and can include code examples that are tested!\r\n\r\nYour task: Add comments and documentation to a simple function.', 'fn main() {\r\n    let result = calculate_area(5.0, 3.0);\r\n    println!(\"Area: {}\", result);\r\n}\r\n\r\n/// Calculate the area of a rectangle\r\n/// \r\n/// # Arguments\r\n/// \r\n/// * `length` - The length of the rectangle\r\n/// * `width` - The width of the rectangle\r\n/// \r\n/// # Examples\r\n/// \r\n/// ```\r\n/// let area = calculate_area(5.0, 3.0);\r\n/// assert_eq!(area, 15.0);\r\n/// ```\r\nfn calculate_area(length: f64, width: f64) -> f64 {\r\n    // TODO: Implement the calculation\r\n    \r\n}', 'Area: 15', 'beginner', 150, 5, '2026-01-11 12:45:05'),
(6, 'Control Flow - if/else', 'Learn conditional logic in Rust', 'Control flow constructs allow you to control the order in which code runs. The most common are `if` expressions.\r\n\r\nKey points:\r\n- `if` is an expression, not a statement\r\n- Conditions must be `bool` type (no truthy/falsy values)\r\n- You can use `if` in `let` statements\r\n- Arms must return the same type\r\n\r\nExample:\r\n```rust\r\nlet number = 6;\r\nif number % 4 == 0 {\r\n    println!(\"divisible by 4\");\r\n} else if number % 3 == 0 {\r\n    println!(\"divisible by 3\");\r\n} else {\r\n    println!(\"not divisible by 4 or 3\");\r\n}\r\n```\r\n\r\nYour task: Write a program that checks if a number is positive, negative, or zero.', 'fn main() {\r\n    let number = -5;\r\n    \r\n    // Write if/else logic to check if number is positive, negative, or zero\r\n    // Print appropriate messages\r\n    \r\n}', 'The number is negative', 'beginner', 175, 6, '2026-01-11 12:45:05'),
(7, 'Loops', 'Master Rust\'s looping constructs', 'Rust has three kinds of loops: `loop`, `while`, and `for`.\r\n\r\nLoop types:\r\n- `loop` - infinite loop until `break`\r\n- `while` - conditional loop\r\n- `for` - iterator loop (most common)\r\n\r\nExamples:\r\n```rust\r\n// loop\r\nlet mut counter = 0;\r\nloop {\r\n    counter += 1;\r\n    if counter == 10 { break; }\r\n}\r\n\r\n// while\r\nwhile counter > 0 {\r\n    counter -= 1;\r\n}\r\n\r\n// for\r\nfor number in 1..4 {\r\n    println!(\"{}\", number);\r\n}\r\n```\r\n\r\nYour task: Use a for loop to print numbers 1 through 5.', 'fn main() {\r\n    // Use a for loop to print numbers 1 through 5\r\n    \r\n}', '1\r\n2\r\n3\r\n4\r\n5', 'beginner', 200, 7, '2026-01-11 12:45:05'),
(8, 'Ownership Basics', 'Understanding Rust\'s ownership system', 'Ownership is Rust\'s most unique feature. It enables memory safety without garbage collection.\r\n\r\nOwnership Rules:\r\n1. Each value has a single owner\r\n2. When the owner goes out of scope, the value is dropped\r\n3. There can only be one owner at a time\r\n\r\nKey concepts:\r\n- Stack vs Heap memory\r\n- Move semantics\r\n- Copy trait\r\n- Drop trait\r\n\r\nExample:\r\n```rust\r\nlet s1 = String::from(\"hello\");\r\nlet s2 = s1; // s1 is moved to s2, s1 is no longer valid\r\n// println!(\"{}\", s1); // This would cause an error\r\nprintln!(\"{}\", s2); // This works\r\n```\r\n\r\nYour task: Demonstrate ownership with String types.', 'fn main() {\r\n    let s1 = String::from(\"Hello\");\r\n    \r\n    // Move s1 to s2\r\n    \r\n    // Try to print s2 (this should work)\r\n    \r\n    // What happens if you try to print s1?\r\n}', 'Hello', 'intermediate', 250, 8, '2026-01-11 12:45:05'),
(9, 'References and Borrowing', 'Learn about borrowing in Rust', 'References allow you to use a value without taking ownership of it. This is called \"borrowing\".\r\n\r\nKey concepts:\r\n- `&` creates a reference (borrowing)\r\n- References are immutable by default\r\n- `&mut` creates a mutable reference\r\n- You can have either one mutable reference OR multiple immutable references\r\n\r\nRules:\r\n- References must always be valid\r\n- No dangling references\r\n- Data race prevention at compile time\r\n\r\nYour task: Create a function that borrows a string and returns its length.', 'fn main() {\r\n    let s = String::from(\"Rustnite\");\r\n    let len = calculate_length(&s);\r\n    \r\n    println!(\"The length of \'{}\' is {}.\", s, len);\r\n}\r\n\r\n// Write your function here that takes a string reference\r\n// and returns its length\r\nfn calculate_length(s: &String) -> usize {\r\n    \r\n}', 'The length of \'Rustnite\' is 8.', 'intermediate', 250, 9, '2026-01-11 12:45:05'),
(10, 'The Slice Type', 'Working with slices in Rust', 'Slices let you reference a contiguous sequence of elements in a collection rather than the whole collection.\r\n\r\nString slices:\r\n- `&str` is a string slice\r\n- `&s[0..5]` creates a slice of first 5 characters\r\n- `&s[..]` creates a slice of the entire string\r\n\r\nArray slices:\r\n- `&[i32]` is a slice of integers\r\n- `&arr[1..3]` creates a slice of elements 1 and 2\r\n\r\nSlices are references, so they don\'t have ownership.\r\n\r\nYour task: Write a function that returns the first word of a string.', 'fn main() {\r\n    let sentence = String::from(\"Hello Rust world\");\r\n    let word = first_word(&sentence);\r\n    println!(\"First word: {}\", word);\r\n}\r\n\r\n// Write a function that returns the first word of a string\r\n// Hint: find the first space and return a slice up to that point\r\nfn first_word(s: &String) -> &str {\r\n    \r\n}', 'First word: Hello', 'intermediate', 275, 10, '2026-01-11 12:45:05'),
(11, 'Defining Structs', 'Define custom data structures', 'Structs let you create custom data types that are meaningful for your domain. They\'re similar to objects in other languages.\r\n\r\nStruct syntax:\r\n```rust\r\nstruct User {\r\n    username: String,\r\n    email: String,\r\n    sign_in_count: u64,\r\n    active: bool,\r\n}\r\n```\r\n\r\nCreating instances:\r\n```rust\r\nlet user1 = User {\r\n    email: String::from(\"someone@example.com\"),\r\n    username: String::from(\"someusername123\"),\r\n    active: true,\r\n    sign_in_count: 1,\r\n};\r\n```\r\n\r\nYour task: Define a User struct and create an instance.', 'struct User {\r\n    // Define the fields: username (String), email (String), active (bool)\r\n    \r\n}\r\n\r\nfn main() {\r\n    let user = User {\r\n        // Initialize the fields\r\n        \r\n    };\r\n    \r\n    println!(\"User: {}\", user.username);\r\n}', 'User: rustacean', 'intermediate', 300, 11, '2026-01-11 12:45:05'),
(12, 'Method Syntax', 'Add methods to structs', 'Methods are functions defined within the context of a struct. They\'re defined in `impl` blocks.\r\n\r\nMethod types:\r\n- Instance methods: `&self`, `&mut self`, or `self`\r\n- Associated functions: no `self` parameter (like constructors)\r\n\r\nExample:\r\n```rust\r\nimpl Rectangle {\r\n    fn area(&self) -> u32 {\r\n        self.width * self.height\r\n    }\r\n    \r\n    fn new(width: u32, height: u32) -> Rectangle {\r\n        Rectangle { width, height }\r\n    }\r\n}\r\n```\r\n\r\nYour task: Implement methods for a Rectangle struct.', 'struct Rectangle {\r\n    width: u32,\r\n    height: u32,\r\n}\r\n\r\nimpl Rectangle {\r\n    // Implement an area method that returns width * height\r\n    \r\n    // Implement a constructor function called new\r\n    \r\n}\r\n\r\nfn main() {\r\n    let rect = Rectangle::new(30, 50);\r\n    println!(\"Area: {}\", rect.area());\r\n}', 'Area: 1500', 'intermediate', 325, 12, '2026-01-11 12:45:05'),
(13, 'Defining Enums', 'Master Rust\'s powerful enums', 'Enums allow you to define a type by enumerating its possible variants. Rust\'s enums are more powerful than in many languages.\r\n\r\nBasic enum:\r\n```rust\r\nenum IpAddrKind {\r\n    V4,\r\n    V6,\r\n}\r\n```\r\n\r\nEnums with data:\r\n```rust\r\nenum IpAddr {\r\n    V4(u8, u8, u8, u8),\r\n    V6(String),\r\n}\r\n```\r\n\r\nThe `Option` enum:\r\n```rust\r\nenum Option<T> {\r\n    Some(T),\r\n    None,\r\n}\r\n```\r\n\r\nYour task: Create a Message enum with different variants.', 'enum Message {\r\n    // Define variants: Quit, Move { x: i32, y: i32 }, Write(String), ChangeColor(i32, i32, i32)\r\n    \r\n}\r\n\r\nfn main() {\r\n    let msg = Message::Write(String::from(\"Hello\"));\r\n    \r\n    // We\'ll learn to handle this with match in the next lesson\r\n    println!(\"Message created!\");\r\n}', 'Message created!', 'intermediate', 275, 13, '2026-01-11 12:45:05'),
(14, 'The match Control Flow', 'Pattern matching with match', 'The `match` control flow construct is extremely powerful in Rust. It allows you to compare a value against patterns and execute code based on which pattern matches.\r\n\r\nMatch syntax:\r\n```rust\r\nmatch coin {\r\n    Coin::Penny => 1,\r\n    Coin::Nickel => 5,\r\n    Coin::Dime => 10,\r\n    Coin::Quarter => 25,\r\n}\r\n```\r\n\r\nMatch is exhaustive - you must handle all possible cases or use `_` as a catch-all.\r\n\r\nYour task: Use match to handle different Message variants.', 'enum Message {\r\n    Quit,\r\n    Move { x: i32, y: i32 },\r\n    Write(String),\r\n    ChangeColor(i32, i32, i32),\r\n}\r\n\r\nfn main() {\r\n    let msg = Message::Write(String::from(\"Hello\"));\r\n    \r\n    match msg {\r\n        // Handle each variant and print appropriate messages\r\n        \r\n    }\r\n}', 'Processing: Hello', 'advanced', 350, 14, '2026-01-11 12:45:05'),
(15, 'Concise Control Flow with if let', 'Simplify match with if let', 'The `if let` syntax lets you handle values that match one pattern while ignoring the rest. It\'s syntactic sugar for a `match` that runs code when the value matches one pattern and ignores all other values.\r\n\r\nInstead of:\r\n```rust\r\nmatch some_option {\r\n    Some(3) => println!(\"three\"),\r\n    _ => (),\r\n}\r\n```\r\n\r\nYou can write:\r\n```rust\r\nif let Some(3) = some_option {\r\n    println!(\"three\");\r\n}\r\n```\r\n\r\nYour task: Use if let to handle Option values.', 'fn main() {\r\n    let some_number = Some(7);\r\n    let some_string = Some(\"a string\");\r\n    let absent_number: Option<i32> = None;\r\n    \r\n    // Use if let to handle some_number if it contains 7\r\n    \r\n    // Use if let to handle some_string if it contains any value\r\n    \r\n    // Use if let to handle absent_number\r\n    \r\n}', 'Got 7!\r\nGot a string!', 'advanced', 300, 15, '2026-01-11 12:45:05'),
(16, 'Vectors', 'Work with dynamic arrays', 'Vectors allow you to store more than one value in a single data structure that puts all the values next to each other in memory. Vectors can only store values of the same type.\r\n\r\nCreating vectors:\r\n```rust\r\nlet v: Vec<i32> = Vec::new();\r\nlet v = vec![1, 2, 3];\r\n```\r\n\r\nCommon operations:\r\n- `push()` to add elements\r\n- `pop()` to remove last element\r\n- Index with `v[0]` or `v.get(0)`\r\n- Iterate with `for` loops\r\n\r\nYour task: Create a vector, add elements, and iterate through them.', 'fn main() {\r\n    let mut numbers = Vec::new();\r\n    \r\n    // Add numbers 1 through 5 to the vector\r\n    \r\n    // Print each number using a for loop\r\n    \r\n}', '1\r\n2\r\n3\r\n4\r\n5', 'intermediate', 250, 16, '2026-01-11 12:45:05'),
(17, 'Hash Maps', 'Store key-value pairs', 'Hash maps store data in key-value pairs. They\'re useful when you want to look up data by using a key rather than an index.\r\n\r\nCreating hash maps:\r\n```rust\r\nuse std::collections::HashMap;\r\n\r\nlet mut scores = HashMap::new();\r\nscores.insert(String::from(\"Blue\"), 10);\r\nscores.insert(String::from(\"Yellow\"), 50);\r\n```\r\n\r\nCommon operations:\r\n- `insert()` to add key-value pairs\r\n- `get()` to retrieve values\r\n- `entry()` for conditional insertion\r\n\r\nYour task: Create a hash map to store team scores.', 'use std::collections::HashMap;\r\n\r\nfn main() {\r\n    let mut scores = HashMap::new();\r\n    \r\n    // Insert team scores: Blue = 10, Red = 50\r\n    \r\n    // Print the score for Blue team\r\n    \r\n}', '10', 'intermediate', 275, 17, '2026-01-11 12:45:05'),
(18, 'Unrecoverable Errors with panic!', 'Handle program crashes', 'Sometimes, bad things happen in your code, and there\'s nothing you can do about it. Rust has the `panic!` macro for these cases.\r\n\r\nWhen `panic!` is called:\r\n1. Program prints a failure message\r\n2. Unwinds and cleans up the stack\r\n3. Quits the program\r\n\r\nExample:\r\n```rust\r\npanic!(\"crash and burn\");\r\n```\r\n\r\nYou can also cause panics by accessing invalid array indices or unwrapping None values.\r\n\r\nYour task: Write code that demonstrates controlled panic handling.', 'fn main() {\r\n    let v = vec![1, 2, 3];\r\n    \r\n    // This will panic - accessing invalid index\r\n    // Uncomment the next line to see panic in action:\r\n    // v[99];\r\n    \r\n    println!(\"This runs before any panic\");\r\n    \r\n    // Use panic! macro with a custom message\r\n    \r\n}', 'This runs before any panic', 'advanced', 200, 18, '2026-01-11 12:45:05'),
(19, 'Recoverable Errors with Result', 'Handle errors the Rust way', 'Most errors aren\'t serious enough to require the program to stop entirely. The `Result` enum handles recoverable errors.\r\n\r\nResult definition:\r\n```rust\r\nenum Result<T, E> {\r\n    Ok(T),\r\n    Err(E),\r\n}\r\n```\r\n\r\nCommon methods:\r\n- `unwrap()` - get value or panic\r\n- `expect()` - unwrap with custom panic message\r\n- `match` - handle both cases\r\n- `?` operator - propagate errors\r\n\r\nYour task: Parse strings to numbers and handle errors.', 'fn main() {\r\n    let numbers = vec![\"42\", \"not_a_number\", \"100\"];\r\n    \r\n    for num_str in numbers {\r\n        // Parse each string to i32 and handle the Result\r\n        // Print success or error messages\r\n        \r\n    }\r\n}', 'Parsed: 42\r\nError parsing: not_a_number\r\nParsed: 100', 'advanced', 400, 19, '2026-01-11 12:45:05'),
(20, 'Generic Data Types', 'Write flexible, reusable code', 'Generics allow you to write code that works with multiple types. They\'re Rust\'s way of achieving code reuse without sacrificing performance.\r\n\r\nGeneric functions:\r\n```rust\r\nfn largest<T>(list: &[T]) -> &T {\r\n    // implementation\r\n}\r\n```\r\n\r\nGeneric structs:\r\n```rust\r\nstruct Point<T> {\r\n    x: T,\r\n    y: T,\r\n}\r\n```\r\n\r\nGeneric enums (like Option<T> and Result<T, E>)\r\n\r\nYour task: Create a generic function that finds the largest item in a slice.', 'fn largest<T: PartialOrd + Copy>(list: &[T]) -> T {\r\n    // Implement finding the largest element\r\n    // Hint: start with the first element and compare with the rest\r\n    \r\n}\r\n\r\nfn main() {\r\n    let number_list = vec![34, 50, 25, 100, 65];\r\n    let result = largest(&number_list);\r\n    println!(\"The largest number is {}\", result);\r\n    \r\n    let char_list = vec![\'y\', \'m\', \'a\', \'q\'];\r\n    let result = largest(&char_list);\r\n    println!(\"The largest char is {}\", result);\r\n}', 'The largest number is 100\r\nThe largest char is y', 'advanced', 450, 20, '2026-01-11 12:45:05');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('follow','like','badge_earned','level_up','lesson_completed') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `xp` int(11) DEFAULT 0,
  `level` int(11) DEFAULT 1,
  `badges` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bio` text DEFAULT NULL,
  `github_username` varchar(100) DEFAULT NULL,
  `twitter_username` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `public_profile` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `xp`, `level`, `badges`, `created_at`, `bio`, `github_username`, `twitter_username`, `website`, `location`, `public_profile`) VALUES
(1, 'miltonhyndrex', 'miltonhyndrex@gmail.com', '$2y$10$lFFKG7br0gijod3Hn8GyOOMVbcFU6rzS1bZ8OEjkZo9RKUi01X6uW', 100, 1, NULL, '2026-01-11 12:07:04', NULL, NULL, NULL, NULL, NULL, 0),
(2, 'demo', 'demo@rustnite.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 1, NULL, '2026-01-11 12:45:05', NULL, NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_follows`
--

CREATE TABLE `user_follows` (
  `id` int(11) NOT NULL,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `code_submitted` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_progress`
--

INSERT INTO `user_progress` (`id`, `user_id`, `lesson_id`, `completed`, `code_submitted`, `completed_at`) VALUES
(1, 1, 1, 1, 'fn main() {\r\n      println!(\"Hello, Rustnite!\");\r\n}', '2026-01-11 12:48:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `code_likes`
--
ALTER TABLE `code_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`user_id`,`code_share_id`),
  ADD KEY `code_share_id` (`code_share_id`);

--
-- Indexes for table `code_shares`
--
ALTER TABLE `code_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_lesson` (`lesson_id`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_unread` (`user_id`,`read_at`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_badge` (`user_id`,`badge_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_badge` (`badge_id`);

--
-- Indexes for table `user_follows`
--
ALTER TABLE `user_follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_follow` (`follower_id`,`following_id`),
  ADD KEY `idx_follower` (`follower_id`),
  ADD KEY `idx_following` (`following_id`);

--
-- Indexes for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_progress` (`user_id`,`lesson_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `code_likes`
--
ALTER TABLE `code_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `code_shares`
--
ALTER TABLE `code_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_follows`
--
ALTER TABLE `user_follows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_progress`
--
ALTER TABLE `user_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `code_likes`
--
ALTER TABLE `code_likes`
  ADD CONSTRAINT `code_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `code_likes_ibfk_2` FOREIGN KEY (`code_share_id`) REFERENCES `code_shares` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `code_shares`
--
ALTER TABLE `code_shares`
  ADD CONSTRAINT `code_shares_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `code_shares_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_follows`
--
ALTER TABLE `user_follows`
  ADD CONSTRAINT `user_follows_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_follows_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
