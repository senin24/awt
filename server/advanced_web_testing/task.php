<?php

namespace AdvancedWebTesting;

class Task {
	private $db;

	public function __construct() {
		$this->db = new \WebConstructionSet\Database\Relational\Pdo(\Config::DB_DSN, \Config::DB_USER, \Config::DB_PASSWORD);
	}

	/*
	 * Get & lock a task
	 * method: post
	 * params: task_type node_id token
	 *
	 * Update the task
	 * method: post
	 * params: task_id status=started vnc token
	 *
	 * method: post
	 * enctype: multipart/form-data
	 * params: task_id status=succeeded|failed [fail1 .. fail[action_id] .. failXX] token
	 * files: [scrn1 .. scrn{action_id} .. scrnXX]
	 */

	public function run() {
		if (isset($_POST['task_type']))
			$this->lock();
		else if (isset($_POST['task_id']))
			$this->update();
	}

	private function lock() {
		$type = $_POST['task_type'];
		$db = $this->db;
		if ($this->checkAuth()) {
			while(true) {
				foreach ($this->getCompatibleTypes($type) as $type2) {
					$tasks = $db->select('tasks', ['task_id', 'debug'], ['status' => \AdvancedWebTesting\Task\Status::INITIAL, 'type' => $type2]);
					if ($tasks)
						break;
				}
				if (!$tasks)
					break;
				foreach ($tasks as $task)
					if ($db->update('tasks', ['status' => \AdvancedWebTesting\Task\Status::STARTING, 'type' => $type], ['task_id' => $task['task_id'], 'status' => \AdvancedWebTesting\Task\Status::INITIAL])) {
						$taskId = $task['task_id'];
						$taskDebug = $task['debug'];
						break 2;
					}
			}
			if (isset($taskId)) {
				$db->update('tasks', ['data' => $_POST['node_id'], 'time' => time()], ['task_id' => $taskId]);
				$taskActions = $db->select('task_actions', ['type', 'selector', 'data', 'action_id'], ['task_id' => $taskId]);
				$result = [
					'task_id' => $taskId,
					'task_actions' => $taskActions
				];
				if ($taskDebug)
					$result['task_debug'] = 1;
			} else {
				$result = [
					'empty' => 1
				];
			}
		} else {
			$result = [
				'fail' => 'auth check failed'
			];
		}
		echo json_encode($result);
	}

	private function update() {
		$taskId = $_POST['task_id'];
		$status = $_POST['status'];
		$db = $this->db;
		if ($this->checkAuth()) {
			switch ($status) {
				case 'running':
					if ($db->update('tasks', ['status' => \AdvancedWebTesting\Task\Status::RUNNING, 'time' => time()], ['task_id' => $taskId, 'status' => \AdvancedWebTesting\Task\Status::STARTING]))
						$result['ok'] = 1;
					else
						$result['fail'] = 'task update failed';
					break;
				case 'succeeded':
				case 'failed':
					$taskDataDir = $taskId . '-' . rand();
					$statusId = $status == 'succeeded' ? \AdvancedWebTesting\Task\Status::SUCCEEDED : \AdvancedWebTesting\Task\Status::FAILED;
					if ($db->update('tasks', ['data' => $taskDataDir, 'status' => $statusId, 'time' => time()], ['task_id' => $taskId, 'status' => \AdvancedWebTesting\Task\Status::RUNNING])) {
						$taskDataPath = \Config::$rootPath . \Config::RESULT_DATA_PATH . $taskDataDir . '/';
						$this->prepareTaskDataPath($taskDataPath);
						$result['ok'] = 0;
						foreach ($db->select('task_actions', ['type', 'selector', 'data', 'action_id'], ['task_id' => $taskId]) as $action) {
							$scrnX = 'scrn' . $action['action_id'];
							if (isset($_FILES[$scrnX])) {
								$scrnFilename = basename($_FILES[$scrnX]['name']);
								$result['ok'] += move_uploaded_file($_FILES[$scrnX]['tmp_name'], $taskDataPath . $scrnFilename);
								$result['ok'] += $db->update('task_actions', ['scrn_filename' => $scrnFilename], ['task_id' => $taskId, 'action_id' => $action['action_id']]);
							}
							$failX = 'fail' . $action['action_id'];
							if (isset($_POST[$failX]))
								$result['ok'] += $db->update('task_actions', ['failed' => $_POST[$failX]], ['task_id' => $taskId, 'action_id' => $action['action_id']]);
						}
					} else
						$result['fail'] = 'task update failed';
					break;
				default:
					$result['fail'] = 'bad status';
					break;
			}
		} else {
			$result = [
				'fail' => 'auth check failed'
			];
		}
		echo json_encode($result);
	}

	private function checkAuth() {
		return $_POST['token'] == \Config::TESTNODE_TOKEN;
	}

	private function prepareTaskDataPath($path) {
		exec('rm -Rf ' . $path);
		mkdir($path);
	}
	
	private function getCompatibleTypes($typeName) {
		$db = $this->db;
		if ($types = $db->select('task_types', ['parent_type_id'], ['name' => $typeName])) {
			$type = $types[0];
			$typeNames = [$typeName];
			$typeId = $type['parent_type_id'];
		}
		while ($typeId) {
			if ($types = $db->select('task_types', ['name', 'parent_type_id'], ['type_id' => $typeId])) {
				$type = $types[0];
				$typeNames[] = $type['name'];
				$typeId = $type['parent_type_id'];
			} else
				$typeId = null;
		}
		return $typeNames;
	}
}