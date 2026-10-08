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
- **Multiple Account Linking Methods:**
  1. **Automatic Phone Number Matching:** Users tap one button in Telegram to share their phone number; Moodle matches it to their custom profile field automatically.
  2. **Manual Chat ID Entry:** Users find their numeric Telegram ID via `@idbot` or `@userinfobot` and paste it directly into Moodle preferences.
  3. **One-Click Deep Link:** Users click the "Connect with Telegram" button in Moodle preferences (`t.me/<bot>?start=<token>`).
- **Privacy API Compliant (GDPR):** Fully implements Moodle's Privacy Subsystem for user metadata declaration, preference export, and deletion.
- **Dual Transport (Polling & Webhook):** Supports automatic polling via background cron task as well as real-time Webhook endpoint for instant delivery.

---

## 🚀 Step-by-Step Setup Guide

### Step 1: Create Your Telegram Bot via BotFather

1. Open Telegram on your phone, desktop, or web, and search for [`@BotFather`](https://t.me/BotFather) (look for the verified blue checkmark).
2. Start the chat with BotFather by clicking **Start** or sending:
   ```text
   /start
   ```
3. Create a new bot by sending:
   ```text
   /newbot
   ```
4. **Choose a Display Name:** Enter a friendly name for your bot that users will see (for example: `SmartLearn Alerts` or `University LMS Bot`).
5. **Choose a Username:** Enter a unique bot username. It **must** end in `bot` or `_bot` (for example: `smartlearn_alert_bot` or `myuniv_moodle_bot`).
6. **Copy the API Token:** BotFather will provide an **HTTP API Token**. It looks like this:
   ```text
   1234567890:ABCdefGhIJKlmNoPQRsTUVwxyZ123456789
   ```
   > ⚠️ **Keep this token secret.** Anyone with this token can send messages on behalf of your bot.

#### (Recommended) Customize Your Bot Appearance in BotFather:
- **Set Profile Picture:** Send `/setuserpic`, choose your bot, and upload your school or institution logo.
- **Set Description:** Send `/setdescription` to define the greeting message shown before users press Start (e.g., *"Official notification bot for our Moodle LMS"*).
- **Set About Info:** Send `/setabouttext` to add a short bio to the bot's profile.

---

### Step 2: Install and Configure the Plugin in Moodle

#### 2.1 Install the Plugin
1. Place or clone the plugin directory into your Moodle installation at:
   ```bash
   moodle/message/output/telegram
   ```
2. As an administrator, go to **Site Administration > Notifications** (or run `php admin/cli/upgrade.php` from your terminal) to complete the database upgrade.

#### 2.2 Enable the Telegram Message Output (Required First Step)
> ℹ️ **Important:** Moodle hides message plugin settings until the plugin is enabled in the message outputs manager.
1. Navigate to **Site Administration > Plugins > Message outputs > Manage message outputs** (`/admin/message.php`).
2. Locate **Telegram** in the list.
3. Make sure the **Enabled** column is active (the eye icon should be **open / enabled**).
4. Click the **Settings** link next to Telegram (or continue to Step 2.3).

#### 2.3 Configure Telegram Bot Token
1. Go to **Site Administration > Plugins > Message outputs > Telegram** (`/admin/settings.php?section=messagesettingtelegram`).
2. In the **Bot token for site** field, paste the HTTP API Token copied from BotFather.
3. Click **Save changes**.
4. Moodle will contact the Telegram API and automatically populate the **Bot name for site** and **Bot username for site** fields.

#### 2.4 Create Required Custom Profile Field for Telegram Phone
The plugin links phone numbers strictly via a **Custom User Profile Field** so administrators can make it required upon registration:
1. Navigate to **Site Administration > Users > User profile fields** (`/admin/user/profile/index.php`).
2. Click **Create a new profile field** and choose **Text input**.
3. Configure the profile field:
   - **Short name:** `telegram` (or `telegram_phone`)
   - **Name:** `Telegram Mobile Phone` (or `Telegram Chat No`)
   - **Is this field required?:** **Yes** *(forces users to fill it out during registration or profile update)*
   - **Display on signup page?:** **Yes** *(prompts new users immediately upon registration)*
4. Click **Save changes**.

#### 2.5 Select the Custom Profile Field in Telegram Settings
1. Return to **Site Administration > Plugins > Message outputs > Telegram**.
2. Under **Custom profile field for Telegram phone**, select your field from the dropdown (e.g., `Telegram Mobile Phone (telegram)`).
3. Click **Save changes**.

#### 2.6 Configure Default Notification Routing
1. Go to **Site Administration > Plugins > Message outputs > Default message outputs** (`/admin/message.php` -> Default message outputs).
2. Review the notification matrix (Assignment notifications, Forum posts, Grades, System alerts, etc.).
3. Under the **Telegram** column, configure the desired notification types:
   - **Permitted:** Allows users to choose whether or not to receive notifications via Telegram.
   - **Default enabled (Online / Offline):** Automatically turns on Telegram notifications for users once their account is connected.
4. Click **Save changes**.

#### 2.7 Transport Mode: Webhook vs Polling
The plugin supports two modes for receiving updates from Telegram:
- **Option A: Webhook Mode (Recommended for Live Public HTTPS Sites)**
  - If your Moodle site is accessible over public **HTTPS** (required by Telegram):
  - In **Site Administration > Plugins > Message outputs > Telegram**, click the **Setup Telegram webhook** button.
  - When active, Telegram pushes updates to your Moodle server in real time with zero delay.
- **Option B: Polling Mode (For Localhost, Development, or Firewalled Sites)**
  - If your site does not have a public HTTPS certificate or is running on a local development machine:
  - Do **not** set a webhook (or click **Remove Telegram webhook** if previously set).
  - Moodle's built-in scheduled background task (`\message_telegram\task\poll_updates`) will automatically poll Telegram updates every minute via Moodle cron.
  - Ensure Moodle cron is running regularly:
    ```bash
    php admin/cli/cron.php
    ```

---

### Step 3: Connect User Accounts to Telegram

There are two primary methods for users to link their Moodle account to Telegram, plus a direct web link fallback:

---

#### 📱 Method 1: Automatic Phone Matching (Easiest — Zero Codes)
This method requires no codes or manual configuration from the user:
1. **Prerequisite:** The student enters their phone number in their Moodle profile under the custom profile field (e.g. during registration or under **Edit profile**).
2. In Telegram, the user searches for your bot (`@your_bot_name`) and clicks **Start** (or sends `/start`).
3. The bot responds automatically with an interactive button:
   ```text
   📱 [ Share Phone Number to Connect ]
   ```
4. The user taps the button and confirms sharing their phone contact.
5. Moodle receives the verified phone number from Telegram, matches it against their custom profile field, and instantly links their account!
6. The bot confirms:
   ```text
   ✅ Welcome [Student Name]! Your Telegram account has been linked to [Site Name].
   ```

---

#### 🆔 Method 2: Manual Chat ID Entry (Direct ID Linking)
If a user cannot or does not want to share their phone number, they can link directly using their Telegram Chat ID:
1. In Telegram, the user searches for [`@idbot`](https://t.me/idbot) or [`@userinfobot`](https://t.me/userinfobot) and clicks **Start**.
2. The bot replies with their numeric Telegram ID (for example: `Id: 112345678` or `123456789`).
3. In Moodle, the user clicks their avatar at top-right -> **Preferences > Notification preferences**.
4. Click the **Settings / Gear icon** next to **Telegram**.
5. In the **Or enter Telegram Chat ID manually** field, enter the numeric ID:
   ```text
   112345678
   ```
6. Click **Save changes**. The account is linked immediately and ready to receive notifications!

---

#### 🔗 Method 3: One-Click Connection Link (Deep Link)
1. In Moodle, go to **Preferences > Notification preferences > Telegram settings**.
2. Click the blue **Connect with Telegram** button (`t.me/<bot>?start=<token>`).
3. Telegram will open to the bot with a one-time secure 32-character token.
4. Click **Start** in Telegram.
5. Return to Moodle and click **Save changes**.

---

#### How to Disconnect an Account:
1. Go to **Preferences > Notification preferences > Telegram settings**.
2. Click the **Disconnect Telegram** button.
3. The user's Telegram Chat ID will be removed immediately and notifications will cease.

---

## 🛠️ Troubleshooting & FAQ

### 1. "The Telegram bot has not been configured by the site administrator"
- **Cause:** The bot token has not been configured or is invalid.
- **Solution:** Verify the token in **Site Administration > Plugins > Message outputs > Telegram** matches the token provided by BotFather.

### 2. Notifications are not being received on Telegram
- **Check User Connection Status:** In **Preferences > Notification preferences > Telegram settings**, verify the account status displays `Connected as: <Chat ID>`.
- **Check Notification Preferences:** In **Preferences > Notification preferences**, confirm that the checkboxes under the **Telegram** column are checked for the specific events (Assignments, Forums, etc.).
- **Check Moodle Cron:** Notifications are dispatched via background queues. Ensure Moodle cron is running:
  ```bash
  php admin/cli/cron.php
  ```
- **Check Development Mode (`$CFG->noemailever`):** If testing on a development server, verify whether `$CFG->noemailever = true;` is present in `config.php`. This Moodle setting halts all outbound messaging.

### 3. Telegram bot does not respond when clicking Start
- **If using Webhooks:** Ensure your Moodle site has a valid public SSL certificate (HTTPS) and that the URL is publicly reachable by Telegram servers.
- **If using Polling:** Ensure Moodle cron is executing (`php admin/cli/cron.php`). Updates are fetched and processed every minute by the scheduled task `\message_telegram\task\poll_updates`.

### 4. "Site must use HTTPS for Telegram's webhook function"
- Telegram requires an authentic SSL certificate on a valid public domain for webhooks. If testing locally or without HTTPS, do not click "Setup Telegram webhook"; use standard background polling instead.

### 5. "409 Conflict: can't use getUpdates method while webhook is active"
- Telegram does not allow polling via `getUpdates` while a webhook is registered. In **Site Administration > Plugins > Message outputs > Telegram**, click **Remove Telegram webhook** to revert the bot back to polling mode.

---

## 🔒 Privacy & GDPR Compliance
This plugin is fully compliant with Moodle's Privacy Subsystem:
- **User Preferences:** Stores the user's Telegram Chat ID in the core `user_preferences` table (`message_processor_telegram_chatid`).
- **External Data Transmission:** Transmits notification content (subject, message body, context URL) and the recipient's Chat ID to Telegram's official API servers (`https://api.telegram.org`).
- **Data Export & Erasure:** Fully implements `\core_privacy\local\metadata\null_provider` / user preference export and deletion handlers.

---

## 📄 License
This plugin is free software released under the [GNU General Public License v3.0 or later](https://www.gnu.org/licenses/gpl-3.0.html).

Created by **Mike Churchward** (2017)  
Modernized and Maintained by **Mohammad Nabil** (2025 onwards)
