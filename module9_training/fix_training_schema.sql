-- Fix Module 9 training schema in PostgreSQL
-- Run this script once in psql or PgAdmin against the HR database.

BEGIN;

-- Ensure employee names exist for lookups
ALTER TABLE employees ADD COLUMN IF NOT EXISTS first_name varchar;
ALTER TABLE employees ADD COLUMN IF NOT EXISTS last_name varchar;

-- Ensure training catalog has all required columns
ALTER TABLE training_catalog ADD COLUMN IF NOT EXISTS course_name varchar;
ALTER TABLE training_catalog ADD COLUMN IF NOT EXISTS category varchar;
ALTER TABLE training_catalog ADD COLUMN IF NOT EXISTS venue_location varchar;
ALTER TABLE training_catalog ADD COLUMN IF NOT EXISTS trainer_provider varchar;
ALTER TABLE training_catalog ADD COLUMN IF NOT EXISTS start_time timestamp;
ALTER TABLE training_catalog ADD COLUMN IF NOT EXISTS end_time timestamp;
ALTER TABLE training_catalog ADD COLUMN IF NOT EXISTS description text;
ALTER TABLE training_catalog ADD COLUMN IF NOT EXISTS department varchar;
ALTER TABLE training_catalog ADD COLUMN IF NOT EXISTS score_tracking int DEFAULT 0;

-- Ensure training enrollments has all required columns
ALTER TABLE training_enrollments ADD COLUMN IF NOT EXISTS training_id int;
ALTER TABLE training_enrollments ADD COLUMN IF NOT EXISTS employee_id int;
ALTER TABLE training_enrollments ADD COLUMN IF NOT EXISTS enrollment_date timestamp DEFAULT now();
ALTER TABLE training_enrollments ADD COLUMN IF NOT EXISTS completion_status varchar;
ALTER TABLE training_enrollments ADD COLUMN IF NOT EXISTS score_result varchar;
ALTER TABLE training_enrollments ADD COLUMN IF NOT EXISTS nomination_type varchar;

-- Ensure serial/sequence defaults on primary IDs
CREATE SEQUENCE IF NOT EXISTS training_catalog_training_id_seq;
SELECT setval('training_catalog_training_id_seq', COALESCE((SELECT MAX(training_id) FROM training_catalog), 0) + 1, false);
ALTER TABLE training_catalog ALTER COLUMN training_id SET DEFAULT nextval('training_catalog_training_id_seq');

CREATE SEQUENCE IF NOT EXISTS training_enrollments_enrollment_id_seq;
SELECT setval('training_enrollments_enrollment_id_seq', COALESCE((SELECT MAX(enrollment_id) FROM training_enrollments), 0) + 1, false);
ALTER TABLE training_enrollments ALTER COLUMN enrollment_id SET DEFAULT nextval('training_enrollments_enrollment_id_seq');

-- Ensure primary keys exist
ALTER TABLE training_catalog ADD CONSTRAINT IF NOT EXISTS training_catalog_pkey PRIMARY KEY (training_id);
ALTER TABLE training_enrollments ADD CONSTRAINT IF NOT EXISTS training_enrollments_pkey PRIMARY KEY (enrollment_id);

-- Ensure foreign keys exist for relational integrity
ALTER TABLE training_enrollments ADD CONSTRAINT IF NOT EXISTS training_enrollments_training_fk FOREIGN KEY (training_id) REFERENCES training_catalog(training_id);
ALTER TABLE training_enrollments ADD CONSTRAINT IF NOT EXISTS training_enrollments_employee_fk FOREIGN KEY (employee_id) REFERENCES employees(employee_id);

COMMIT;
