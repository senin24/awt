<?php

namespace AdvancedWebTesting\History;

/**
 * Лог
 * Model (MVC)
 */
class Manager {
	private $history, $userId;

	public function __construct(\WebConstructionSet\Database\Relational $db, $userId) {
		$this->history = new \WebConstructionSet\Database\Relational\History($db);
		$this->userId = $userId;
	}

	/**
	 * @param string $name
	 * @param mixed $data
	 * @return boolean
	 */
	public function add($name, $data) {
		return $this->history->add($name, $data, $this->userId);
	}

	/**
	 * @return [][time => integer, name => string, data => mixed, user_id => integer]
	 */
	public function get() {
		$events = [];
		$data = $this->history->get($this->userId);
		foreach ($data as $data1) {
			$event = [];
			foreach (['key' => 'user_id', 'time' => 'time', 'name' => 'name', 'data' => 'data'] as $src => $dst)
				$event[$dst] = $data1[$src];
			$events[] = $event;
		}
		return $events;
	}
}