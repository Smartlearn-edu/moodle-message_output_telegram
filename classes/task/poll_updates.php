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

namespace message_telegram\task;

/**
 * Scheduled task to poll Telegram updates and process phone/account linkings.
 *
 * @package    message_telegram
 * @copyright  2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class poll_updates extends \core\task\scheduled_task {
    /**
     * Get the descriptive name for this scheduled task.
     *
     * @return string The task name.
     */
    public function get_name(): string {
        return get_string('taskpollupdates', 'message_telegram');
    }

    /**
     * Execute the task to process pending Telegram updates.
     *
     * @return void
     */
    public function execute(): void {
        $manager = new \message_telegram\manager();
        $processed = $manager->process_all_pending_updates();
        if ($processed > 0) {
            mtrace("Processed {$processed} Telegram updates.");
        }
    }
}
