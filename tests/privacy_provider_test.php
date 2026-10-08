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

use context_system;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;

/**
 * Unit tests for message_telegram privacy provider.
 *
 * @package    message_telegram
 * @category   test
 * @covers     \message_telegram\privacy\provider
 * @copyright  2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class privacy_provider_test extends provider_testcase {
    /**
     * Basic setup for these tests.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test returning metadata.
     */
    public function test_get_metadata(): void {
        $collection = new \core_privacy\local\metadata\collection('message_telegram');
        $collection = \message_telegram\privacy\provider::get_metadata($collection);
        $this->assertNotEmpty($collection);
    }

    /**
     * Test getting contexts for user ID.
     */
    public function test_get_contexts_for_userid(): void {
        $user = $this->getDataGenerator()->create_user();
        $contextlist = \message_telegram\privacy\provider::get_contexts_for_userid((int)$user->id);
        $this->assertEmpty($contextlist);
    }

    /**
     * Test exporting user preferences.
     */
    public function test_export_user_preferences(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        set_user_preference('message_processor_telegram_chatid', '987654321', $user->id);

        provider::export_user_preferences((int)$user->id);

        $writer = writer::with_context(context_system::instance());
        $this->assertTrue($writer->has_any_data());

        $preferences = $writer->get_user_preferences('message_telegram');
        $this->assertNotEmpty($preferences->telegram_chatid);
        $this->assertEquals('987654321', $preferences->telegram_chatid->value);
    }
}
