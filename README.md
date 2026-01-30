# Baseball Stats

A comprehensive baseball statistics tracking and scoring application built with Laravel and Vue.js. This application allows users to track games, manage teams and players, score live games, view real-time statistics, and generate detailed box scores.

## Purpose

Baseball Stats is designed to provide a complete solution for baseball leagues, teams, and enthusiasts to:
- Track and manage baseball seasons, teams, and players
- Score games in real-time with detailed play-by-play tracking
- Generate comprehensive statistics and analytics
- View game history and player performance
- Cast live game data to external displays (Google Cast support)
- Manage team rosters and player information

## Features

### Game Management
- **Create and schedule games** - Set up games with home/away teams, location, and first pitch time
- **Live game scoring** - Real-time play-by-play input with state management
- **Game viewer** - Watch games with live updates
- **Box scores** - Generate detailed box scores for completed games
- **Australian scorebook export** - Export detailed HTML scorebooks that can be printed to PDF
- **Undo functionality** - Correct mistakes with play-by-play undo
- **Game locking** - Prevent accidental changes to completed games
- **Google Cast support** - Display live game data on external displays

### Team & Player Management
- **Team creation and editing** - Manage team information and rosters
- **Season-based organization** - Associate teams with specific seasons
- **Player profiles** - Track individual player statistics and performance
- **Player search** - Quickly find players across all teams
- **Positional tracking** - Monitor player performance by position

### Statistics & Analytics
- **Real-time statistics** - Automatically calculated batting and pitching stats
- **Player statistics** - Individual performance metrics across all games
- **Team statistics** - Aggregate team performance data
- **Balls in play tracking** - Detailed field position analytics
- **Historical data** - Access past games and seasonal performance

### User Management
- **Authentication** - OAuth integration with external providers
- **User preferences** - Customizable settings for users and seasons
- **Role-based permissions** - Control access to scoring and team management features
- **Sanctum API authentication** - Secure API access

### Additional Features
- **Australian scorebook export** - Generate detailed HTML scorebooks matching Australian baseball format
  - Command-line and web interface access
  - Visual indicators for outs, runs (earned/unearned), and inning transitions
  - Player substitution tracking (PH, PR, DSUB)
  - Comprehensive statistics and pitcher tracking
  - Print directly from browser to create PDF
  - See [SCOREBOOK.md](SCOREBOOK.md) for detailed documentation
- **Schedule management** - Import and display game schedules
- **Real-time broadcasting** - Live game updates using Laravel Reverb
- **Responsive design** - Works on desktop and mobile devices
- **Data visualization** - Field diagrams for balls in play using Babylon.js

## Technology Stack

- **Backend:** PHP 8.2+ with Laravel (latest version)
- **Frontend:** Vue.js 3.5+ with Vite
- **Database:** MySQL (via Doctrine DBAL)
- **Real-time:** Laravel Reverb (WebSockets)
- **Authentication:** Laravel Sanctum & Laravel Socialite
- **3D Graphics:** Babylon.js
- **Development Environment:** Lando (Docker-based)

## Prerequisites

Before you begin, ensure you have the following installed:
- PHP 8.2 or higher
- Composer
- Node.js and npm
- MySQL or MariaDB
- Lando (optional, for Docker-based development)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/KindredSpritess/baseball-stats.git
   cd baseball-stats
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Set up environment configuration**
   ```bash
   cp app/.env.example .env
   ```
   
   Edit `.env` and configure your database connection with your actual credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=baseball_stats
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```
   
   For real-time features, configure the broadcast driver and Reverb settings:
   ```
   BROADCAST_DRIVER=reverb
   
   REVERB_APP_ID=your_app_id
   REVERB_APP_KEY=your_app_key
   REVERB_APP_SECRET=your_app_secret
   REVERB_HOST=localhost
   REVERB_PORT=8080
   REVERB_SCHEME=http
   ```
   
   **Note:** Replace `your_username` and `your_password` with your actual MySQL credentials, and ensure the database exists before running migrations. Generate secure values for Reverb credentials.

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Run database migrations**
   ```bash
   php artisan migrate
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   ```
   
   For development with hot reload:
   ```bash
   npm run dev
   ```

## Running the Application

### Using PHP Built-in Server

1. **Start the Laravel development server**
   ```bash
   php artisan serve
   ```
   The application will be available at `http://localhost:8000`

