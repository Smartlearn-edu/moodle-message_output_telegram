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

use advanced_testcase;

/**
 * Unit tests for telegram manager.
 *
 * @package    message_telegram
 * @category   test
 * @covers     \message_telegram\manager
 * @copyright  2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manager_test extends advanced_testcase {
    /**
     * Setup test environment.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test token generation and validation.
     */
    public function test_usersecret_generation_and_matching(): void {
        $user = $this->getDataGenerator()->create_user();
        $manager = new manager();

        $token = $manager->get_or_create_usersecret((int)$user->id);
        $this->assertNotEmpty($token);
        $this->assertEquals(32, strlen($token));

        // Matching token.
        $this->assertTrue($manager->usersecret_match($token, (int)$user->id));

        // Non-matching token.
        $this->assertFalse($manager->usersecret_match('invalidtoken123456', (int)$user->id));
    }

    /**
     * Test chat ID configuration checks.
     */
    public function test_is_chatid_set(): void {
        $user = $this->getDataGenerator()->create_user();
        $manager = new manager();

        // Not set initially.
        $this->assertFalse($manager->is_chatid_set((int)$user->id));

        // Pending secret is not considered fully configured.
        $manager->get_or_create_usersecret((int)$user->id);
        $this->assertFalse($manager->is_chatid_set((int)$user->id));

        // Setting a real chat ID.
        $manager->set_manual_chatid((int)$user->id, '123456789');
        $this->assertTrue($manager->is_chatid_set((int)$user->id));

        // Removing chat ID.
        $manager->remove_chatid((int)$user->id);
        $this->assertFalse($manager->is_chatid_set((int)$user->id));
    }

    /**
     * Test connect URL generation.
     */
    public function test_get_connect_url(): void {
        $user = $this->getDataGenerator()->create_user();
        $manager = new manager();

        $manager->set_config('sitebotusername', 'MyTestBot');
        $url = $manager->get_connect_url((int)$user->id);

        $this->assertStringStartsWith('https://t.me/MyTestBot?start=', $url);
    }

    /**
     * Test phone normalization and matching.
     */
    public function test_phone_normalization_and_matching(): void {
        $manager = new manager();

        $this->assertEquals('201005822858', $manager->normalize_phone('+20 10 0582 2858'));
        $this->assertEquals('01005822858', $manager->normalize_phone('010-0582-2858'));

        // Matching international vs local formats.
        $this->assertTrue($manager->match_phone_numbers('+20 10 0582 2858', '01005822858'));
        $this->assertTrue($manager->match_phone_numbers('201005822858', '+201005822858'));
        $this->assertFalse($manager->match_phone_numbers('+201005822858', '01123456789'));
    }
}
