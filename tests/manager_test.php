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

    /**
     * Test processing /start with token update links account.
     */
    public function test_process_single_update_start_token(): void {
        $user = $this->getDataGenerator()->create_user();
        $manager = new manager();

        $token = $manager->get_or_create_usersecret((int)$user->id);
        $this->assertNotEmpty($token);

        $update = (object)[
            'message' => (object)[
                'chat' => (object)['id' => 987654321],
                'text' => '/start ' . $token,
            ],
        ];

        $result = $manager->process_single_update($update);
        $this->assertTrue($result);
        $this->assertEquals('987654321', $manager->get_user_chatid((int)$user->id));
    }

    /**
     * Test auto-provisioning custom profile field in message_telegram.
     */
    public function test_ensure_profile_field(): void {
        global $DB;
        $this->resetAfterTest(true);

        $fieldid1 = manager::ensure_profile_field();
        $this->assertGreaterThan(0, $fieldid1);

        $field = $DB->get_record('user_info_field', ['id' => $fieldid1]);
        $this->assertNotEmpty($field);
        $this->assertEquals(manager::PROFILE_FIELD_SHORTNAME, $field->shortname);

        // Verify category was created.
        $category = $DB->get_record('user_info_category', ['id' => $field->categoryid]);
        $this->assertNotEmpty($category);
        $this->assertEquals(manager::PROFILE_CATEGORY_NAME, $category->name);

        // Idempotency.
        $fieldid2 = manager::ensure_profile_field();
        $this->assertEquals($fieldid1, $fieldid2);
        $this->assertEquals(1, $DB->count_records('user_info_field', ['shortname' => manager::PROFILE_FIELD_SHORTNAME]));
    }

    /**
     * Test finding user by phone via telegram_phone custom profile field.
     */
    public function test_find_user_by_phone_via_custom_field(): void {
        global $DB;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user(['phone1' => '']);
        $manager = new manager();

        $fieldid = manager::ensure_profile_field();
        $data = (object)[
            'userid' => $user->id,
            'fieldid' => $fieldid,
            'data' => '+966501234567',
            'dataformat' => 0,
        ];
        $DB->insert_record('user_info_data', $data);

        $found = $manager->find_user_by_phone('+966501234567');
        $this->assertNotEmpty($found);
        $this->assertEquals($user->id, $found->id);

        // Local format search also matches.
        $foundlocal = $manager->find_user_by_phone('0501234567');
        $this->assertNotEmpty($foundlocal);
        $this->assertEquals($user->id, $foundlocal->id);
    }

    /**
     * Test finding user by phone via phone1 fallback.
     */
    public function test_find_user_by_phone_via_phone1_fallback(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user(['phone1' => '+201012345678']);
        $manager = new manager();

        $found = $manager->find_user_by_phone('01012345678');
        $this->assertNotEmpty($found);
        $this->assertEquals($user->id, $found->id);
    }

    /**
     * Test processing /start reg_ token for local_telegramotp.
     */
    public function test_process_single_update_with_local_telegramotp_token(): void {
        global $DB;
        $this->resetAfterTest(true);

        if (!class_exists('\local_telegramotp\manager')) {
            $this->markTestSkipped('local_telegramotp not present in test environment.');
        }

        set_config('bot_username', 'MyTestBot', 'local_telegramotp');
        set_config('bot_verification_security', 'fast', 'local_telegramotp');

        $data = [
            'firstname' => 'TestBot',
            'lastname'  => 'User',
            'email'     => 'botuser@example.com',
            'password'  => 'Pass123!@#',
            'phone'     => '+966503332211',
        ];

        $tokenres = \local_telegramotp\manager::create_bot_verification_token($data);
        $token = $tokenres['token'];

        $update = (object)[
            'message' => (object)[
                'chat' => (object)['id' => 77889900],
                'text' => '/start ' . $token,
            ],
        ];

        $manager = new manager();
        $result = $manager->process_single_update($update);
        $this->assertTrue($result);

        // Check user was created and chat id linked.
        $user = $DB->get_record('user', ['email' => 'botuser@example.com']);
        $this->assertNotEmpty($user);
        $this->assertEquals('77889900', get_user_preferences('message_processor_telegram_chatid', null, $user->id));
    }
}