2. **Start the Reverb WebSocket server** (in a separate terminal, **required for real-time features**)
   ```bash
   php artisan reverb:start
   ```
   
   **Note:** The Reverb server is essential for real-time game updates, live scoring, and broadcasting features. Ensure your `.env` file has `BROADCAST_DRIVER=reverb` and the Reverb configuration values are set.

### Using Lando (Recommended)

If you have Lando installed, you can use the included configuration:

1. **Start Lando**
   ```bash
   lando start
   ```

2. **Run migrations**
   ```bash
   lando artisan migrate
   ```

3. **Access the application**
   - Web: `https://baseball.lndo.site`
   - Reverb WebSocket: Port 8080

## Usage

### Basic Workflow

1. **Create a Season** - Organize your games by season
2. **Add Teams** - Create teams and add them to your season
3. **Add Players** - Build team rosters with player information
4. **Schedule Games** - Create games with home/away teams and timing
5. **Score Games** - Use the live scoring interface to track play-by-play action
6. **View Statistics** - Access player and team stats from the dashboard

### Scoring a Game

1. Navigate to a scheduled game
2. Click "Score Game" (requires appropriate permissions)
3. Enter plays using the play-by-play interface
4. The game state updates automatically with scores, outs, and baserunners
5. Use the undo feature if you make a mistake
6. The game will be marked as ended when complete

### Viewing Game Data

- **Live View:** Watch games in progress with real-time updates
- **Box Score:** View detailed statistics after game completion
- **Scorebook:** Export Australian-style scorebook HTML (⚙️ Options → Export Scorebook)
- **Cast:** Display game data on a Google Cast device

## Testing

Run the test suite:
```bash
php artisan test
```

Or with PHPUnit directly:
```bash
./vendor/bin/phpunit
```

## Project Structure

```
baseball-stats/
├── app/
│   ├── Casts/          # Custom model casts (GameState)
│   ├── Console/        # Artisan commands
│   ├── Events/         # Event classes (GameUpdated)
│   ├── Helpers/        # Helper classes (StatsHelper)
│   ├── Http/
│   │   ├── Controllers/ # Game, Team, Person, Stats controllers
│   │   └── Middleware/  # HTTP middleware
│   └── Models/         # Eloquent models (Game, Team, Player, etc.)
├── database/
│   ├── migrations/     # Database schema migrations
│   └── seeders/        # Database seeders
├── public/             # Public web root
├── resources/
│   └── views/          # Blade templates
├── routes/
│   ├── api.php        # API routes
│   ├── web.php        # Web routes
│   └── channels.php   # Broadcasting channels
└── tests/             # Test files
```

## API Endpoints

The application provides both web and API interfaces:

### Web Routes
- `/` - Home dashboard
- `/game/{game}` - Game scoring interface
- `/game/{game}/scorebook` - Export scorebook HTML
- `/game/view/{game}` - Game viewer
- `/team/{team}` - Team details
- `/person/{person}` - Player statistics
- `/stats` - Global statistics

### API Routes

**Public Routes:**
- `GET /api/game/{game}` - Get game data

**Authenticated Routes (require Sanctum auth):**
- `GET /api/user` - Get current user
- `PUT /api/user/preferences` - Update user preferences
- `GET /api/season/{season}/preferences` - Get season preferences
- `PUT /api/season/{season}/preferences` - Update season preferences
- `GET /api/game/{game}/preferences` - Get game preferences

**Authorized Routes (require 'score' ability):**
- `GET /api/players/search` - Search players
- `GET /api/players/team/{team}` - Get team players

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
