<?php

if (!function_exists('activity_log_column_exists')) {
	function activity_log_column_exists(mysqli $conn, string $column): bool
	{
		$escapedColumn = mysqli_real_escape_string($conn, $column);
		$result = mysqli_query($conn, "SHOW COLUMNS FROM activity_logs LIKE '" . $escapedColumn . "'");
		if (!$result) {
			return false;
		}
		$exists = mysqli_num_rows($result) > 0;
		mysqli_free_result($result);
		return $exists;
	}
}

if (!function_exists('activity_log_index_exists')) {
	function activity_log_index_exists(mysqli $conn, string $indexName): bool
	{
		$escaped = mysqli_real_escape_string($conn, $indexName);
		$result = mysqli_query($conn, "SHOW INDEX FROM activity_logs WHERE Key_name = '" . $escaped . "'");
		if (!$result) {
			return false;
		}
		$exists = mysqli_num_rows($result) > 0;
		mysqli_free_result($result);
		return $exists;
	}
}

if (!function_exists('ensure_activity_log_table')) {
	function ensure_activity_log_table(mysqli $conn): void
	{
		static $ensured = false;
		if ($ensured) {
			return;
		}

		$createSql = "CREATE TABLE IF NOT EXISTS activity_logs (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) NULL,
			actor_id BIGINT(20) NULL,
			target_user_id BIGINT(20) NULL,
			actor_role VARCHAR(50) NULL,
			actor_unique_id VARCHAR(100) NULL,
			actor_username VARCHAR(100) NULL,
			actor_name VARCHAR(150) NULL,
			target_role VARCHAR(50) NULL,
			target_unique_id VARCHAR(100) NULL,
			target_username VARCHAR(100) NULL,
			target_name VARCHAR(150) NULL,
			object_type VARCHAR(100) NULL,
			object_id VARCHAR(100) NULL,
			object_label VARCHAR(255) NULL,
			action VARCHAR(150) NOT NULL,
			event_label VARCHAR(255) NULL,
			details TEXT NULL,
			metadata JSON NULL,
			ip_address VARCHAR(45) NULL,
			user_agent VARCHAR(255) NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_user_id (user_id),
			KEY idx_actor_id (actor_id),
			KEY idx_target_user_id (target_user_id),
			KEY idx_action (action),
			KEY idx_object (object_type, object_id),
			KEY idx_created_at (created_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

		mysqli_query($conn, $createSql);

		$alterStatements = [
			"ALTER TABLE activity_logs MODIFY COLUMN id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT",
			"ALTER TABLE activity_logs MODIFY COLUMN user_id BIGINT(20) NULL",
			"ALTER TABLE activity_logs MODIFY COLUMN actor_id BIGINT(20) NULL",
			"ALTER TABLE activity_logs MODIFY COLUMN action VARCHAR(150) NOT NULL"
		];

		foreach ($alterStatements as $sql) {
			if ($sql !== '') {
				mysqli_query($conn, $sql);
			}
		}

		$columnDefinitions = [
			'target_user_id' => 'ADD COLUMN target_user_id BIGINT(20) NULL AFTER actor_id',
			'actor_role' => "ADD COLUMN actor_role VARCHAR(50) NULL AFTER target_user_id",
			'actor_unique_id' => "ADD COLUMN actor_unique_id VARCHAR(100) NULL AFTER actor_role",
			'actor_username' => "ADD COLUMN actor_username VARCHAR(100) NULL AFTER actor_unique_id",
			'actor_name' => "ADD COLUMN actor_name VARCHAR(150) NULL AFTER actor_username",
			'target_role' => "ADD COLUMN target_role VARCHAR(50) NULL AFTER actor_name",
			'target_unique_id' => "ADD COLUMN target_unique_id VARCHAR(100) NULL AFTER target_role",
			'target_username' => "ADD COLUMN target_username VARCHAR(100) NULL AFTER target_unique_id",
			'target_name' => "ADD COLUMN target_name VARCHAR(150) NULL AFTER target_username",
			'object_type' => "ADD COLUMN object_type VARCHAR(100) NULL AFTER target_name",
			'object_id' => "ADD COLUMN object_id VARCHAR(100) NULL AFTER object_type",
			'object_label' => "ADD COLUMN object_label VARCHAR(255) NULL AFTER object_id",
			'event_label' => "ADD COLUMN event_label VARCHAR(255) NULL AFTER action",
			'metadata' => "ADD COLUMN metadata JSON NULL AFTER details",
			'ip_address' => "ADD COLUMN ip_address VARCHAR(45) NULL AFTER metadata",
			'user_agent' => "ADD COLUMN user_agent VARCHAR(255) NULL AFTER ip_address"
		];

		foreach ($columnDefinitions as $column => $definition) {
			if (!activity_log_column_exists($conn, $column)) {
				mysqli_query($conn, "ALTER TABLE activity_logs " . $definition);
			}
		}

		$indexDefinitions = [
			'idx_target_user_id' => 'ALTER TABLE activity_logs ADD KEY idx_target_user_id (target_user_id)',
			'idx_object' => 'ALTER TABLE activity_logs ADD KEY idx_object (object_type, object_id)',
			'idx_created_at' => 'ALTER TABLE activity_logs ADD KEY idx_created_at (created_at)'
		];

		foreach ($indexDefinitions as $index => $definition) {
			if (!activity_log_index_exists($conn, $index)) {
				mysqli_query($conn, $definition);
			}
		}

		$ensured = true;
	}
}

if (!function_exists('resolve_user_snapshot_for_logging')) {
	function resolve_user_snapshot_for_logging(mysqli $conn, ?int $userId): ?array
	{
		if ($userId === null || $userId <= 0) {
			return null;
		}

		static $cache = [];
		if (isset($cache[$userId])) {
			return $cache[$userId];
		}

		$stmt = mysqli_prepare($conn, 'SELECT id, username, role, name, teacher_unique_id, school, department FROM users WHERE id = ? LIMIT 1');
		if (!$stmt) {
			return null;
		}
		mysqli_stmt_bind_param($stmt, 'i', $userId);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
		$row = $result ? mysqli_fetch_assoc($result) : null;
		if ($result) {
			mysqli_free_result($result);
		}
		mysqli_stmt_close($stmt);

		if (!$row) {
			return null;
		}

		$snapshot = [
			'user_id' => (int)$row['id'],
			'username' => $row['username'],
			'role' => $row['role'],
			'name' => $row['name'],
			'teacher_unique_id' => $row['teacher_unique_id'],
			'school' => $row['school'],
			'department' => $row['department'],
			'unique_id' => $row['username'],
			'student_id' => null,
			'student_sap_id' => null,
			'student_roll_number' => null
		];

		$role = strtolower((string)$row['role']);
		if (in_array($role, ['teacher', 'program_chair'], true) && !empty($row['teacher_unique_id'])) {
			$snapshot['unique_id'] = $row['teacher_unique_id'];
		}

		if ($role === 'student') {
			$studentStmt = mysqli_prepare($conn, 'SELECT id, sap_id, roll_number FROM students WHERE sap_id = ? LIMIT 1');
			if ($studentStmt) {
				mysqli_stmt_bind_param($studentStmt, 's', $row['username']);
				mysqli_stmt_execute($studentStmt);
				$studentResult = mysqli_stmt_get_result($studentStmt);
				$studentRow = $studentResult ? mysqli_fetch_assoc($studentResult) : null;
				if ($studentResult) {
					mysqli_free_result($studentResult);
				}
				mysqli_stmt_close($studentStmt);
				if ($studentRow) {
					$snapshot['student_id'] = (int)$studentRow['id'];
					$snapshot['student_sap_id'] = $studentRow['sap_id'];
					$snapshot['student_roll_number'] = $studentRow['roll_number'];
					if (!empty($studentRow['sap_id'])) {
						$snapshot['unique_id'] = (string)$studentRow['sap_id'];
					}
				}
			}
		}

		$cache[$userId] = $snapshot;
		return $snapshot;
	}
}

if (!function_exists('normalize_activity_metadata')) {
	function normalize_activity_metadata($metadata): ?string
	{
		if ($metadata === null || $metadata === '') {
			return null;
		}
		if (is_string($metadata)) {
			return $metadata;
		}
		$json = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return $json !== false ? $json : null;
	}
}

if (!function_exists('log_activity')) {
	function log_activity(mysqli $conn, ...$args): void
	{
		$event = [];
		if (count($args) === 1 && is_array($args[0])) {
			$event = $args[0];
		} else {
			$event = [
				'target_user_id' => isset($args[0]) ? (int)$args[0] : null,
				'event_type' => isset($args[1]) ? (string)$args[1] : '',
				'description' => isset($args[2]) ? (string)$args[2] : '',
				'actor_id' => isset($args[3]) ? (int)$args[3] : (isset($args[0]) ? (int)$args[0] : null)
			];
		}

		ensure_activity_log_table($conn);

		$action = trim((string)($event['event_type'] ?? $event['action'] ?? ''));
		if ($action === '') {
			return;
		}

		$actorId = isset($event['actor_id']) && $event['actor_id'] ? (int)$event['actor_id'] : null;
		$targetUserId = null;
		foreach (['target_user_id', 'user_id'] as $key) {
			if (isset($event[$key]) && $event[$key]) {
				$targetUserId = (int)$event[$key];
				break;
			}
		}

		$actorSnapshot = resolve_user_snapshot_for_logging($conn, $actorId);
		$targetSnapshot = resolve_user_snapshot_for_logging($conn, $targetUserId);

		$actorRole = $event['actor_role'] ?? ($actorSnapshot['role'] ?? null);
		$actorUniqueId = $event['actor_unique_id'] ?? ($actorSnapshot['unique_id'] ?? null);
		$actorUsername = $event['actor_username'] ?? ($actorSnapshot['username'] ?? null);
		$actorName = $event['actor_name'] ?? ($actorSnapshot['name'] ?? null);

		$targetRole = $event['target_role'] ?? ($targetSnapshot['role'] ?? null);
		$targetUniqueId = $event['target_unique_id'] ?? ($targetSnapshot['unique_id'] ?? null);
		$targetUsername = $event['target_username'] ?? ($targetSnapshot['username'] ?? null);
		$targetName = $event['target_name'] ?? ($targetSnapshot['name'] ?? null);

		$objectType = isset($event['object_type']) ? (string)$event['object_type'] : null;
		$objectId = isset($event['object_id']) ? (string)$event['object_id'] : null;
		$objectLabel = isset($event['object_label']) ? (string)$event['object_label'] : null;

		$eventLabel = isset($event['event_label']) ? (string)$event['event_label'] : null;
		$details = isset($event['description']) ? (string)$event['description'] : ($event['details'] ?? '');

		$metadata = normalize_activity_metadata($event['metadata'] ?? null);

		$ipAddress = $event['ip_address'] ?? $event['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
		$userAgent = $event['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);

		$sql = "INSERT INTO activity_logs (
			user_id,
			target_user_id,
			actor_id,
			actor_role,
			actor_unique_id,
			actor_username,
			actor_name,
			target_role,
			target_unique_id,
			target_username,
			target_name,
			object_type,
			object_id,
			object_label,
			action,
			event_label,
			details,
			metadata,
			ip_address,
			user_agent
			) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

		$stmt = mysqli_prepare($conn, $sql);
		if (!$stmt) {
			return;
		}

		$userIdParam = $targetUserId;
		$targetUserIdParam = $targetUserId;
		$actorIdParam = $actorId;
		$actorRoleParam = $actorRole !== null ? (string)$actorRole : null;
		$actorUniqueIdParam = $actorUniqueId !== null ? (string)$actorUniqueId : null;
		$actorUsernameParam = $actorUsername !== null ? (string)$actorUsername : null;
		$actorNameParam = $actorName !== null ? (string)$actorName : null;
		$targetRoleParam = $targetRole !== null ? (string)$targetRole : null;
		$targetUniqueIdParam = $targetUniqueId !== null ? (string)$targetUniqueId : null;
		$targetUsernameParam = $targetUsername !== null ? (string)$targetUsername : null;
		$targetNameParam = $targetName !== null ? (string)$targetName : null;
		$objectTypeParam = $objectType !== null ? (string)$objectType : null;
		$objectIdParam = $objectId !== null ? (string)$objectId : null;
		$objectLabelParam = $objectLabel !== null ? (string)$objectLabel : null;
		$actionParam = $action;
		$eventLabelParam = $eventLabel !== null ? (string)$eventLabel : null;
		$detailsParam = $details !== null ? (string)$details : null;
		$metadataParam = $metadata;
		$ipParam = $ipAddress !== null ? (string)$ipAddress : null;
		$agentParam = $userAgent !== null ? (string)$userAgent : null;

		$bindTypes = 'iii' . str_repeat('s', 17);
		mysqli_stmt_bind_param(
			$stmt,
			$bindTypes,
			$userIdParam,
			$targetUserIdParam,
			$actorIdParam,
			$actorRoleParam,
			$actorUniqueIdParam,
			$actorUsernameParam,
			$actorNameParam,
			$targetRoleParam,
			$targetUniqueIdParam,
			$targetUsernameParam,
			$targetNameParam,
			$objectTypeParam,
			$objectIdParam,
			$objectLabelParam,
			$actionParam,
			$eventLabelParam,
			$detailsParam,
			$metadataParam,
			$ipParam,
			$agentParam
		);

		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
}

if (!function_exists('fetch_activity_logs')) {
	function fetch_activity_logs(mysqli $conn, int $limit = 100): array
	{
		ensure_activity_log_table($conn);
		$sql = "SELECT al.*,
					actor.role AS actor_role_resolved,
					actor.name AS actor_name_resolved,
					actor.teacher_unique_id AS actor_unique_resolved,
					target.role AS target_role_resolved,
					target.name AS target_name_resolved
				FROM activity_logs al
				LEFT JOIN users actor ON al.actor_id = actor.id
				LEFT JOIN users target ON al.target_user_id = target.id
				ORDER BY al.created_at DESC
				LIMIT ?";
		$stmt = mysqli_prepare($conn, $sql);
		if (!$stmt) {
			return [];
		}
		mysqli_stmt_bind_param($stmt, 'i', $limit);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
		$rows = [];
		if ($result) {
			while ($row = mysqli_fetch_assoc($result)) {
				$rows[] = $row;
			}
			mysqli_free_result($result);
		}
		mysqli_stmt_close($stmt);
		return $rows;
	}
}

