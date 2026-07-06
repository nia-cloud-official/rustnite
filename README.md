# Rustnite

A multi-language battle-royale coding arena inspired by Twitch. Learn, compete, and level up across 8 programming languages with AI-powered lessons, real code execution via Piston API, and community features.

---

## Features

### 🎮 Battle Royale Arena
- Coding competitions (solo, duo, squad)
- Language-specific leaderboards
- XP and leveling system with streaks

### 🤖 AI Learning (Powered by OpenCode Big Pickle)
- AI coding tutor with rendered markdown and code highlighting
- Dynamic lesson generation for all 8 languages
- Auto-generated coding exercises and daily challenges
- AI-generated mini-games

### 💻 8 Supported Languages
**Rust · Python · JavaScript · TypeScript · Go · Java · C++ · C**

### 🏆 Gamification
- XP and leveling system with streak bonuses
- Achievement badges
- AI-generated daily challenges
- Login streaks with rewards
- Global and language-specific leaderboards

### 🎯 Mini Games
- **Syntax Speed** — Type correct syntax fast
- **Bug Hunt** — Find and fix bugs
- **Output Prediction** — Predict code output
- **Code Race** — Complete code fastest

### 👥 Community
- Feed page for posts, questions, blogs, ideas
- Like and comment system
- User profiles with GitHub avatar
- Notification system
- GitHub OAuth login

### ✨ Code Editor (Monaco)
- Language-specific syntax highlighting
- Real code execution via Piston API
- Multi-language support with correct templates

### 🎨 UI/UX
- Twitch-inspired dark theme
- Smooth animations (slide-up, fade-in, bounce-in)
- Confetti on achievements
- Responsive mobile-friendly design

---

## Technology Stack

| Layer | Technology |
|--------|------------|
| Backend | PHP 8+ |
| Database | MariaDB / MySQL |
| Frontend | Tailwind CSS, Font Awesome |
| Editor | Monaco Editor |
| AI | OpenCode Big Pickle (`big-pickle`) |
| Auth | Email/Password, GitHub OAuth |
| Code Execution | Piston API (emkc.org) |
| Hosting | Wasmer Edge |

---

## Getting Started

### Requirements
- PHP 8.0+
- MariaDB or MySQL
- cURL and PDO MySQL extensions

### Installation

```bash
git clone https://github.com/nia-cloud-official/rustnite.git
cd rustnite
```

Configure your database credentials and API keys in `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'rustnite');
define('DB_USER', 'username');
define('DB_PASS', 'password');
define('OPENCODE_API_KEY', 'sk-your-key-here');
define('GITHUB_CLIENT_ID', 'your-github-client-id');
define('GITHUB_CLIENT_SECRET', 'your-github-client-secret');
```

Point your web server to the `rustnite/` directory and open the app. Database tables are created automatically on first load.

---

## API Integration

Rustnite uses the OpenCode Zen API (`https://opencode.ai/zen/v1/chat/completions`) with the **big-pickle** model for:
- AI tutoring and code explanations
- Dynamic lesson content generation
- Coding exercise and daily challenge generation
- Mini-game creation

### GitHub OAuth

Create a GitHub OAuth App at https://github.com/settings/developers
- Callback URL: `https://rustnite.wasmer.app/index.php?page=login&github_callback=1`

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
| `AI_TUTOR_MAX_TOKENS` | Max tokens per response (default: 1024) |
| `AI_TUTOR_TEMPERATURE` | Response creativity (0.0-1.0) |

---

## License

MIT License
