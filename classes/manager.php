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

namespace message_telegram;

/**
 * Telegram helper manager class.
 *
 * @package    message_telegram
 * @author     Mike Churchward
 * @copyright  2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @copyright  2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * @var \stdClass Plugin configuration object.
     */
    protected $config;

    /**
     * @var string Prefix used to identify temporary pending user connection tokens.
     */
    protected $secretprefix = 'usersecret::';

    /**
     * @var \curl|null The curl instance for HTTP communication.
     */
    protected $curl = null;

    /**
     * Constructor. Loads plugin configuration.
     */
    public function __construct() {
        $this->config = get_config('message_telegram');
    }

    /**
     * Send a notification message to Telegram.
     *
     * @param string $message The message text to send.
     * @param int $userid The Moodle user id being sent to.
     * @param string $subject Optional message subject.
     * @param string $contexturl Optional URL for action link.
     * @return bool True on success, false on failure.
     */
    public function send_message(string $message, int $userid, string $subject = '', string $contexturl = ''): bool {
        if (empty($this->config('sitebottoken'))) {
            return true;
        }

        $chatid = $this->resolve_user_chatid($userid);
        if (empty($chatid) || strpos($chatid, $this->secretprefix) === 0) {
            return true;
        }

        $formattedtext = '';
        if (!empty($subject)) {
            $formattedtext .= '<b>' . htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</b>\n\n";
        }

        $cleanmessage = strip_tags($message, '<b><i><a><code><pre>');
        $formattedtext .= trim($cleanmessage);

        if (!empty($contexturl)) {
            $formattedtext .= "\n\n<a href=\"" . s($contexturl) . '">' .
                get_string('openinmoodle', 'message_telegram') . '</a>';
        }

        // Enforce Telegram message length limit (4096 characters).
        if (mb_strlen($formattedtext, 'UTF-8') > 4000) {
            $formattedtext = mb_substr($formattedtext, 0, 3950, 'UTF-8') . '...';
            if (!empty($contexturl)) {
                $formattedtext .= "\n\n<a href=\"" . s($contexturl) . '">' .
                    get_string('openinmoodle', 'message_telegram') . '</a>';
            }
        }

        $params = [
            'chat_id' => $chatid,
            'text' => $formattedtext,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => 'true',
        ];

        $response = $this->send_api_command('sendMessage', $params);

        // Fallback to plain text if HTML tags failed to parse.
        if (
            empty($response->ok) && !empty($response->description) &&
                strpos($response->description, 'can\'t parse entities') !== false
        ) {
            $plain = (!empty($subject) ? $subject . "\n\n" : '') . strip_tags($message);
            if (!empty($contexturl)) {
                $plain .= "\n\n" . $contexturl;
            }
            if (mb_strlen($plain, 'UTF-8') > 4000) {
                $plain = mb_substr($plain, 0, 3950, 'UTF-8') . '...';
            }
            $params['text'] = $plain;
            unset($params['parse_mode']);
            $response = $this->send_api_command('sendMessage', $params);
        }

        return !empty($response->ok);
    }

    /**
     * Resolve the Telegram Chat ID for a user, checking preferences and custom profile fields.
     *
     * @param int $userid The user ID.
     * @return string The resolved chat ID or empty string.
     */
    public function resolve_user_chatid(int $userid): string {
        $chatid = (string)get_user_preferences('message_processor_telegram_chatid', '', $userid);
        if (!empty($chatid) && strpos($chatid, $this->secretprefix) !== 0) {
            return $chatid;
        }

        // Check if custom profile field contains a direct numeric chat ID.
        $customfield = $this->config('customphonefield');
        if (!empty($customfield)) {
            $fieldvalue = $this->get_user_custom_field_value($userid, $customfield);
            if (!empty($fieldvalue) && preg_match('/^-?\d+$/', trim($fieldvalue))) {
                return trim($fieldvalue);
            }
        }

        return '';
    }

    /**
     * Set the config item to the specified value in the object and database.
     *
     * @param string $name The name of the config item.
     * @param mixed $value The value of the config item.
     * @return void
     */
    public function set_config(string $name, $value): void {
        set_config($name, $value, 'message_telegram');
        if (!is_object($this->config)) {
            $this->config = new \stdClass();
        }
        $this->config->{$name} = $value;
    }

    /**
     * Return the requested configuration item or null.
     *
     * @param string $configitem The requested configuration item.
     * @return mixed The requested value or null.
     */
    public function config(string $configitem) {
        return $this->config->{$configitem} ?? null;
    }

    /**
     * Return the HTML for the user preferences form.
     *
     * @param object $preferences An object of user preferences.
     * @param int $userid Moodle id of the user in question.
     * @return string The HTML for the form.
     */
    public function config_form($preferences, int $userid): string {
        $html = '';

        if (!$this->is_chatid_set($userid, $preferences)) {
            $connecturl = $this->get_connect_url($userid);
            $botname = $this->config('sitebotname') ?: get_string('pluginname', 'message_telegram');

            $html .= '<div class="alert alert-info py-2 my-2">';
            $html .= get_string('connectinstructions', 'message_telegram', s($botname));
            $html .= '</div>';

            if (!empty($connecturl)) {
                $html .= '<div class="text-center my-3">';
                $html .= '<a href="' . s($connecturl) . '" class="btn btn-primary" target="_blank" rel="noopener noreferrer">';
                $html .= '<i class="fa fa-telegram mr-1" aria-hidden="true"></i> ';
                $html .= get_string('connectme', 'message_telegram');
                $html .= '</a>';
                $html .= '</div>';
            }

            $html .= '<hr class="my-3"/>';
            $html .= '<div class="form-group row">';
            $html .= '<label for="telegram_manual_chatid" class="col-form-label col-md-5 font-weight-bold">';
            $html .= get_string('manualchatid', 'message_telegram');
            $html .= '</label>';
            $html .= '<div class="col-md-7">';
            $html .= '<input type="text" id="telegram_manual_chatid" name="telegram_manual_chatid" class="form-control" ';
            $html .= 'placeholder="e.g. 123456789" />';
            $html .= '<small class="form-text text-muted">';
            $html .= get_string('manualchatid_help', 'message_telegram');
            $html .= '</small>';
            $html .= '</div></div>';
        } else {
            $chatid = $this->get_user_chatid($userid, $preferences);
            $disconnecturl = new \moodle_url($this->redirect_uri(), [
                'action' => 'removechatid',
                'userid' => $userid,
                'sesskey' => sesskey(),
            ]);

            $html .= '<div class="alert alert-success d-flex align-items-center justify-content-between py-2 my-2">';
            $html .= '<div><strong>' . get_string('connectedas', 'message_telegram', s($chatid)) . '</strong></div>';
            $html .= '<a href="' . $disconnecturl . '" class="btn btn-outline-danger btn-sm">';
            $html .= get_string('removetelegram', 'message_telegram');
            $html .= '</a>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Generate or return a secure connection token for linking Telegram to the user.
     *
     * @param int|null $userid The user id.
     * @return string The generated connection token.
     */
    public function get_or_create_usersecret(?int $userid = null): string {
        global $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $existing = get_user_preferences('message_processor_telegram_chatid', '', $userid);
        if (!empty($existing) && strpos($existing, $this->secretprefix) === 0) {
            $token = substr($existing, strlen($this->secretprefix));
            if (!empty($token)) {
                return $token;
            }
        }

        $token = random_string(32);
        set_user_preference('message_processor_telegram_chatid', $this->secretprefix . $token, $userid);
        return $token;
    }

    /**
     * Check if a received secret matches the pending token stored for the user.
     *
     * @param string $receivedsecret The secret to test against the stored one.
     * @param int|null $userid The id of the user to test.
     * @return bool True on match, false otherwise.
     */
    public function usersecret_match(string $receivedsecret, ?int $userid = null): bool {
        global $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $stored = get_user_preferences('message_processor_telegram_chatid', '', $userid);
        if (strpos($stored, $this->secretprefix) !== 0) {
            return false;
        }

        $token = substr($stored, strlen($this->secretprefix));
        return !empty($token) && hash_equals($token, trim($receivedsecret));
    }

    /**
     * Verify whether a user has a valid Telegram chat id configured.
     *
     * @param int $userid The id of the user to check.
     * @param object|null $preferences Optional user preferences object.
     * @return bool True if configured.
     */
    public function is_chatid_set(int $userid, $preferences = null): bool {
        $chatid = $this->get_user_chatid($userid, $preferences);
        return !empty($chatid) && (strpos($chatid, $this->secretprefix) !== 0);
    }

    /**
     * Retrieve the stored chat id or pending secret for the user.
     *
     * @param int $userid The user ID.
     * @param object|null $preferences Optional user preferences.
     * @return string The stored preference value.
     */
    public function get_user_chatid(int $userid, $preferences = null): string {
        if ($preferences !== null && isset($preferences->telegram_chatid)) {
            return (string)$preferences->telegram_chatid;
        }
        return (string)get_user_preferences('message_processor_telegram_chatid', '', $userid);
    }

    /**
     * Construct the Telegram deep link for account connection.
     *
     * @param int|null $userid The user ID.
     * @return string The Telegram URL.
     */
    public function get_connect_url(?int $userid = null): string {
        $botusername = $this->config('sitebotusername');
        if (empty($botusername)) {
            return '';
        }
        $token = $this->get_or_create_usersecret($userid);
        return 'https://t.me/' . urlencode($botusername) . '?start=' . urlencode($token);
    }

    /**
     * Return the redirect URI to handle callbacks and links.
     *
     * @return string The URL.
     */
    public function redirect_uri(): string {
        global $CFG;
        return $CFG->wwwroot . '/message/output/telegram/telegramconnect.php';
    }

    /**
     * Given a valid bot token, query Telegram and cache the bot name and username.
     *
     * @return bool True on success, false on failure.
     */
    public function update_bot_info(): bool {
        if (empty($this->config('sitebottoken'))) {
            return false;
        }

        $response = $this->send_api_command('getMe');
        if (!empty($response->ok) && !empty($response->result)) {
            $this->set_config('sitebotname', $response->result->first_name ?? '');
            $this->set_config('sitebotusername', $response->result->username ?? '');
            return true;
        }
        return false;
    }

    /**
     * Normalize a phone number to digits only.
     *
     * @param string $phone The input phone number.
     * @return string Digits-only phone string.
     */
    public function normalize_phone(string $phone): string {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    /**
     * Compare two phone numbers to verify if they match, taking into account local prefixes.
     *
     * @param string $phone1 First phone number.
     * @param string $phone2 Second phone number.
     * @return bool True if matching.
     */
    public function match_phone_numbers(string $phone1, string $phone2): bool {
        $clean1 = $this->normalize_phone($phone1);
        $clean2 = $this->normalize_phone($phone2);

        if (empty($clean1) || empty($clean2)) {
            return false;
        }

        if ($clean1 === $clean2) {
            return true;
        }

        // Compare the last 9 digits (handles country code differences, e.g., +2010... vs 010...).
        $len1 = strlen($clean1);
        $len2 = strlen($clean2);
        $minlen = min($len1, $len2, 9);

        if ($minlen >= 7) {
            $sub1 = substr($clean1, -$minlen);
            $sub2 = substr($clean2, -$minlen);
            return ($sub1 === $sub2);
        }

        return false;
    }

    /**
     * Get a user's value for a custom profile field.
     *
     * @param int $userid The user ID.
     * @param string $shortname The custom field shortname.
     * @return string The field data or empty string.
     */
    public function get_user_custom_field_value(int $userid, string $shortname): string {
        global $DB;

        $sql = "SELECT d.data
                  FROM {user_info_data} d
                  JOIN {user_info_field} f ON d.fieldid = f.id
                 WHERE f.shortname = :shortname AND d.userid = :userid";
        $record = $DB->get_record_sql($sql, ['shortname' => $shortname, 'userid' => $userid]);
        return $record ? trim((string)$record->data) : '';
    }

    /**
     * Find a Moodle user matching a given phone number.
     *
     * @param string $phone The phone number to search for.
     * @return \stdClass|null The user record or null if not found.
     */
    public function find_user_by_phone(string $phone): ?\stdClass {
        global $DB;

        $target = $this->normalize_phone($phone);
        if (empty($target) || strlen($target) < 6) {
            return null;
        }

        // 1. Check configured custom profile field first.
        $customfield = $this->config('customphonefield');
        if (!empty($customfield)) {
            $sql = "SELECT d.userid, d.data
                      FROM {user_info_data} d
                      JOIN {user_info_field} f ON d.fieldid = f.id
                      JOIN {user} u ON d.userid = u.id
                     WHERE f.shortname = :shortname AND u.deleted = 0";
            $records = $DB->get_records_sql($sql, ['shortname' => $customfield]);
            foreach ($records as $record) {
                if ($this->match_phone_numbers($phone, (string)$record->data)) {
                    return $DB->get_record('user', ['id' => $record->userid, 'deleted' => 0]);
                }
            }
        }

        // 2. Check standard phone fields (phone2 / phone1).
        $candidates = $DB->get_records_select(
            'user',
            "deleted = 0 AND (phone1 IS NOT NULL AND phone1 <> '' OR phone2 IS NOT NULL AND phone2 <> '')",
            null,
            'id ASC',
            'id, phone1, phone2, firstname, lastname, email, auth, suspended'
        );

        foreach ($candidates as $candidate) {
            if (!empty($candidate->phone2) && $this->match_phone_numbers($phone, $candidate->phone2)) {
                return $candidate;
            }
            if (!empty($candidate->phone1) && $this->match_phone_numbers($phone, $candidate->phone1)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Send a prompt to Telegram asking the user to share their phone number contact.
     *
     * @param int|string $chatid The recipient chat ID.
     * @return bool True on success.
     */
    public function send_contact_prompt($chatid): bool {
        $sitename = get_site()->fullname;
        $prompt = get_string('promptsharephone', 'message_telegram', s($sitename));

        $keyboard = [
            'keyboard' => [
                [
                    [
                        'text' => '📱 ' . get_string('sharephone', 'message_telegram'),
                        'request_contact' => true,
                    ],
                ],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];

        $params = [
            'chat_id' => $chatid,
            'text' => $prompt,
            'reply_markup' => json_encode($keyboard),
        ];

        $response = $this->send_api_command('sendMessage', $params);
        return !empty($response->ok);
    }

    /**
     * Process a single update object received from Telegram.
     *
     * @param object $object The update object.
     * @return bool True if an account was linked or handled.
     */
    public function process_single_update(object $object): bool {
        global $DB;

        if (!isset($object->message)) {
            return false;
        }

        $message = $object->message;
        $chatid = $message->chat->id ?? null;
        if (empty($chatid)) {
            return false;
        }

        // 1. User shared contact (Phone Number auto-link).
        if (isset($message->contact) && isset($message->contact->phone_number)) {
            $phone = (string)$message->contact->phone_number;
            $user = $this->find_user_by_phone($phone);

            if ($user) {
                set_user_preference('message_processor_telegram_chatid', (string)$chatid, $user->id);

                $a = (object)[
                    'name' => fullname($user),
                    'site' => get_site()->fullname,
                ];
                $confirmation = get_string('welcomelinked', 'message_telegram', $a);

                $this->send_api_command('sendMessage', [
                    'chat_id' => $chatid,
                    'text' => $confirmation,
                    'reply_markup' => json_encode(['remove_keyboard' => true]),
                ]);

                return true;
            } else {
                $a = (object)[
                    'phone' => $phone,
                    'site' => get_site()->fullname,
                ];
                $notfound = get_string('phonenotfound', 'message_telegram', $a);

                $this->send_api_command('sendMessage', [
                    'chat_id' => $chatid,
                    'text' => $notfound,
                ]);

                return false;
            }
        }

        // 2. User sent text message.
        if (isset($message->text)) {
            $text = trim($message->text);

            if (strpos($text, '/start') === 0) {
                $parts = preg_split('/\s+/', $text, 2);
                $token = $parts[1] ?? '';

                if (!empty($token)) {
                    // Match token to pending secret in user preferences.
                    $targetvalue = $this->secretprefix . $token;
                    $pref = $DB->get_record('user_preferences', [
                        'name' => 'message_processor_telegram_chatid',
                        'value' => $targetvalue,
                    ]);

                    if ($pref) {
                        $user = $DB->get_record('user', ['id' => $pref->userid, 'deleted' => 0]);
                        if ($user) {
                            set_user_preference('message_processor_telegram_chatid', (string)$chatid, (int)$pref->userid);

                            $a = (object)[
                                'name' => fullname($user),
                                'site' => get_site()->fullname,
                            ];
                            $this->send_api_command('sendMessage', [
                                'chat_id' => $chatid,
                                'text' => get_string('welcomelinked', 'message_telegram', $a),
                                'reply_markup' => json_encode(['remove_keyboard' => true]),
                            ]);
                            return true;
                        }
                    }
                }

                // If plain /start, send the contact request prompt.
                $this->send_contact_prompt($chatid);
                return true;
            }
        }

        return false;
    }

    /**
     * Process all pending updates from getUpdates and acknowledge them.
     *
     * @return int Number of updates processed.
     */
    public function process_all_pending_updates(): int {
        if (empty($this->config('sitebottoken')) || !empty($this->config('webhook'))) {
            return 0;
        }

        $results = $this->get_updates();
        if ($results === false || !is_array($results) || empty($results)) {
            return 0;
        }

        $processed = 0;
        $maxupdateid = 0;

        foreach ($results as $object) {
            if (isset($object->update_id)) {
                $maxupdateid = max($maxupdateid, (int)$object->update_id);
            }
            if ($this->process_single_update($object)) {
                $processed++;
            }
        }

        if ($maxupdateid > 0) {
            $this->send_api_command('getUpdates', [
                'offset' => $maxupdateid + 1,
                'limit' => 1,
            ]);
        }

        return $processed;
    }

    /**
     * Check Telegram getUpdates to locate a matching /start command for this user.
     *
     * @param int|null $userid The id of the user in question.
     * @return bool True if connected, false otherwise.
     */
    public function set_chatid(?int $userid = null): bool {
        global $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        if (empty($this->config('sitebottoken'))) {
            return false;
        }

        $this->process_all_pending_updates();

        return $this->is_chatid_set($userid);
    }

    /**
     * Set a chat ID directly entered by a user or administrator.
     *
     * @param int $userid The user ID.
     * @param string $chatid The Telegram Chat ID.
     * @return bool True on success.
     */
    public function set_manual_chatid(int $userid, string $chatid): bool {
        $clean = trim($chatid);
        if (preg_match('/^-?\d+$/', $clean)) {
            set_user_preference('message_processor_telegram_chatid', $clean, $userid);
            return true;
        }
        return false;
    }

    /**
     * Remove the user's Telegram chat id from preferences.
     *
     * @param int|null $userid The id to be cleared.
     * @return void
     */
    public function remove_chatid(?int $userid = null): void {
        global $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }
        unset_user_preference('message_processor_telegram_chatid', $userid);
    }

    /**
     * Configure the webhook URL in Telegram Bot.
     *
     * @param string $webhookurl The endpoint URL.
     * @return string Empty string on success, error message on failure.
     */
    public function set_webhook(string $webhookurl): string {
        if (empty($this->config('sitebottoken'))) {
            return get_string('sitebottokennotsetup', 'message_telegram');
        }

        $params = [
            'url' => $webhookurl,
            'allowed_updates' => json_encode(['message']),
        ];

        $response = $this->send_api_command('setWebhook', $params);
        if (!empty($response->ok)) {
            $this->set_config('webhook', '1');
            return '';
        }

        return $response->description ?? 'Failed to set webhook.';
    }

    /**
     * Remove the Telegram webhook, reverting bot to getUpdates polling mode.
     *
     * @return string Empty string on success, error message on failure.
     */
    public function delete_webhook(): string {
        if (empty($this->config('sitebottoken'))) {
            return get_string('sitebottokennotsetup', 'message_telegram');
        }

        $response = $this->send_api_command('deleteWebhook');
        if (!empty($response->ok)) {
            $this->set_config('webhook', '0');
            return '';
        }

        return $response->description ?? 'Failed to remove webhook.';
    }

    /**
     * Returns the results of a getUpdates API request.
     *
     * @return array|false The decoded results array or false on failure.
     */
    public function get_updates() {
        $response = $this->send_api_command('getUpdates', ['limit' => 100]);
        if (!empty($response->ok) && isset($response->result) && is_array($response->result)) {
            return $response->result;
        }
        return false;
    }

    /**
     * Send a Telegram API command via HTTP POST and return decoded results.
     *
     * @param string $command The API method to execute.
     * @param array $params The parameters to send.
     * @return \stdClass|null The decoded response object or null.
     */
    protected function send_api_command(string $command, array $params = []): ?\stdClass {
        $token = $this->config('sitebottoken');
        if (empty($token)) {
            return null;
        }

        if ($this->curl === null) {
            require_once($GLOBALS['CFG']->dirroot . '/lib/filelib.php');
            $this->curl = new \curl();
        }

        $url = 'https://api.telegram.org/bot' . $token . '/' . $command;
        $rawresponse = $this->curl->post($url, $params);
        $result = json_decode($rawresponse);

        if (empty($result) || empty($result->ok)) {
            $description = $result->description ?? 'Unknown error';
            debugging('Telegram API command "' . $command . '" failed: ' . $description, DEBUG_DEVELOPER);
        }

        return $result;
    }
}
