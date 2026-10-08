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
 * Telegram connection handler and webhook endpoint.
 *
 * @package    message_telegram
 * @author     Mike Churchward
 * @copyright  2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @copyright  2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/message/lib.php');

// If incoming request is a Telegram Webhook POST payload.
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    define('NO_DEBUG_DISPLAY', true);

    $raw = file_get_contents('php://input');
    $data = json_decode($raw);

    if (!empty($data) && is_object($data)) {
        $manager = new \message_telegram\manager();
        $manager->process_single_update($data);
    }

    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit(0);
}

$action = optional_param('action', '', PARAM_ALPHANUMEXT);

$PAGE->set_url(new moodle_url('/message/output/telegram/telegramconnect.php'));
$PAGE->set_context(context_system::instance());

require_login();

$telegrammanager = new message_telegram\manager();

if ($action === 'setwebhook') {
    require_sesskey();
    require_capability('moodle/site:config', context_system::instance());

    if (strpos($CFG->wwwroot, 'https:') !== 0) {
        $message = get_string('requirehttps', 'message_telegram');
    } else {
        $error = $telegrammanager->set_webhook($telegrammanager->redirect_uri());
        $message = empty($error) ? get_string('webhookset', 'message_telegram') : $error;
    }
    redirect(new moodle_url('/admin/settings.php', ['section' => 'messagesettingtelegram']), $message);
} else if ($action === 'unsetwebhook') {
    require_sesskey();
    require_capability('moodle/site:config', context_system::instance());

    $error = $telegrammanager->delete_webhook();
    $message = empty($error) ? get_string('webhookremoved', 'message_telegram') : $error;
    redirect(new moodle_url('/admin/settings.php', ['section' => 'messagesettingtelegram']), $message);
} else if ($action === 'removechatid') {
    require_sesskey();
    $userid = optional_param('userid', $USER->id, PARAM_INT);
    $user = core_user::get_user($userid, '*', MUST_EXIST);

    if (!core_message_can_edit_message_profile($user)) {
        throw new moodle_exception('cannoteditmessageprofile', 'message');
    }

    $telegrammanager->remove_chatid($userid);
    redirect(
        new moodle_url('/message/notificationpreferences.php', ['userid' => $userid]),
        get_string('chatidremoved', 'message_telegram')
    );
}

redirect(new moodle_url('/'));
