<?php

namespace AdvancedWebTesting\Task;

/**
 * Управление задачами
 * Model (MVC)
 */
class Manager {
	private $db, $tasks;

	public function __construct(\WebConstructionSet\Database\Relational $db, $userId) {
		$this->db = $db;
		$fields = [];
		if ($userId !== null)
			$fields['user_id'] = $userId;
		$this->tasks = new \WebConstructionSet\Database\Relational\TableWrapper($db, 'tasks', $fields);
	}

	/**
	 * Создать новую задачу
	 * @param integer $testId
	 * @param string $testName
	 * @param string $type
	 * @param boolean $debug
	 * @throws \ErrorException
	 * @return $taskId|null
	 */
	public function add($testId, $testName, $type, $debug = false) {
		if ($type === null) {
			$typeMgr = new Type\Manager($this->db);
			$types = $typeMgr->get($typeMgr->getTop());
			$type = $types[0]['name'];
		}
		$testActMgr = new \AdvancedWebTesting\Test\Action\Manager($this->db, $testId);
		$actions = $testActMgr->get();
		if (count($actions) > \Config::TEST_MAX_ACTIONS_CNT)
			return null;
		if ($taskId = $this->tasks->insert(['test_id' => $testId, 'test_name' => $testName, 'type' => $type, 'debug' => $debug, 'time' => time()])) {
			$taskActMgr = new \AdvancedWebTesting\Task\Action\Manager($this->db, $taskId);
			$taskActMgr->import($actions);
			if (!$this->tasks->update(['status' => Status::INITIAL], ['task_id' => $taskId]))
				throw new \ErrorException('Task ' . $taskId . ' final update failed', null, null, __FILE__, __LINE__);
		} else {
			throw new \ErrorException('Task insert failed', null, null, __FILE__, __LINE__);
		}
		return $taskId;
	}

	/**
	 * Отменить задачу
	 * @param integer $taskId
	 * @return boolean
	 */
	public function cancel($taskId) {
		if ($this->tasks->update(['status' => Status::CANCELED, 'time' => time()], ['task_id' => $taskId, 'status' => Status::INITIAL]))
			return true;
		$data = $this->tasks->select(['time'], ['task_id' => $taskId]);
		if (!$data)
			return false;
		$task = $data[0];
		$taskActMgr = new \AdvancedWebTesting\Task\Action\Manager($this->db, $taskId);
		if ($task['time'] <= time() - \Config::TASK_ACTION_TIMEOUT * count($taskActMgr->get())) {
			$cnt = $this->tasks->update(['status' => Status::CANCELED, 'time' => time()], ['task_id' => $taskId, 'status' => Status::RUNNING]);
			$cnt += $this->tasks->update(['status' => Status::CANCELED, 'time' => time()], ['task_id' => $taskId, 'status' => Status::STARTING]);
			return $cnt;
		}
		return false;
	}

	/**
	 * Получить
	 * @param [integer]|null $taskIds null - все
	 * @return [][id => integer, test_id => integer, test_name => string, type => string, debug => boolean,
	 *  status => integer, result => string|null, node_id => string|null, time => integer, user_id => integer]
	 */
	public function get($taskIds = null) {
		$fields = ['task_id', 'test_id', 'test_name', 'type', 'debug', 'status', 'result', 'node_id', 'time', 'user_id'];
		$data = [];
		if ($taskIds === null)
			$data = $this->tasks->select($fields);
		else
			foreach ($taskIds as $taskId)
				if ($data1 = $this->tasks->select($fields, ['task_id' => $taskId]))
					$data = array_merge($data, $data1);
		foreach ($data as &$data1) {
			$data1['id'] = $data1['task_id'];
			unset($data1['task_id']);
		}
		return $data;
	}

	/**
	 * Выбрать задачу и перевести её в status = STARTING
	 * @param string $type
	 * @param string $nodeId
	 * @return [id => integer, debug => boolean, type => string]|null
	 */
	public function lock($type, $nodeId) {
		$typeMgr = new Type\Manager($this->db);
		foreach ($typeMgr->get($typeMgr->getCompatible($type)) as $typeData) {
			$type1 = $typeData['name'];
			if ($data = $this->tasks->select(['task_id', 'debug'], ['status' => Status::INITIAL, 'type' => $type1]))
				foreach ($data as $data1) {
					$task = ['type' => $type];
					foreach (['task_id' => 'id', 'debug' => 'debug'] as $src => $dst)
						$task[$dst] = $data1[$src];
					if ($this->tasks->update(['status' => Status::STARTING, 'type' => $type, 'node_id' => $nodeId, 'time' => time()], ['task_id' => $data1['task_id'], 'status' => Status::INITIAL]))
						return $task;
				}
		}
		return null;
	}

	/**
	 * Перевести задачу из status = STARTING в status = RUNNING
	 * @param integer $taskId
	 * @return boolean
	 */
	public function start($taskId) {
		return $this->tasks->update(['status' => Status::RUNNING, 'time' => time()], ['task_id' => $taskId, 'status' => Status::STARTING]);
	}

	/**
	 * Перевести задачу из status = RUNNING в $status
	 * @param integer $taskId
	 * @param integer $status
	 * @param string $result
	 * @return boolean
	 */
	public function finish($taskId, $status, $result) {
		return $this->tasks->update(['status' => $status, 'result' => $result, 'time' => time()], ['task_id' => $taskId, 'status' => Status::RUNNING]);
	}

	/**
	 * @param integer $taskId
	 * @return integer
	 */
	public function getUserId($taskId) {
		$data = $this->tasks->select(['user_id'], ['task_id' => $taskId]);
		if ($data)
			return $data[0]['user_id'];
		return null;
	}

	/**
	 * Перевести все задачи старше $time из status = RUNNING|STARTING в status = INITIAL
	 * @param integer $time Задачи с временем модификации (time) меньше заданного будут перезаущены
	 * @return integer Количество перезапущенных задач
	 */
	public function restart($time) {
		$cnt = $this->tasks->update(['status' => Status::INITIAL, 'time' => time()], ['time' => $this->tasks->predicate('less_eq', $time), 'status' => Status::RUNNING]);
		$cnt += $this->tasks->update(['status' => Status::INITIAL, 'time' => time()], ['time' => $this->tasks->predicate('less_eq', $time), 'status' => Status::STARTING]);
		return $cnt;
	}
}