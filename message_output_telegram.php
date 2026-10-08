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
 * Telegram message processor main output class.
 *
 * @package    message_telegram
 * @author     Mike Churchward
 * @copyright  2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @copyright  2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/message/output/lib.php');

/**
 * The telegram message processor class.
 *
 * @package    message_telegram
 * @copyright  2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @copyright  2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_output_telegram extends message_output {
    /**
     * @var \message_telegram\manager The telegram manager instance.
     */
    protected $manager;

    /**
     * Constructor to initialize the telegram manager.
     */
    public function __construct() {
        $this->manager = new \message_telegram\manager();
    }

    /**
     * Processes the message and sends a notification via Telegram.
     *
     * @param \stdClass $eventdata The event data submitted by the message provider.
     * @return bool True if ok, false on error.
     */
    public function send_message($eventdata) {
        global $CFG;

        $user = is_object($eventdata->userto) ? $eventdata->userto : \core_user::get_user($eventdata->userto);
        if (empty($user)) {
            return false;
        }

        // Skip any messaging of suspended and deleted users.
        if (($user->auth === 'nologin') || !empty($user->suspended) || !empty($user->deleted)) {
            return true;
        }

        if (!empty($CFG->noemailever)) {
            debugging('$CFG->noemailever is active, no telegram message sent.', DEBUG_MINIMAL);
            return true;
        }

        $message = '';
        if (!empty($eventdata->fullmessage)) {
            $message = $eventdata->fullmessage;
        } else if (!empty($eventdata->fullmessagehtml)) {
            $message = $eventdata->fullmessagehtml;
        } else if (!empty($eventdata->smallmessage)) {
            $message = $eventdata->smallmessage;
        } else if (!empty($eventdata->subject)) {
            $message = $eventdata->subject;
        }

        if (empty($message)) {
            return true;
        }

        $subject = $eventdata->subject ?? '';
        $contexturl = $eventdata->contexturl ?? '';

        return $this->manager->send_message($message, (int)$user->id, $subject, $contexturl);
    }

    /**
     * Creates necessary fields in the messaging config form.
     *
     * @param object $preferences An object of user preferences.
     * @return string HTML content for preferences configuration.
     */
    public function config_form($preferences) {
        global $USER;

        if (!$this->is_system_configured()) {
            return get_string('notconfigured', 'message_telegram');
        }

        $userid = $preferences->userid ?? $USER->id;
        return $this->manager->config_form($preferences, (int)$userid);
    }

    /**
     * Parses the submitted form data and saves it into preferences array.
     *
     * @param \stdClass $form Preferences form class.
     * @param array $preferences Preferences array passed by reference.
     * @return void
     */
    public function process_form($form, &$preferences) {
        global $USER;

        $userid = isset($form->userid) ? (int)$form->userid : $USER->id;

        if (isset($form->telegram_manual_chatid)) {
            $clean = trim($form->telegram_manual_chatid);
            if (!empty($clean)) {
                $preferences['message_processor_telegram_chatid'] = $clean;
                $this->manager->set_manual_chatid($userid, $clean);
                return;
            }
        }

        $this->manager->set_chatid($userid);
        $current = get_user_preferences('message_processor_telegram_chatid', '', $userid);
        if (!empty($current) && strpos($current, 'usersecret::') !== 0) {
            $preferences['message_processor_telegram_chatid'] = $current;
        }
    }

    /**
     * Loads config data from database into preferences object during initial display.
     *
     * @param object $preferences Preferences object.
     * @param int $userid The user ID.
     * @return void
     */
    public function load_data(&$preferences, $userid) {
        $preferences->telegram_chatid = get_user_preferences('message_processor_telegram_chatid', '', $userid);
        $preferences->userid = (int)$userid;
    }

    /**
     * Tests whether the Telegram settings have been configured at the user level.
     *
     * @param \stdClass|int|null $user The user object or ID, defaults to $USER.
     * @return bool True if configured.
     */
    public function is_user_configured($user = null) {
        global $USER;

        if ($user === null) {
            $user = $USER;
        }

        $userid = is_object($user) ? (int)$user->id : (int)$user;
        return $this->manager->is_chatid_set($userid);
    }

    /**
     * Tests whether the Telegram settings have been configured at the site level.
     *
     * @return bool True if configured.
     */
    public function is_system_configured() {
        return !empty(get_config('message_telegram', 'sitebottoken'));
    }

    /**
     * Returns the message processor default settings.
     *
     * @return int Bitmask default settings.
     */
    public function get_default_messaging_settings() {
        return MESSAGE_PERMITTED;
    }
}
