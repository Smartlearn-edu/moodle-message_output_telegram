<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for telegram message plugin.
 *
 * @package    message_telegram
 * @author     Mike Churchward
 * @copyright  2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @copyright  2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['chatidremoved'] = 'Telegram connection removed.';
$string['chatidupdated'] = 'Telegram Chat ID updated.';
$string['configcustomphonefield'] = 'Select the custom user profile field that stores the user\'s Telegram phone number. You can create this under Site Administration > Users > User profile fields and set it as Required and visible on Signup.';
$string['configsitebotname'] = 'This will be filled in automatically when you save the bot token.';
$string['configsitebottoken'] = 'Enter the site bot token from BotFather here.';
$string['configsitebotusername'] = 'This will be filled in automatically when you save the bot token.';
$string['connected'] = 'Connected';
$string['connectedas'] = 'Connected (Chat ID: {$a})';
$string['connectinstructions'] = 'Click the button below to open Telegram and start a chat with <strong>{$a}</strong>. In Telegram, click the <strong>Start</strong> button to connect your account. After clicking Start in Telegram, return to this page and save preferences.';
$string['connectme'] = 'Connect with Telegram';
$string['customphonefield'] = 'Custom profile field for Telegram phone';
$string['errorapi'] = 'Telegram API error: {$a}';
$string['manualchatid'] = 'Or enter Telegram Chat ID manually';
$string['manualchatid_help'] = 'If you already know your Telegram Chat ID (from @userinfobot or @raw_data_bot), you can enter it directly here.';
$string['nocustomfields'] = 'No custom profile fields found. Create one under Site Administration > Users > User profile fields.';
$string['notconfigured'] = 'The Telegram bot has not been configured by the site administrator.';
$string['notconnected'] = 'Not connected';
$string['openinmoodle'] = 'Open in Moodle';
$string['phonenotfound'] = '⚠️ No Moodle account was found matching phone number: {$a->phone}. Please ensure your phone number is saved in your profile on {$a->site}.';
$string['pluginname'] = 'Telegram';
$string['privacy:metadata:chat_id'] = 'The Telegram chat ID of the message recipient.';
$string['privacy:metadata:date'] = 'The timestamp when the notification was sent.';
$string['privacy:metadata:externalpurpose'] = 'This plugin transmits notification content and recipient chat IDs to the Telegram Bot API (https://api.telegram.org) to deliver messages.';
$string['privacy:metadata:preference:telegram_chatid'] = 'The Telegram chat ID or temporary connection token associated with the user.';
$string['privacy:metadata:subject'] = 'The subject of the notification being sent.';
$string['privacy:metadata:text'] = 'The body text of the notification being sent.';
$string['privacy:preference:telegram_chatid'] = 'Your configured Telegram chat ID.';
$string['promptsharephone'] = 'Welcome to {$a}! Tap the button below to share your phone number and connect your Moodle account.';
$string['removetelegram'] = 'Disconnect Telegram';
$string['setupinstructions'] = 'Create a new Telegram Bot using BotFather. Click the link below and open it in Telegram. Use the "/newbot" command to create the bot. Specify a name (e.g., "{$a->name}") and a unique username ending in "bot" (e.g., "{$a->username}"). Copy the API token provided by BotFather into the field below.';
$string['sharephone'] = 'Share Phone Number to Connect';
$string['sitebotname'] = 'Bot name for site';
$string['sitebottoken'] = 'Bot token for site';
$string['sitebottokennotsetup'] = 'Bot token for site must be specified in plugin settings.';
$string['sitebotusername'] = 'Bot username for site';
$string['status'] = 'Telegram connection status';
$string['taskpollupdates'] = 'Poll Telegram updates for phone and account linking';
$string['telegrambottoken'] = 'Telegram bot token';
$string['telegramchatid'] = 'Telegram chat ID';
$string['tokenexpired'] = 'The connection token has expired or is invalid. Please try connecting again.';
$string['welcomelinked'] = '✅ Welcome {$a->name}! Your Telegram account has been linked to {$a->site}.';

// Custom user profile field strings.
$string['profile_category_name'] = 'Telegram';
$string['profile_field_name'] = 'Telegram Phone Number';
$string['profile_field_desc'] = 'Verified phone number for Telegram notifications and OTP verification.';
