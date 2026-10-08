# Telegram Message Processor for Moodle

The **Telegram Message Processor** (`message_telegram`) delivers Moodle notifications and messages directly to users' Telegram accounts in real time.

---

## 📌 System Compatibility
- **Moodle Versions:** Moodle 4.5 LTS, 5.0, 5.1, 5.2, 5.3+
- **PHP Versions:** PHP 8.2, 8.3, 8.4+
- **Database:** MySQL, MariaDB, PostgreSQL

---

## ✨ Key Features
- **Real-Time Notification Delivery:** Course announcements, assignment grades, feedback, forum replies, quiz submissions, and system alerts sent instantly to Telegram.
- **Rich HTML Formatting:** Messages display bold titles, clean body text, and direct clickable **"Open in Moodle"** action links.
- **Message Length Protection:** Automatically chunks or truncates long notifications (such as daily forum digests) to respect Telegram's 4,096-character limit without delivery failures.
- **Decoupled Secure Authentication:** Account linking uses cryptographically secure 32-character tokens without exposing user session CSRF tokens (`sesskey`).
- **Dual Account Linking Options:** Users can connect in one click via `t.me/<bot>?start=<token>` or manually enter their Chat ID.
- **Privacy API Compliant (GDPR):** Fully implements Moodle's Privacy Subsystem for user metadata declaration, preference export, and deletion.
- **Dual Transport (Polling & Webhook):** Supports polling via `getUpdates` as well as an optional real-time Webhook endpoint.

---

## 🚀 Step-by-Step Setup Guide

### Step 1: Create and Configure Your Telegram Bot (BotFather)

