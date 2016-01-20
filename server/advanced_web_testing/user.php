<?php

namespace AdvancedWebTesting;

/**
 * Интерфейс <user/>
 * View, Controller (MVC)
 */
class User {
	private $db, $userId;

	public function __construct() {
		$this->db = new \WebConstructionSet\Database\Relational\Pdo(\Config::DB_DSN, \Config::DB_USER, \Config::DB_PASSWORD);
	}

	public function run() {
		if (preg_match('/Mobile|Phone|Android|PhantomJS/', $_SERVER['HTTP_USER_AGENT'])) {
			\WebConstructionSet\OutputBuffer\XsltHtml::init();
		} else {
			header('Content-Type: text/xml');
		}
		\WebConstructionSet\OutputBuffer\XmlFormatter::init();
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<?xml-stylesheet type="text/xsl" href="ui-en/index.xsl"?>';
		$userDb = new \WebConstructionSet\Database\Relational\User($this->db);
		$user = new \WebConstructionSet\Accounting\User($userDb);
		$this->userId = $user->getId();
		if ($this->userId) {
			echo '<user login="', htmlspecialchars($user->getLogin()), '">';
?>
<!--
	Register
	action: ?register=1

	Login
	action: ?login=1

	Logout
	action: ?logout=1

	Settings
	action: ?settings=1

	Tests
	action: ?tests=1

	Test
	action: ?test=xxx

	Tasks
	action: ?tasks=1

	Task
	action: ?task=xxx

	Schedule
	action: ?schedule=1

	History
	action: ?history=1

	Billing
	action: ?billing=1

	Stats
	action: ?stats=1
-->
<?php
			if (isset($_GET['register'])) {
				$user->logout();
				echo '<logout/>';
			} else if (isset($_GET['login'])) {
				$user->logout();
				echo '<logout/>';
			} else if (isset($_GET['logout'])) {
				$this->logout($user);
			} else if (isset($_GET['settings'])) {
				$this->settings($userDb, $user->getLogin());
			} else if (isset($_GET['tests'])) {
				$this->tests();
			} else if (isset($_GET['test'])) {
				$this->test();
			} else if (isset($_GET['tasks'])) {
				$this->tasks();
			} else if (isset($_GET['task'])) {
				$this->task();
			} else if (isset($_GET['schedule'])) {
				$this->schedule();
			} else if (isset($_GET['history'])) {
				$this->history();
			} else if (isset($_GET['billing'])) {
				$this->billing();
			} else if (isset($_GET['stats'])) {
				$this->stats();
			} else {
				$this->stats();
			}
		} else {
			echo '<user>';
?>
<!--
	Login
	action: index

	Register
	action: ?register=1

	Password Reset
	action: ?password_reset=1
-->
<?php
			if (isset($_GET['register'])) {
				$this->register($user);
			} else if (isset($_GET['password_reset'])) {
				$this->passwordReset($userDb);
			} else {
				$this->login($user);
			}
		}
		echo '</user>';
	}

	private function login(\WebConstructionSet\Accounting\User $user) {
?>
<!--
	Login
	method: post
	params: user password
	submit: login
-->
<?php
		if (isset($_POST['login'])) {
			if ($user->login($_POST['user'], $_POST['password'])) {
				$this->userId = $user->getId();
				echo '<message type="notice" value="login_ok"/>';
				$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
				$histMgr->add('login', ['ip' => $_SERVER['REMOTE_ADDR'], 'ua' => substr($_SERVER['HTTP_USER_AGENT'], 0, 128)]);
				$settMgr = new \AdvancedWebTesting\Settings\Manager($this->db, $this->userId);
				if (!$settMgr->get()['email'])
					echo '<message type="notice" value="set_up_email"/>';
			} else
				echo '<message type="error" value="bad_login"/>';
		}
		echo '<login/>';
	}

	private function register(\WebConstructionSet\Accounting\User $user) {
?>
<!--
	Register/Sign Up
	method: post
	params: user password1 password2 captcha
	submit: register
-->
<?php
		if (isset($_POST['register'])) {
			$captcha = new \AdvancedWebTesting\Captcha();
			if ($captcha->get() === $_POST['captcha']) {
				if ($_POST['password1'] == $_POST['password2']) {
					if ($user->register($_POST['user'], $_POST['password1'])) {
						echo '<message type="notice" value="register_ok"/>';
						echo '<message type="notice" value="set_up_email"/>';
					} else
						echo '<message type="error" value="login_busy"/>';
				} else
					echo '<message type="error" value="passwords_dont_match"/>';
			} else
				echo '<message type="error" value="bad_captcha"/>';
		}
		echo '<register/>';
	}

