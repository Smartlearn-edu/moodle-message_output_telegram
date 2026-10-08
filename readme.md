# Telegram Message Processor for Moodle

The **Telegram Message Processor** (`message_telegram`) delivers Moodle notifications and messages directly to users' Telegram accounts in real time.

## Compatibility
- **Moodle Versions:** Moodle 4.5, 5.0, 5.1, 5.2, 5.3+
- **PHP Versions:** PHP 8.2, 8.3, 8.4+

## Key Features
- **Instant Notification Delivery:** Receive course updates, forum posts, assignments, and system alerts on Telegram.
- **Rich HTML Formatting:** Notifications display bold titles, clean body text, and direct "Open in Moodle" action links.
- **Message Length Protection:** Automatically chunks or truncates long messages to respect Telegram's 4,096-character limit without delivery failures.
- **Decoupled Secure Authentication:** Account linking uses cryptographically secure 32-character tokens without exposing user session CSRF tokens (`sesskey`).
- **Dual Account Linking:** Users can connect in one click via `t.me/<bot>?start=<token>` or manually enter their Chat ID.
- **Privacy API Compliant:** Fully implements Moodle's Privacy Subsystem (GDPR) for data export and deletion.
- **Dual Transport:** Supports polling via `getUpdates` as well as optional real-time Webhook endpoint.

## Setup Instructions

### 1. Create a Telegram Bot
1. Open Telegram and search for `@BotFather`.
2. Send `/newbot` and follow the prompts to choose a display name and a bot username (ending in `bot`).
3. Copy the HTTP API token provided by BotFather.

### 2. Configure Moodle Site
1. Log in to Moodle as an Administrator.
2. Navigate to **Site Administration > Plugins > Message outputs > Telegram**.
3. Paste the Bot Token into the **Bot token for site** setting.
4. Save changes. The bot name and username will be populated automatically.
5. Enable the Telegram message output in **Site Administration > Plugins > Message outputs > Manage message outputs**.

### 3. User Configuration
1. Users navigate to **Preferences > Notification preferences**.
2. Click the gear icon next to **Telegram**.
3. Click **Connect with Telegram** and press **Start** in the Telegram bot chat.
4. Return to Moodle and click **Save changes**.

## License
Licensed under the [GNU General Public License v3.0 or later](https://www.gnu.org/licenses/gpl-3.0.html).
