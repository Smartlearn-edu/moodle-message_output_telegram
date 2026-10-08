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

namespace message_telegram\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem for the Telegram message processor.
 *
 * @package    message_telegram
 * @copyright  2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\core_userlist_provider, \core_privacy\local\request\plugin\provider, \core_privacy\local\request\user_preference_provider {
    /**
     * Returns metadata about user data stored or transmitted by this plugin.
     *
     * @param  collection $collection The initialised collection to add items to.
     * @return collection A listing of user data items.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference(
            'message_processor_telegram_chatid',
            'privacy:metadata:preference:telegram_chatid'
        );

        $collection->link_external_location(
            'telegram',
            [
                'chat_id' => 'privacy:metadata:chat_id',
                'text' => 'privacy:metadata:text',
                'subject' => 'privacy:metadata:subject',
                'date' => 'privacy:metadata:date',
            ],
            'privacy:metadata:externalpurpose'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param  int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context.
     */
    public static function get_users_in_context(userlist $userlist): void {
        // No custom database tables storing user data.
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        // Handled via export_user_preferences.
    }

    /**
     * Delete all user data for all users in the specified context.
     *
     * @param \context $context A context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        // No custom database tables.
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        // No custom database tables.
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        // Preferences are managed by core user deletion.
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param int $userid The user ID.
     */
    public static function export_user_preferences(int $userid): void {
        $chatid = get_user_preferences('message_processor_telegram_chatid', null, $userid);
        if (!empty($chatid)) {
            writer::export_user_preference(
                'message_telegram',
                'telegram_chatid',
                $chatid,
                get_string('privacy:preference:telegram_chatid', 'message_telegram')
            );
        }
    }
}
