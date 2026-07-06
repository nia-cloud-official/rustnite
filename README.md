# Rustnite

A modern coding platform inspired by competitive gaming, where developers improve their skills through interactive lessons, coding challenges, multiplayer competitions, and AI-assisted learning.

---

## Overview

Rustnite combines structured learning, competitive programming, and AI-powered assistance into a single platform. Users earn experience, unlock achievements, compete against other developers, and practice across multiple programming languages.

---

## Features

### Coding Arena

- Competitive coding challenges
- Multiplayer battle royale matches
- Language-specific leaderboards
- Experience and progression system

### AI Learning

- AI-powered coding tutor
- Dynamic lesson generation
- AI-generated coding challenges
- AI-generated mini-games

### Supported Languages

- Rust
- Python
- JavaScript
- TypeScript
- Go
- Java
- C++
- C

### Gamification

- XP and leveling
- Achievement system
- Daily challenges
- Login streaks
- Global and language-specific leaderboards

### Mini Games

- Code Rush
- Bug Squasher
- Syntax Sprint
- Pattern Match

### Community

- User profiles
- Developer feed
- Code sharing
- Follow system
- Real-time notifications

### Developer Experience

- Monaco Editor integration
- Real code execution
- Syntax highlighting
- Code formatting and linting
- GitHub OAuth authentication

---

## Technology Stack

| Layer | Technology |
|--------|------------|
| Backend | PHP 8+ |
| Database | MariaDB / MySQL |
| Frontend | Tailwind CSS |
| Editor | Monaco Editor |
| AI | OpenCode Big Pickle |
| Authentication | Email/Password, GitHub OAuth |
| Hosting | Wasmer Edge |

---

## Getting Started

### Requirements

- PHP 8.0+
- MariaDB or MySQL
- Apache or Nginx
- cURL extension
- PDO MySQL extension

### Installation

Clone the repository.

```bash
git clone https://github.com/nia-cloud-official/rustnite.git
cd rustnite
```

Configure your database credentials in `config.php`.

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'rustnite');
define('DB_USER', 'username');
define('DB_PASS', 'password');
```

Configure your OpenCode API key.

```php
define('OPENCODE_API_KEY', 'your-api-key');
```

(Optional) Configure GitHub OAuth credentials.

Start your web server and open the application in your browser.

---

## AI Integration

Rustnite integrates the OpenCode Zen API using the **Big Pickle** model to provide:

- AI tutoring
- Lesson generation
- Challenge generation
- Mini-game generation
- Context-aware coding assistance

---

## Database

Core tables include:

- users
- lessons
- user_progress
- mini_games
- mini_game_scores
- daily_challenges
- notifications
- feed_posts
- badges
- battle_royale tables

Database migrations are handled automatically during application startup.

---

## Roadmap

- Real-time multiplayer battles
- Team competitions
- Tournament mode
- Custom avatars
- Mobile applications
- Additional programming languages

---

## Contributing

Contributions are welcome.

1. Fork the repository.
2. Create a feature branch.
3. Commit your changes.
4. Push your branch.
5. Open a Pull Request.

---

## License

This project is licensed under the MIT License.

See the `LICENSE` file for details.

---

## Acknowledgements

- OpenCode
- Monaco Editor
- Tailwind CSS
- Font Awesome