	private function passwordReset(\WebConstructionSet\Database\Relational\User $userDb) {
?>
<!--
	Password Reset

	Reset
	method: post
	params: user password1 password2 captcha
	submit: reset

	Reset (commit)
	method: get
	params: reset_code
-->
<?php
		if (isset($_POST['reset'])) {
			$captcha = new \AdvancedWebTesting\Captcha();
			if ($captcha->get() === $_POST['captcha']) {
				if ($_POST['password1'] == $_POST['password2']) {
					$userId = $userDb->getId($_POST['user']);
					if ($userId) {
						$settMgr = new \AdvancedWebTesting\Settings\Manager($this->db, $userId);
						$email = $settMgr->get()['email'];
						if ($email) {
							$_SESSION['password_reset_password'] = $_POST['password1'];
							$_SESSION['password_reset_user_id'] = $userId;
							$_SESSION['password_reset_code'] = rand();
							$mailMgr = new \AdvancedWebTesting\Mail\Manager($this->db, $userId);
							if ($mailMgr->passwordReset($email, $_POST['user'],
								\WebConstructionSet\Url\Tools::addParams(
									\WebConstructionSet\Url\Tools::getMyUrl(),
										['reset_code' => $_SESSION['password_reset_code']])))
							{
								echo '<message type="notice" value="email_confirmation_pending"/>';
							} else {
								error_log('Password Reset: user: ' . $_POST['user'] . ' - Mail Manager error');
								echo '<message type="error" value="password_reset_fail"/>';
							}
						} else {
							error_log('Password Reset: user: ' . $_POST['user'] . ' - no E-Mail');
							echo '<message type="error" value="password_reset_fail"/>';
						}
					} else {
						error_log('Password Reset: user: ' . $_POST['user'] . ' - bad login');
						echo '<message type="error" value="password_reset_fail"/>';
					}
				} else
					echo '<message type="error" value="passwords_dont_match"/>';
			} else
				echo '<message type="error" value="bad_captcha"/>';
		} else if (isset($_GET['reset_code'])) {
			if (isset($_SESSION['password_reset_code']) && $_SESSION['password_reset_code'] == $_GET['reset_code']) {
				if ($userDb->password($_SESSION['password_reset_user_id'], $_SESSION['password_reset_password'])) {
					echo '<message type="notice" value="password_change_ok"/>';
					$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $_SESSION['password_reset_user_id']);
					$histMgr->add('password_change', ['ip' => $_SERVER['REMOTE_ADDR'], 'ua' => substr($_SERVER['HTTP_USER_AGENT'], 0, 128)]);
				} else {
					error_log('Password Reset: userDb error');
					echo '<message type="error" value="password_reset_fail"/>';
				}
				unset($_SESSION['password_reset_password']);
				unset($_SESSION['password_reset_user_id']);
				unset($_SESSION['password_reset_code']);
			} else
				echo '<message type="error" value="bad_code"/>';
		}
		echo '<password_reset/>';
	}

	private function logout(\WebConstructionSet\Accounting\User $user) {
?>
<!--
	Logout
-->
<?php
		$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
		$histMgr->add('logout', ['ip' => $_SERVER['REMOTE_ADDR'], 'ua' => substr($_SERVER['HTTP_USER_AGENT'], 0, 128)]);
		$user->logout();
		unset($this->userId);
		echo '<logout/>';
	}

	private function settings(\WebConstructionSet\Database\Relational\User $userDb, $login) {
?>
<!--
	Settings

	Change password
	method: post
	params: password password1 password2

	Change e-mail
	method: post
	params: email

	Change e-mail (commit)
	method: get
	params: email_code

	On/Off e-mail reports on task failure
	method: post
	params: task_fail_email_report (0/1)

	On/Off e-mail reports on task success
	method: post
	params: task_success_email_report (0/1)

	Delete account
	method: post
	submit: delete_account

	Delete account (commit)
	method: get
	params: delete_account_code
-->
<?php
		$settMgr = new \AdvancedWebTesting\Settings\Manager($this->db, $this->userId);
		if (isset($_POST['password'])) {
			if ($userDb->check($login, $_POST['password'])) {
				if ($_POST['password1'] === $_POST['password2']) {
					if ($userDb->password($this->userId, $_POST['password1'])) {
						echo '<message type="notice" value="password_change_ok"/>';
						$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
						$histMgr->add('password_change', ['ip' => $_SERVER['REMOTE_ADDR'], 'ua' => substr($_SERVER['HTTP_USER_AGENT'], 0, 128)]);
					} else
						echo '<message type="error" value="password_change_fail"/>';
				} else
					echo '<message type="error" value="passwords_dont_match"/>';
			} else
				echo '<message type="error" value="bad_current_password"/>';
		}
		if (isset($_POST['task_fail_email_report']) || isset($_POST['task_success_email_report'])) {
			$settings = $settMgr->get();
			if ($settMgr->set(
				null,
				isset($_POST['task_fail_email_report']) ? $_POST['task_fail_email_report'] : null,
				isset($_POST['task_success_email_report']) ? $_POST['task_success_email_report'] : null
			)) {
				echo '<message type="notice" value="settings_change_ok"/>';
				$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
				$event = ['ip' => $_SERVER['REMOTE_ADDR'], 'ua' => substr($_SERVER['HTTP_USER_AGENT'], 0, 128)];
				foreach (['task_fail_email_report', 'task_success_email_report'] as $param)
					if (isset($_POST[$param])) {
						$event[$param] = $_POST[$param];
						if ($_POST[$param] != $settings[$param])
							$event['old_' . $param] = $settings[$param] ? $settings[$param] : 0;
					}
				$histMgr->add('settings_change', $event);
			} else
				echo '<message type="error" value="settings_change_fail"/>';
		}
		if (isset($_POST['email']) && $_POST['email']) {
			$oldEmail = $settMgr->get()['email'];
			$newEmail = $_POST['email'];
			if ($oldEmail != $newEmail) {
				$_SESSION['settings_email'] = $newEmail;
				$_SESSION['settings_email_code'] = rand();
				$mailMgr = new \AdvancedWebTesting\Mail\Manager($this->db, $this->userId);
				if ($mailMgr->emailVerification($newEmail, $login,
					\WebConstructionSet\Url\Tools::addParams(
						\WebConstructionSet\Url\Tools::getMyUrl(), ['email_code' => $_SESSION['settings_email_code']])))
				{
					echo '<message type="notice" value="email_confirmation_pending"/>';
				} else
					echo '<message type="error" value="email_change_fail"/>';
			} else
				echo '<message type="error" value="email_change_fail"/>';
		} else if (isset($_GET['email_code'])) {
			if (isset($_SESSION['settings_email_code']) && $_SESSION['settings_email_code'] == $_GET['email_code']) {
				$oldEmail = $settMgr->get()['email'];
				$newEmail = $_SESSION['settings_email'];
				if ($oldEmail != $newEmail) {
					if (!$oldEmail) {
						$billMgr = new \AdvancedWebTesting\Billing\Manager($this->db, $this->userId);
						$billMgr->service(\Config::SIGNUP_BONUS, 'Sign Up bonus');
					}
					if ($settMgr->set($newEmail)) {
						echo '<message type="notice" value="email_change_ok"/>';
						$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
						$histMgr->add('email_change', ['email' => $newEmail, 'old_email' => $oldEmail, 'ip' => $_SERVER['REMOTE_ADDR'], 'ua' => substr($_SERVER['HTTP_USER_AGENT'], 0, 128)]);
					} else
						echo '<message type="error" value="email_change_fail"/>';
				} else
					echo '<message type="error" value="email_change_fail"/>';
				unset($_SESSION['settings_email_code']);
				unset($_SESSION['settings_email']);
			} else
				echo '<message type="error" value="bad_code"/>';
		}
		if (isset($_POST['delete_account'])) {
			$settings = $settMgr->get();
			if (!$settings['undeletable']) {
				$email = $settings['email'];
				if ($email) {
					$_SESSION['delete_account_code'] = rand();
					$mailMgr = new \AdvancedWebTesting\Mail\Manager($this->db, $this->userId);
					if ($mailMgr->deleteAccount($email, $login,
						\WebConstructionSet\Url\Tools::addParams(
							\WebConstructionSet\Url\Tools::getMyUrl(), ['delete_account_code' => $_SESSION['delete_account_code']])))
					{
						echo '<message type="notice" value="email_confirmation_pending"/>';
					} else
						echo '<message type="error" value="delete_account_fail"/>';
				} else {
					$account = new \AdvancedWebTesting\User\Account($this->db, $this->userId);
					$data = $account->delete();
					if ($data)
						echo $data;
					else
						echo '<message type="notice" value="delete_account_ok"/>';
				}
			} else
				echo '<message type="error" value="delete_account_fail"/>';
		} else if (isset($_GET['delete_account_code'])) {
			if (isset($_SESSION['delete_account_code']) && $_SESSION['delete_account_code'] == $_GET['delete_account_code']) {
				unset($_SESSION['delete_account_code']);
				if (!$settMgr->get()['undeletable']) {
					$account = new \AdvancedWebTesting\User\Account($this->db, $this->userId);
					$data = $account->delete();
					if ($data)
						echo $data;
					else
						echo '<message type="notice" value="delete_account_ok"/>';
				} else
					echo '<message type="error" value="delete_account_fail"/>';
			} else
				echo '<message type="error" value="bad_code"/>';
		}
		echo '<settings';
		foreach ($settMgr->get() as $name => $value)
			echo ' ', $name, '="', htmlspecialchars($value), '"';
		echo '/>';
	}

	private function tests() {
?>
<!--
	Tests

	Add
	method: post
	params: name
	submit: add

	Delete
	method: post
	params: id
	submit: delete

	Restore
	method: post
	params: id
	submit: restore

	Rename
	method: post
	params: id name
	submit: rename

	Copy
	method: post
	params: id name
	submit: copy
-->
<?php
		$testMgr = new \AdvancedWebTesting\Test\Manager($this->db, $this->userId);
		if (isset($_POST['add'])) {
			if ($testId = $testMgr->add($_POST['name'])) {
				echo '<message type="notice" value="test_add_ok" id="', $testId, '"/>';
				$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
				$histMgr->add('test_add', ['test_id' => $testId, 'test_name' => $_POST['name']]);
			} else
				echo '<message type="error" value="test_add_fail"/>';
		} else if (isset($_POST['delete'])) {
			if ($tests = $testMgr->get([$_POST['id']])) {
				$test = $tests[0];
				if ($testMgr->delete($_POST['id'])) {
					echo '<message type="notice" value="test_delete_ok"/>';
					$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
					$histMgr->add('test_delete', ['test_id' => $_POST['id'], 'test_name' => $test['name']]);
				} else
					echo '<message type="error" value="test_delete_fail"/>';
			} else
				echo '<message type="error" value="bad_test_id"/>';
		} else if (isset($_POST['restore'])) {
			if ($tests = $testMgr->get([$_POST['id']])) {
				$test = $tests[0];
				if ($testMgr->restore($_POST['id'])) {
					echo '<message type="notice" value="test_restore_ok"/>';
					$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
					$histMgr->add('test_restore', ['test_id' => $_POST['id'], 'test_name' => $test['name']]);
				} else
					echo '<message type="error" value="test_restore_fail"/>';
			} else
				echo '<message type="error" value="bad_test_id"/>';
		} else if (isset($_POST['rename'])) {
			if ($tests = $testMgr->get([$_POST['id']])) {
				$test = $tests[0];
				if ($testMgr->rename($_POST['id'], $_POST['name'])) {
					echo '<message type="notice" value="test_rename_ok"/>';
					$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
					$histMgr->add('test_rename', ['test_id' => $_POST['id'], 'test_name' => $_POST['name'], 'old_test_name' => $test['name']]);
				} else
					echo '<message type="error" value="test_rename_fail"/>';
			} else
				echo '<message type="error" value="bad_test_id"/>';
		} else if (isset($_POST['copy'])) {
			if ($tests = $testMgr->get([$_POST['id']])) {
				$test = $tests[0];
				if ($testId = $testMgr->add($_POST['name'])) {
					$testActMgrSrc = new \AdvancedWebTesting\Test\Action\Manager($this->db, $_POST['id']);
					$testActMgrDst = new \AdvancedWebTesting\Test\Action\Manager($this->db, $testId);
					$testActMgrDst->import($testActMgrSrc->get());
					echo '<message type="notice" value="test_copy_ok"/>';
					$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
					$histMgr->add('test_copy', ['test_id' => $testId, 'test_name' => $_POST['name'],
						'orig_test_name' => $test['name'], 'orig_test_id' => $_POST['id']]);
				} else
					echo '<message type="error" value="test_copy_fail"/>';
			} else
				echo '<message type="error" value="bad_test_id"/>';
		}
		echo '<tests>';
		foreach ($testMgr->get() as $test) {
			echo '<test name="', htmlspecialchars($test['name']), '" id="', $test['id'], '"',
				' time="', $test['time'], '"';
			if ($test['deleted'])
				echo ' deleted="1"';
			echo '/>';
		}
		echo '</tests>';
		$this->taskTypes();
	}

	private function test() {
?>
<!--
	Test
	method: get
	params: test

	Add
	method: post
	params: type selector data
	submit: add

	Delete
	method: post
	params: id
	submit: delete

	Modify
	method: post
	params: id [selector] [data]
	submit: modify

	Insert
	method: post
	params: [id] type selector data
	submit: insert

	Import
	method: post
	params: data
	submit: import

	Clear (delete all actions)
	method: post
	submit: clear
-->
<?php
		$testId = $_GET['test'];
		$testMgr = new \AdvancedWebTesting\Test\Manager($this->db, $this->userId);
		if ($tests = $testMgr->get([$testId])) {
			$test = $tests[0];
			$testActMgr = new \AdvancedWebTesting\Test\Action\Manager($this->db, $testId);
			if (isset($_POST['add'])) {
				$actionId = $testActMgr->add($_POST['type'],
					\AdvancedWebTesting\Tools::valueOrNull($_POST, 'selector'),
					\AdvancedWebTesting\Tools::valueOrNull($_POST, 'data'));
				echo '<message type="notice" value="test_action_add_ok" id="', $actionId, '"/>';
				$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
				$event = [];
				foreach(['selector', 'data'] as $field)
					if (isset($_POST[$field]))
						$event[$field] = $_POST[$field];
				$histMgr->add('test_action_add', array_merge([
						'test_id' => $testId, 'test_name' => $test['name'],
						'action_id' => $actionId, 'type' => $_POST['type']], $event));
			} else if (isset($_POST['delete'])) {
				$actionId = $_POST['id'];
				if ($actions = $testActMgr->get([$actionId])) {
					$action = $actions[0];
					if ($testActMgr->delete($actionId)) {
						echo '<message type="notice" value="test_action_delete_ok"/>';
						$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
						$event = [];
						foreach (['type', 'selector', 'data'] as $field)
							if (isset($action[$field]))
								$event[$field] = $action[$field];
						$histMgr->add('test_action_delete', array_merge([
							'test_id' => $testId, 'test_name' => $test['name'],
							'action_id' => $actionId], $event));
					} else
						echo '<message type="error" value="test_action_delete_fail"/>';
				} else
					echo '<message type="error" value="bad_action_id"/>';
			} else if (isset($_POST['modify'])) {
				$actionId = $_POST['id'];
				if ($actions = $testActMgr->get([$actionId])) {
					$action = $actions[0];
					if ($testActMgr->modify($actionId,
						\AdvancedWebTesting\Tools::valueOrNull($_POST, 'selector', $action['selector']),
						\AdvancedWebTesting\Tools::valueOrNull($_POST, 'data', $action['data']))
					) {
						echo '<message type="notice" value="test_action_modify_ok"/>';
						$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
						$event = ['type' => $action['type']];
						foreach (['selector', 'data'] as $field)
							if (\AdvancedWebTesting\Tools::valueOrNull($_POST, $field, $action[$field]) !== null) {
								$event[$field] = $_POST[$field];
								$event['old_' . $field] = $action[$field];
							} else
								if ($action[$field] !== null)
									$event[$field] = $action[$field];
						$histMgr->add('test_action_modify', array_merge([
							'test_id' => $testId, 'test_name' => $test['name'],
							'action_id' => $actionId], $event));
					} else
						echo '<message type="error" value="test_action_modify_fail"/>';
				} else
					echo '<message type="error" value="bad_action_id"/>';
			} else if (isset($_POST['insert'])) {
				$actionId = $_POST['id'];
				if ($testActMgr->insert($actionId, $_POST['type'],
					\AdvancedWebTesting\Tools::valueOrNull($_POST, 'selector'),
					\AdvancedWebTesting\Tools::valueOrNull($_POST, 'data'))
				) {
					echo '<message type="notice" value="test_action_insert_ok"/>';
					$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
					$event = ['type' => $_POST['type']];
					foreach (['selector', 'data'] as $field)
						if (isset($_POST[$field]))
							$event[$field] = $_POST[$field];
					$histMgr->add('test_action_insert', array_merge([
						'test_id' => $testId, 'test_name' => $test['name'],
						'action_id' => $actionId], $event));
				} else
					echo '<message type="error" value="test_action_insert_fail"/>';
			} else if (isset($_POST['import'])) {
				$data = null;
				if (isset($_POST['data']))
					$data = json_decode($_POST['data'], true /* assoc */);
				else if (isset($_FILES['data']) && is_uploaded_file($_FILES['data']['tmp_name']))
					$data = json_decode(file_get_contents($_FILES['data']['tmp_name']), true /* assoc */);
				$valid = true;
				if ($valid)
					if (!is_array($data))
						$valid = false;
				if ($valid)
					foreach ($data as $data1)
						foreach (['id', 'type'] as $param)
							if (!isset($data1[$param])) {
								$valid = false;
								break 2;
							}
				if ($valid) {
					usort($data, function ($a, $b) {return $a['id']-$b['id'];});
					foreach ($data as $data1) {
						$actionId = $testActMgr->add($data1['type'],
							\AdvancedWebTesting\Tools::valueOrNull($data1, 'selector'),
							\AdvancedWebTesting\Tools::valueOrNull($data1, 'data'));
						echo '<message type="notice" value="test_action_add_ok" id="', $actionId, '"/>';
						$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
						$event = [];
						foreach(['selector', 'data'] as $field)
							if (\AdvancedWebTesting\Tools::valueOrNull($data1, $field) !== null)
								$event[$field] = $data1[$field];
						$histMgr->add('test_action_add', array_merge([
							'test_id' => $testId, 'test_name' => $test['name'],
							'action_id' => $actionId, 'type' => $data1['type']], $event));
					}
				} else
					echo '<message type="error" value="test_import_fail"/>';
			} else if (isset($_POST['clear'])) {
				$actions = $testActMgr->get();
				if ($actions)
					$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
				foreach ($actions as $action) {
					$actionId = $action['id'];
					if ($testActMgr->delete($actionId)) {
						echo '<message type="notice" value="test_action_delete_ok"/>';
						$event = [];
						foreach (['type', 'selector', 'data'] as $field)
							if (\AdvancedWebTesting\Tools::valueOrNull($action, $field) !== null)
								$event[$field] = $action[$field];
						$histMgr->add('test_action_delete', array_merge([
							'test_id' => $testId, 'test_name' => $test['name'],
							'action_id' => $actionId], $event));
					} else
						echo '<message type="error" value="test_action_delete_fail"/>';
				}
			}
			echo '<test id="', $testId, '" name="', htmlspecialchars($test['name']), '"',
				' time="', $test['time'], '" max_actions_cnt="', \Config::TEST_MAX_ACTIONS_CNT, '"';
			if ($test['deleted'])
				echo ' deleted="1"';
			echo '>';
			foreach ($testActMgr->get() as $action) {
				echo '<action id="', $action['id'], '" type="', htmlspecialchars($action['type']), '"';
				foreach (['selector', 'data'] as $param)
					if ($action[$param] !== null)
						echo ' ', $param, '="', htmlspecialchars($action[$param]), '"';
				echo '/>';
			}
			echo '</test>';
			$this->taskTypes();
		} else
			echo '<message type="error" value="bad_test_id"/><test/>';
	}

	private function tasks() {
?>
<!--
	Tasks
	method: get
	params: tasks [time]

	Add
	method: post
	params: test_id type [debug]
	submit: add

	Cancel
	method: post
	params: task_id
	submit: cancel
-->
<?php
		$taskMgr = new \AdvancedWebTesting\Task\Manager($this->db, $this->userId);
		if (isset($_POST['add'])) {
			$testId = $_POST['test_id'];
			$type = $_POST['type'];
			$debug = false;
			if (isset($_POST['debug']) && $_POST['debug'])
				$debug = true;
			$testMgr = new \AdvancedWebTesting\Test\Manager($this->db, $this->userId);
			if ($tests = $testMgr->get([$testId])) {
				$test = $tests[0];
				if (!$test['deleted']) {
					$billMgr = new \AdvancedWebTesting\Billing\Manager($this->db, $this->userId);
					if ($billMgr->getAvailableActionsCnt() >= \AdvancedWebTesting\Billing\Price::TASK_START) {
						if ($taskId = $taskMgr->add($testId, $test['name'], $type, $debug)) {
							$billMgr->startTask($taskId, $test['id'], $test['name']);
							echo '<message type="notice" value="task_add_ok" id="', $taskId, '"/>';
							$statMgr = new \AdvancedWebTesting\Stats\Manager($this->db, $this->userId);
							$statMgr->add(1);
							$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
							$histMgr->add('task_add', ['task_id' => $taskId,
								'test_id' => $testId, 'test_name' => $test['name'],
								'type' => $type]);
						} else
							echo '<message type="error" value="task_add_fail"/>';
					} else
						echo '<message type="error" value="no_funds"/>';
				} else
					echo '<message type="error" value="test_is_deleted"/>';
			} else
				echo '<message type="error" value="bad_test_id"/>';
		} else if (isset($_POST['cancel'])) {
			$taskId = $_POST['task_id'];
			if ($tasks = $taskMgr->get([$taskId])) {
				$task = $tasks[0];
				if ($taskMgr->cancel($taskId)) {
					echo '<message type="notice" value="task_cancel_ok"/>';
					$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
					$histMgr->add('task_cancel', ['task_id' => $taskId,
						'test_id' => $task['test_id'], 'test_name' => $task['test_name']]);
				} else
					echo '<message type="error" value="task_cancel_fail"/>';
			} else
				echo '<message type="error" value="bad_task_id"/>';
		}
		$time = 0;
		if (isset($_GET['time']) && $_GET['time'])
			$time = $_GET['time'];
		echo '<tasks', $time ? ' time="' . $time . '"' : '', '>';
		foreach ($taskMgr->get(null, $time) as $task)
			echo '<task id="', $task['id'], '" test_id="', $task['test_id'], '"',
				' test_name="', htmlspecialchars($task['test_name']), '"',
				' type="', htmlspecialchars($task['type']), '"',
				' ', $task['debug'] ? ' debug="1"' : '',
				' status="', \AdvancedWebTesting\Task\Status::toString($task['status']), '"',
				' time="', $task['time'], '"/>';
		echo '</tasks>';
		$this->taskTypes();
	}

	private function task() {
?>
<!--
	Task
	method: get
	params: task
-->
<?php
		$task = new \AdvancedWebTesting\User\Task($this->db, $this->userId);
		echo $task->get($_GET['task']);
		$this->taskTypes();
	}

	private function taskTypes() {
		echo '<task_types>';
		$typeMgr = new \AdvancedWebTesting\Task\Type\Manager($this->db);
		foreach ($typeMgr->get() as $type)
			echo '<type name="', $type['name'], '" id="', $type['id'], '" parent_id="', $type['parent_id'], '"/>';
		echo '</task_types>';
	}

	private function schedule() {
?>
<!--
	Schedule

	Add
	method: post
	params: name test_id type start period
	submit: add

	Delete
	method: post
	params: id
	submit: delete

	Modify
	method: post
	params: id [name] [test_id] [type] [start] [period]
	submit: modify
-->
<?php
		$taskSched = new \AdvancedWebTesting\Task\Schedule($this->db, $this->userId);
		$testMgr = new \AdvancedWebTesting\Test\Manager($this->db, $this->userId);
		if (isset($_POST['add'])) {
			if ($tests = $testMgr->get([$_POST['test_id']])) {
				$test = $tests[0];
				if ($schedId = $taskSched->add($_POST['start'], $_POST['period'], $_POST['test_id'], $_POST['type'], $_POST['name'])) {
					echo '<message type="notice" value="sched_add_ok"/>';
					$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
					$histMgr->add('sched_add', ['sched_id' => $schedId, 'sched_name' => $_POST['name'],
						'test_id' => $_POST['test_id'], 'test_name' => $test['name'], 'test_deleted' => $test['deleted'],
						'type' => $_POST['type'], 'start' => $_POST['start'], 'period' => $_POST['period']]);
				} else
					echo '<message type="error" value="sched_add_fail"/>';
			} else
				echo '<message type="error" value="bad_test_id"/>';
		} else if (isset($_POST['delete'])) {
			if ($scheds = $taskSched->get([$_POST['id']])) {
				$sched = $scheds[0];
				if ($taskSched->delete($_POST['id'])) {
					echo '<message type="notice" value="sched_delete_ok"/>';
					$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
					if ($tests = $testMgr->get([$sched['test_id']]))
						$test = $tests[0];
					else
						$test = ['name' => '__deleted__', 'deleted' => true];
					$histMgr->add('sched_delete', ['sched_id' => $_POST['id'], 'sched_name' => $sched['name'],
						'test_id' => $sched['test_id'], 'test_name' => $test['name'], 'test_deleted' => $test['deleted'],
						'type' => $sched['type'], 'start' => $sched['start'], 'period' => $sched['period']]);
				} else
					echo '<message type="error" value="sched_delete_fail"/>';
			} else
				echo '<message type="error" value="bad_sched_id"/>';
		} else if (isset($_POST['modify'])) {
			if ($scheds = $taskSched->get([$_POST['id']])) {
				$sched = $scheds[0];
				if ($taskSched->modify($_POST['id'],
					\AdvancedWebTesting\Tools::valueOrNull($_POST, 'start', $sched['start']),
					\AdvancedWebTesting\Tools::valueOrNull($_POST, 'period', $sched['period']),
					\AdvancedWebTesting\Tools::valueOrNull($_POST, 'test_id', $sched['test_id']),
					\AdvancedWebTesting\Tools::valueOrNull($_POST, 'type', $sched['type']),
					\AdvancedWebTesting\Tools::valueOrNull($_POST, 'name', $sched['name']))
				) {
					echo '<message type="notice" value="sched_modify_ok"/>';
					$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
					$event = [];
					foreach (['start' => 'start', 'period' => 'period', 'name' => 'sched_name', 'test_id' => 'test_id', 'type' => 'type'] as $param => $evParam)
						if (\AdvancedWebTesting\Tools::valueOrNull($_POST, $param, $sched[$param]) !== null) {
							$event[$evParam] = $_POST[$param];
							$event['old_' . $evParam] = $sched[$param];
						} else
							$event[$evParam] = $sched[$param];
					if ($tests = $testMgr->get([$event['test_id']]))
						$event['test_name'] = $tests[0]['name'];
					else
						$event['test_name'] = '__deleted__';
					if (isset($event['old_test_id']))
						if ($tests = $testMgr->get([$event['old_test_id']]))
							$event['old_test_name'] = $tests[0]['name'];
						else
							$event['old_test_name'] = '__deleted__';
					$histMgr->add('sched_modify', array_merge(['sched_id' => $_POST['id']], $event));
				} else
					echo '<message type="error" value="sched_modify_fail"/>';
			} else
				echo '<message type="error" value="bad_sched_id"/>';
		}
		echo '<schedule>';
		foreach ($taskSched->get() as $sched)
			echo '<task id="', $sched['id'], '" name="', $sched['name'], '"',
				' start="', $sched['start'], '" period="', $sched['period'], '"',
				' type="', htmlspecialchars($sched['type']), '" test_id="', $sched['test_id'], '"/>';
		foreach ($testMgr->get() as $test)
			if (!$test['deleted'])
				echo '<test name="', htmlspecialchars($test['name']), '" id="', $test['id'], '"/>';
		echo '</schedule>';
		$this->taskTypes();
	}

	private function history() {
?>
<!--
	History
	method: get
	params: history [time]
-->
<?php
		$time = 0;
		if (isset($_GET['time']) && $_GET['time'])
			$time = $_GET['time'];
		echo '<history', $time ? ' time="' . $time . '"' : '', '>';
		$histMgr = new \AdvancedWebTesting\History\Manager($this->db, $this->userId);
		foreach ($histMgr->get($time) as $event) {
			echo '<event name="', htmlspecialchars($event['name']), '" time="', $event['time'], '"';
			foreach ($event['data'] as $param => $value)
				echo ' ', $param, '="', htmlspecialchars($value), '"';
			echo '/>';
		}
		echo '</history>';
		$this->taskTypes();
	}

	private function billing() {
?>
<!--
	Billing
	method: get
	params: biling [time]

	Top Up
	method: post
	params: payment_type actions_cnt [subscription]
	submit: top_up

	Refund Transaction
	method: post
	params: id
	submit: refund

	Process Pending Transaction
	method: post
	params: payment_type id [code]
	submit: process_pending_transaction

	Cancel Pending Transaction
	method: post
	params: payment_type id
	submit: cancel_pending_transaction

	Cancel Subscription
	method: post
	params: payment_type id
	submit: cancel_subscription

	Top Up by Subscription
	method: post
	params: payment_type id
	submit: top_up_subscription

	Modify Subscription
	method: post
	params: payment_type id actions_cnt
	submit: modify_subscription
-->
<?php
		$billMgr = new \AdvancedWebTesting\Billing\Manager($this->db, $this->userId);
		if (isset($_POST['top_up'])) {
			$actionsCnt = $_POST['actions_cnt'] + 0;
			if ($actionsCnt && \AdvancedWebTesting\Billing\PaymentType::toString($_POST['payment_type'])) {
				$paymentType = $_POST['payment_type'];
				$pendingTransactionId = $billMgr->topUp($actionsCnt, $paymentType, isset($_POST['subscription']) && $_POST['subscription']);
				if ($pendingTransactionId) {
					$data = $billMgr->getPendingTransactions($paymentType, [$pendingTransactionId]);
					if ($data) {
						$transaction = $data[0];
						echo '<message type="notice" value="payment_pending" payment_type="', $paymentType, '" id="', $pendingTransactionId, '"/>';
					} else
						echo '<message type="error" value="top_up_fail"/>';
				} else
					echo '<message type="error" value="top_up_fail"/>';
			} else
				echo '<message type="error" value="bad_params"/>';
		} else if (isset($_POST['refund'])) {
			if ($billMgr->refund($_POST['id']))
				echo '<message type="notice" value="refund_ok"/>';
			else
				echo '<message type="error" value="refund_fail"/>';
		} else if (isset($_POST['process_pending_transaction'])) {
			if (\AdvancedWebTesting\Billing\PaymentType::toString($_POST['payment_type']))
				if ($billMgr->processPendingTransaction($_POST['payment_type'], $_POST['id'], isset($_POST['code']) ? $_POST['code'] : null))
					echo '<message type="notice" value="process_pending_transaction_ok"/>';
				else
					echo '<message type="error" value="process_pending_transaction_fail"/>';
			else
				echo '<message type="error" value="bad_params"/>';
		} else if (isset($_POST['cancel_pending_transaction'])) {
			if (\AdvancedWebTesting\Billing\PaymentType::toString($_POST['payment_type']))
				if ($billMgr->cancelPendingTransaction($_POST['payment_type'], $_POST['id']))
					echo '<message type="notice" value="cancel_pending_transaction_ok"/>';
				else
					echo '<message type="error" value="cancel_pending_transaction_fail"/>';
			else
				echo '<message type="error" value="bad_params"/>';
		} else if (isset($_POST['cancel_subscription'])) {
			if (\AdvancedWebTesting\Billing\PaymentType::toString($_POST['payment_type']))
				if ($billMgr->cancelSubscription($_POST['payment_type'], $_POST['id']))
					echo '<message type="notice" value="cancel_subscription_ok"/>';
				else
					echo '<message type="error" value="cancel_subscription_fail"/>';
			else
				echo '<message type="error" value="bad_params"/>';
		} else if (isset($_POST['top_up_subscription'])) {
			if (\AdvancedWebTesting\Billing\PaymentType::toString($_POST['payment_type']))
				if ($billMgr->processSubscription($_POST['payment_type'], $_POST['id']))
					echo '<message type="notice" value="top_up_subscription_ok"/>';
				else
					echo '<message type="error" value="top_up_subscription_fail"/>';
			else
				echo '<message type="error" value="bad_params"/>';
		} else if (isset($_POST['modify_subscription'])) {
			$actionsCnt = $_POST['actions_cnt'] + 0;
			if ($actionsCnt && \AdvancedWebTesting\Billing\PaymentType::toString($_POST['payment_type']))
				if ($billMgr->modifySubscription($_POST['payment_type'], $_POST['id'], $actionsCnt))
					echo '<message type="notice" value="modify_subscription_ok"/>';
				else
					echo '<message type="error" value="modify_subscription_fail"/>';
			else
				echo '<message type="error" value="bad_params"/>';
		}
		if (isset($_GET['token'])) {
			// PayPal hack
			$token = $_GET['token'];
			$tokenFound = false;
			foreach ($billMgr->getPendingTransactions(\AdvancedWebTesting\Billing\PaymentType::PAYPAL) as $pendingTransaction)
				if ($pendingTransaction['payment_data'] == $token) {
					$tokenFound = true;
					if ($billMgr->processPendingTransaction(\AdvancedWebTesting\Billing\PaymentType::PAYPAL, $pendingTransaction['id']))
						echo '<message type="notice" value="paypal_ok"/>';
					else
						echo '<message type="error" value="paypal_fail"/>';
					break;
				}
			if (!$tokenFound)
				echo '<message type="error" value="bad_paypal_token"/>';
		}
		if (isset($_GET['LMI_PAYMENT_NO'])) {
			// WebMoney hack
			$paymentNumber = $_GET['LMI_PAYMENT_NO'];
			$paymentNumberFound = false;
			foreach ($billMgr->getPendingTransactions(\AdvancedWebTesting\Billing\PaymentType::WEBMONEY) as $pendingTransaction)
				if (isset($pendingTransaction['transaction_id']) && $pendingTransaction['transaction_id'] == $paymentNumber) {
					$paymentNumberFound = true;
					if ($billMgr->processPendingTransaction(\AdvancedWebTesting\Billing\PaymentType::WEBMONEY, $pendingTransaction['id']))
						echo '<message type="notice" value="webmoney_ok"/>';
					else
						echo '<message type="error" value="webmoney_fail"/>';
					break;
				}
			if (!$paymentNumberFound)
				echo '<message type="error" value="bad_webmoney_payment_number"/>';
		}
		$time = time() - 42 * 86400;
		if (isset($_GET['time']) && $_GET['time'])
			$time = $_GET['time'];
		echo '<billing actions_available="', $billMgr->getAvailableActionsCnt(), '"', $time ? ' time="' . $time . '"' : '', '>';
		foreach ($billMgr->getTransactions(null, $time) as $transaction) {
			unset($transaction['user_id']);
			echo '<transaction';
			foreach ($transaction as $name => $value) {
				echo ' ', $name, '="';
				if ($name == 'type')
					echo \AdvancedWebTesting\Billing\TransactionType::toString($value);
				else
					echo htmlspecialchars($value);
				echo '"';
			}
			echo '/>';
		}
		foreach ($billMgr->getPendingTransactions() as $pendingTransaction) {
			unset($pendingTransaction['user_id']);
			echo '<pending_transaction';
			foreach ($pendingTransaction as $name => $value)
				echo ' ', $name, '="', htmlspecialchars($value), '"';
			echo '/>';
		}
		foreach ($billMgr->getSubscriptions() as $subscription) {
			unset($subscription['user_id']);
			echo '<subscription';
			foreach ($subscription as $name => $value)
				echo ' ', $name, '="', htmlspecialchars($value), '"';
			echo '/>';
		}
		echo '</billing>';
	}

	private function stats() {
?>
<!--
	Stats
-->
<?php
		$testsCnt = 0;
		$testIds = [];
		$testMgr = new \AdvancedWebTesting\Test\Manager($this->db, $this->userId);
		foreach ($testMgr->get() as $test)
			if (!$test['deleted']) {
				++$testsCnt;
				$testIds[$test['id']] = true;
			}
		$schedsCnt = 0;
		$schedMgr = new \AdvancedWebTesting\Task\Schedule($this->db, $this->userId);
		foreach ($schedMgr->get() as $sched)
			if (isset($testIds[$sched['test_id']]))
				++$schedsCnt;
		$spendingsMonthly = 0;
		$billMgr = new \AdvancedWebTesting\Billing\Manager($this->db, $this->userId);
		foreach ($billMgr->getTransactions(null, time() - 86400 * 31) as $transaction)
			switch ($transaction['type']) {
				case \AdvancedWebTesting\Billing\TransactionType::TASK_START:
				case \AdvancedWebTesting\Billing\TransactionType::TASK_FINISH:
					$spendingsMonthly += - $transaction['actions_cnt'];
			}
		echo '<stats tests="', $testsCnt, '" scheds="', $schedsCnt, '"',
			' spendings_monthly="', $spendingsMonthly, '" actions_available="', $billMgr->getAvailableActionsCnt(), '">';
		$maxTime = time();
		$minTime = 0;
		$statMgr = new \AdvancedWebTesting\Stats\Manager($this->db, $this->userId);
		foreach ($statMgr->get() as $stat) {
			echo '<stat time="', $stat['time'], '" tasks_added="', $stat['tasks_added'], '" tasks_finished="', $stat['tasks_finished'], '"',
				' tasks_failed="', $stat['tasks_failed'], '" actions_executed="', $stat['actions_executed'], '"/>';
			if ($stat['time'] < $maxTime)
				$maxTime = $stat['time'];
			if ($stat['time'] > $minTime)
				$minTime = $stat['time'];
		}
		$time = time();
		for ($time = $time - 86400 * \Config::PURGE_PERIOD - $time % 86400; $time < $maxTime; $time += 86400)
			echo '<stat time="', $time, '" tasks_finished="0" tasks_failed="0" actions_executed="0"/>';
		$time = time();
		for ($time = $time - $time % 86400; $time > $minTime; $time -= 86400)
			echo '<stat time="', $time, '" tasks_finished="0" tasks_failed="0" actions_executed="0"/>';
		echo '</stats>';
	}
}
