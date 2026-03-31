-- Run this on an existing ICA Tracker database to enable System Admin role.

ALTER TABLE users
  MODIFY role ENUM('teacher','program_chair','admin','system_admin','student') NOT NULL;

INSERT INTO users (id, username, password, role, email, name, teacher_unique_id, school, status, department)
VALUES (4000888, '4000888', '751cb3f4aa17c36186f4856c8982bf27', 'system_admin', 'system.admin@nmims.edu', 'System Administrator', '4000888', 'STME', 'active', NULL)
ON DUPLICATE KEY UPDATE
  username = VALUES(username),
  password = VALUES(password),
  role = VALUES(role),
  email = VALUES(email),
  name = VALUES(name),
  teacher_unique_id = VALUES(teacher_unique_id),
  school = VALUES(school),
  status = VALUES(status),
  department = VALUES(department);