1. Open the Telegram app on your phone, desktop, or web, and search for [`@BotFather`](https://t.me/BotFather) (verify the blue checkmark).
2. Start the chat with BotFather by clicking **Start** or sending the command:
   ```text
   /start
   ```
3. Create a new bot by sending:
   ```text
   /newbot
   ```
4. **Choose a Display Name:** Enter a friendly name for your bot that users will see (for example: `My University Notifications` or `SmartLearn Alerts`).
5. **Choose a Username:** Enter a unique bot username. It **must** end in `bot` or `_bot` (for example: `smartlearn_alert_bot` or `myuniv_moodle_bot`).
6. **Copy the API Token:** BotFather will reply with a congratulatory message containing your **HTTP API Token**. It looks like this:
   ```text
   1234567890:ABCdefGhIJKlmNoPQRsTUVwxyZ123456789
   ```
   *Keep this token secret — anyone with the token can control the bot.*

#### (Optional) Customize Your Bot Appearance in BotFather:
- **Set Profile Picture:** Send `/setuserpic`, select your bot, and upload your institution's logo.
- **Set Description:** Send `/setdescription` to set the greeting text users see before clicking Start (e.g., *"Official notification bot for our Moodle LMS"*).
- **Set About Info:** Send `/setabouttext` to set the bio in the bot's profile.

---

### Step 2: Install and Configure the Plugin in Moodle

#### 1. Install the Plugin
1. Place or clone the plugin directory into your Moodle installation at:
   ```bash
   moodle/message/output/telegram
   ```
2. As an administrator, navigate to **Site Administration > Notifications** (or run `php admin/cli/upgrade.php` from your terminal) to complete the installation.

#### 2. Configure Telegram Bot Settings
1. Go to **Site Administration > Plugins > Message outputs > Telegram**.
2. In the **Bot token for site** field, paste the HTTP API Token copied from BotFather.
3. Click **Save changes**.
4. Moodle will contact the Telegram API and automatically populate the **Bot name for site** and **Bot username for site** fields.

#### 3. Enable the Telegram Message Output
1. Navigate to **Site Administration > Plugins > Message outputs > Manage message outputs**.
2. Locate **Telegram** in the list of processors and make sure the **Enabled** column is active (open eye icon).

#### 4. Configure Default Notification Routing
1. Go to **Site Administration > Plugins > Message outputs > Default message outputs**.
2. Review the notification matrix (Assignment notifications, Forum posts, System alerts, etc.).
3. Under the **Telegram** column, configure the desired notification types:
   - **Permitted:** Users can choose whether or not to receive notifications via Telegram.
   - **Default enabled (Online / Offline):** Automatically turns on Telegram notifications for users once their account is connected.
4. Click **Save changes**.

#### 5. Configure Required Custom Profile Field for Phone (Recommended)
You can require users to enter their phone number upon signup so they can connect automatically:
1. Navigate to **Site Administration > Users > User profile fields**.
2. Click **Create a new profile field** and choose **Text input**.
3. Configure the field:
   - **Short name:** `telegram_phone` (or any shortname you prefer)
   - **Name:** `Telegram Phone Number`
   - **Is this field required?:** **Yes** *(forces users to fill it out during registration or profile update)*
   - **Display on signup page?:** **Yes** *(prompts new users immediately upon registration)*
4. Click **Save changes**.
5. Return to **Site Administration > Plugins > Message outputs > Telegram**.
6. Under **Custom profile field for Telegram phone**, select your field:  
   `Telegram Phone Number (telegram_phone)`.
7. Click **Save changes**.

#### 6. (Optional) Configure Webhook Mode
If your Moodle site is accessible over public **HTTPS** (required by Telegram):
1. In **Site Administration > Plugins > Message outputs > Telegram**, click the **Setup Telegram webhook** button.
2. Webhook mode enables instant, real-time account linking when users press Start in Telegram.
3. If your site is behind a firewall, on localhost, or does not have public HTTPS, leave the webhook unset. Moodle's background task (`\message_telegram\task\poll_updates`) will poll updates automatically.

---

### Step 3: Connect User Accounts to Telegram

#### Method A: Automatic Phone Matching (Easiest - Zero Codes)
When a user has their phone number registered in their Moodle profile (either in the required custom profile field or standard mobile phone):
1. In Telegram, the user opens your bot (`@yourbot`) and clicks **Start**.
2. The bot automatically replies with an interactive button:
   ```text
   📱 [ Share Phone Number to Connect ]
   ```
3. The user taps the button. Telegram sends their verified phone number to the bot.
4. Moodle matches the phone number with their Moodle profile and immediately links their account!
5. The bot replies:
   ```text
   ✅ Welcome [Student Name]! Your Telegram account has been linked to [Site Name].
   ```

#### Method B: One-Click Connection Link
1. In Moodle, click on your user profile avatar in the top-right corner and select **Preferences > Notification preferences**.
2. Click the **Settings / Gear icon** next to **Telegram**.
3. Click the blue **Connect with Telegram** button (`t.me/<bot>?start=<token>`).
4. Click **Start** in Telegram, return to Moodle, and click **Save changes**.

#### Method C: Manual Chat ID Entry (Alternative)
For users behind restrictive firewalls:
1. In Telegram, search for `@userinfobot` and click **Start** to get your numeric ID (e.g. `123456789`).
2. In Moodle **Notification preferences > Telegram settings**, enter the ID into **Or enter Telegram Chat ID manually**.
3. Click **Save changes**.

#### How to Disconnect an Account:
1. Go to **Preferences > Notification preferences > Telegram settings**.
2. Click the **Disconnect Telegram** button.
3. Your Telegram Chat ID will be removed and notifications will stop immediately.

---

## 🛠️ Troubleshooting & FAQ

### 1. "The Telegram bot has not been configured by the site administrator"
- **Cause:** The site bot token has not been saved or is invalid.
- **Solution:** Re-check **Site Administration > Plugins > Message outputs > Telegram** and verify the token matches BotFather.

### 2. Notifications are not being received on Telegram
- **Check User Connection:** Ensure the user has connected their account (status shows `Connected`).
- **Check Notification Preferences:** In **Preferences > Notification preferences**, verify that the checkboxes for Telegram are toggled on for the specific event (e.g., Forum posts or Assignment submissions).
- **Check Moodle Cron:** Notifications are dispatched via background tasks. Ensure Moodle cron is running regularly:
  ```bash
  php admin/cli/cron.php
  ```
- **Check Development Mode (`$CFG->noemailever`):** If testing on a development server, check if `$CFG->noemailever = true;` is present in `config.php`. This core setting halts all outbound messaging.

### 3. "Site must use HTTPS for Telegram's webhook function"
- Telegram requires an authentic SSL certificate on a valid public domain for webhooks. If you do not have public HTTPS, do not click "Setup Telegram webhook"; standard polling mode works without HTTPS.

### 4. "409 Conflict: can't use getUpdates method while webhook is active"
- If a webhook was previously registered on Telegram for this bot, polling via `getUpdates` is blocked. Click **Remove Telegram webhook** in plugin settings to revert the bot back to polling mode.

---

## 🔒 Privacy & GDPR Compliance
This plugin is fully compliant with Moodle's Privacy Subsystem:
- **User Preferences:** Stores the Telegram Chat ID in the core `user_preferences` table.
- **External Data Transmission:** Transmits notification content (subject, body, action link) and the recipient's chat ID to Telegram's secure API servers (`https://api.telegram.org`).
- **Data Export & Erasure:** Fully supports core user data export requests and account deletion routines.

---

## 📄 License
This plugin is free software released under the [GNU General Public License v3.0 or later](https://www.gnu.org/licenses/gpl-3.0.html).

Created by **Mike Churchward** (2017)  
Modernized and Maintained by **Mohammad Nabil** (2025 onwards)
