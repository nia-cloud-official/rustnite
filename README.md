# Rustnite

A multi-language battle-royale coding arena inspired by Twitch. Learn, compete, and level up across 8 programming languages with AI-powered lessons, real compilers, and community features.

---

## Features

### Battle Royale Arena
- Real-time coding competitions
- Multiplayer matches (solo, duo, squad)
- Language-specific leaderboards
- XP and leveling system

### AI Learning (Powered by OpenCode Big Pickle)
- AI coding tutor with markdown responses
- Dynamic lesson generation per language
- Auto-generated coding exercises
- AI-generated mini-games
- Automatic lesson content when none exist

### 8 Supported Languages
- Rust, Python, JavaScript, TypeScript, Go, Java, C++, C

### Gamification
- XP and leveling system
- Achievement badges
- Daily challenges
- Login streaks
- Global and language-specific leaderboards

### Mini Games
- Syntax Speed — Type correct syntax as fast as possible
- Bug Hunt — Find and fix bugs in code
- Output Prediction — Predict code output
- Code Race — Race to complete code

### Community
- Feed page for posts, questions, blogs, ideas
- Like and comment system
- User profiles with stats
- Notification system
- GitHub OAuth login

### Code Editor
- Monaco Editor with language-specific syntax highlighting
- Real code execution via Piston API
- Code linting and formatting
- Multi-language support

---

## Technology Stack

| Layer | Technology |
|--------|------------|
| Backend | PHP 8+ |
| Database | MariaDB / MySQL |
| Frontend | Tailwind CSS |
| Editor | Monaco Editor |
| AI | OpenCode Big Pickle (`big-pickle`) |
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

Configure GitHub OAuth credentials.

```php
define('GITHUB_CLIENT_ID', 'your-client-id');
define('GITHUB_CLIENT_SECRET', 'your-client-secret');
```

Point your web server to the `rustnite/` directory and open the app in your browser. The database schema and tables are created automatically on first load.

---

## API Integration

Rustnite uses the OpenCode Zen API (`https://opencode.ai/zen/v1/chat/completions`) with the **big-pickle** model for:

- AI tutoring and code explanations
- Dynamic lesson content generation
- Coding exercise generation
- Mini-game creation

---

## Database

Tables are auto-created by `includes/db.php` on first page load. Migrations for new columns run automatically on each request.

---

## Configuration

All settings in `config.php`:

| Key | Description |
|-----|-------------|
| `OPENCODE_API_KEY` | OpenCode Big Pickle API key |
| `GITHUB_CLIENT_ID` | GitHub OAuth App client ID |
| `GITHUB_CLIENT_SECRET` | GitHub OAuth App client secret |
| `AI_TUTOR_ENABLED` | Enable/disable AI tutor |
| `AI_TUTOR_MODEL` | Model ID (`big-pickle`) |
| `AI_TUTOR_MAX_TOKENS` | Max tokens per response |
| `AI_TUTOR_TEMPERATURE` | Response creativity (0.0-1.0) |

---

## License

This project is licensed under the MIT License.
