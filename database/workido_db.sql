-- =====================================================================
-- Workido - Skilled-Worker Management Platform
-- Database Schema & Initial Seed Data
-- Database Name: workido_db
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `workido_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `workido_db`;

-- Set SQL Mode & Foreign Key Checks
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `performance_records`;
DROP TABLE IF EXISTS `wage_records`;
DROP TABLE IF EXISTS `job_assignments`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `workers`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. Users Table (Authentication & Access Control)
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NULL,
  `address` TEXT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'manager', 'staff', 'customer') NOT NULL DEFAULT 'customer',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `last_login` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Workers Table
-- ---------------------------------------------------------------------
CREATE TABLE `workers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `worker_code` VARCHAR(20) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `mobile_number` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NULL,
  `address` TEXT NOT NULL,
  `category` ENUM('Carpenter', 'Electrician', 'Plumber', 'Mason') NOT NULL,
  `skills` TEXT NOT NULL,
  `experience_years` DECIMAL(4,1) NOT NULL DEFAULT 0.0,
  `daily_wage` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `availability_status` ENUM('Available', 'Assigned', 'On Leave', 'Inactive') NOT NULL DEFAULT 'Available',
  `joining_date` DATE NOT NULL,
  `additional_details` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_worker_category` (`category`),
  INDEX `idx_worker_status` (`availability_status`),
  INDEX `idx_worker_code` (`worker_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Jobs Table
-- ---------------------------------------------------------------------
CREATE TABLE `jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `job_code` VARCHAR(20) NOT NULL UNIQUE,
  `title` VARCHAR(150) NOT NULL,
  `category` ENUM('Carpenter', 'Electrician', 'Plumber', 'Mason') NOT NULL,
  `description` TEXT NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `start_date` DATE NOT NULL,
  `expected_completion_date` DATE NOT NULL,
  `actual_completion_date` DATE NULL,
  `payment_type` ENUM('Daily Rate', 'Fixed Price') NOT NULL DEFAULT 'Daily Rate',
  `agreed_wage` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Pending', 'Assigned', 'In Progress', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `client_name` VARCHAR(100) NULL,
  `client_contact` VARCHAR(30) NULL,
  `customer_id` INT NULL,
  `notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_job_status` (`status`),
  INDEX `idx_job_category` (`category`),
  INDEX `idx_job_code` (`job_code`),
  INDEX `idx_job_customer` (`customer_id`),
  CONSTRAINT `fk_jobs_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_jobs_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Job Assignments Table (Links Workers to Jobs)
-- ---------------------------------------------------------------------
CREATE TABLE `job_assignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `job_id` INT NOT NULL,
  `worker_id` INT NOT NULL,
  `assignment_date` DATE NOT NULL,
  `assigned_by` INT NULL,
  `assignment_notes` TEXT NULL,
  `assignment_status` ENUM('Assigned', 'In Progress', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Assigned',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_assign_job` (`job_id`),
  INDEX `idx_assign_worker` (`worker_id`),
  CONSTRAINT `fk_assignment_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignment_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignment_user` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. Wage Records Table
-- ---------------------------------------------------------------------
CREATE TABLE `wage_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `voucher_no` VARCHAR(30) NOT NULL UNIQUE,
  `worker_id` INT NOT NULL,
  `job_id` INT NULL,
  `payment_type` ENUM('Daily Rate', 'Fixed Price') NOT NULL DEFAULT 'Daily Rate',
  `daily_wage` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `working_days` DECIMAL(5,1) NOT NULL DEFAULT 0.0,
  `base_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `additional_payment` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `deductions` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_payable` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('Pending', 'Paid', 'Partially Paid') NOT NULL DEFAULT 'Pending',
  `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('Cash', 'Bank Transfer', 'UPI', 'Cheque') NOT NULL DEFAULT 'Cash',
  `payment_date` DATE NULL,
  `reference_no` VARCHAR(50) NULL,
  `notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_wage_worker` (`worker_id`),
  INDEX `idx_wage_job` (`job_id`),
  INDEX `idx_wage_status` (`payment_status`),
  CONSTRAINT `fk_wage_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wage_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_wage_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Performance Records Table
-- ---------------------------------------------------------------------
CREATE TABLE `performance_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `worker_id` INT NOT NULL,
  `job_id` INT NULL,
  `rating` DECIMAL(2,1) NOT NULL DEFAULT 5.0,
  `punctuality_rating` INT NOT NULL DEFAULT 5,
  `quality_rating` INT NOT NULL DEFAULT 5,
  `safety_rating` INT NOT NULL DEFAULT 5,
  `remarks` TEXT NOT NULL,
  `admin_feedback` TEXT NULL,
  `recorded_by` INT NULL,
  `review_date` DATE NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_perf_worker` (`worker_id`),
  INDEX `idx_perf_job` (`job_id`),
  CONSTRAINT `fk_perf_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_perf_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_perf_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. Contact Messages Table (For Landing Page Inquiries)
-- ---------------------------------------------------------------------
CREATE TABLE `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `subject` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('New', 'Read', 'Replied') NOT NULL DEFAULT 'New',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- INITIAL SEED DATA
-- =====================================================================

-- 1. Default Accounts (Admin & Customer)
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `address`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'Administrator', 'admin@workido.com', '9876543210', 'Workido Headquarters, Sector 29, Gurgaon', '$2y$10$4GQKi55OmqzcVi6QdXqVg.lVoLcFF0I5PgD0x9rSMZyY/xvgTEuHS', 'admin', 'active', NOW()),
(2, 'Vikram Malhotra', 'customer@example.com', '9810112233', 'Villa 42, Palm Meadows, Gurgaon', '$2y$10$oV5vNNPNrhf/VgvC.DNJzei9ZimnozwI1YnfM1ayDFk2/3GTUqpm2', 'customer', 'active', NOW());

-- 2. Workers Seed Data (Carpenters, Electricians, Plumbers, Masons)
INSERT INTO `workers` (`id`, `worker_code`, `full_name`, `mobile_number`, `email`, `address`, `category`, `skills`, `experience_years`, `daily_wage`, `availability_status`, `joining_date`, `additional_details`) VALUES
(1, 'WRK-1001', 'Rajesh Sharma', '9876543210', 'rajesh.carpenter@example.com', '124 Timber Yard Rd, Sector 14, Delhi', 'Carpenter', 'Custom Furniture, Kitchen Cabinets, Door Framing, Wood Polishing, Roofing Framing', 8.5, 850.00, 'Assigned', '2023-01-15', 'Certified Master Carpenter with own specialized power tools and fine timber finishing experience.'),
(2, 'WRK-1002', 'Amit Kumar Verma', '9812345678', 'amit.electrician@example.com', '45 Green Park Extension, New Delhi', 'Electrician', 'Conduit Wiring, 3-Phase Circuit Panels, Fault Diagnostics, Inverter Setup, LED Installations', 6.0, 900.00, 'Assigned', '2023-03-10', 'Govt Licensed Electrical Contractor (Grade A), industrial & residential certified.'),
(3, 'WRK-1003', 'Mohammad Imran', '9890123456', 'imran.plumber@example.com', '78 Old City Bazaar, Civil Lines, Agra', 'Plumber', 'CPVC/UPVC Piping, Sanitary Ware Installation, Leak Detection, Booster Pumps, Drainage', 7.0, 800.00, 'Available', '2023-02-20', 'Specialized in high-pressure bathroom fittings and modern concealed sanitary plumbing.'),
(4, 'WRK-1004', 'Ramesh Yadav', '9765432109', 'ramesh.mason@example.com', '89 Brickworks Colony, Mathura Road, Faridabad', 'Mason', 'Brickwork, Plastering, Tile Laying, Concrete Reinforcement, Stone Cladding, Waterproofing', 10.0, 950.00, 'Assigned', '2022-11-05', 'Experienced Head Mason for commercial multi-story construction and precision leveling.'),
(5, 'WRK-1005', 'Suresh Chandra Pal', '9834567890', 'suresh.carpenter@example.com', '12 Craft Lane, Rohini, Delhi', 'Carpenter', 'Modular Wardrobes, Veneer Fixing, Antique Restoration, Window Grills, CNC Finishing', 5.0, 750.00, 'Available', '2023-06-12', 'Punctual artisan with strong attention to modern aesthetic interior woodwork.'),
(6, 'WRK-1006', 'Dinesh Singh Rawat', '9845123789', 'dinesh.electrician@example.com', '230 Vasant Kunj, South Delhi', 'Electrician', 'Smart Home Automation, Solar Inverters, DB Dressing, Industrial Transformers, Earthing', 9.0, 1100.00, 'Available', '2022-09-01', 'Expert in residential smart grid installations and energy audit compliance.'),
(7, 'WRK-1007', 'Vikram Patel', '9823456712', 'vikram.plumber@example.com', '67 Metro Vihar, Noida Sector 62', 'Plumber', 'Sewage Lines, Solar Water Heater Plumbing, Valve Replacements, Kitchen Sinks, Hydro Testing', 4.5, 700.00, 'On Leave', '2023-08-18', 'Reliable technician for domestic maintenance and rapid emergency leak repairs.'),
(8, 'WRK-1008', 'Kailash Meena', '9811223344', 'kailash.mason@example.com', '34 Shanti Nagar, Gurgaon Phase 3', 'Mason', 'RCC Casting, Granite Flooring, Foundation Footings, Boundary Walls, Texture Plaster', 12.0, 1000.00, 'Available', '2022-08-14', 'Veteran site supervisor and structural masonry specialist.');

-- 3. Jobs Seed Data
INSERT INTO `jobs` (`id`, `job_code`, `title`, `category`, `description`, `location`, `start_date`, `expected_completion_date`, `actual_completion_date`, `payment_type`, `agreed_wage`, `status`, `client_name`, `client_contact`, `customer_id`, `notes`, `created_by`, `created_at`) VALUES
(1, 'JOB-2001', 'Modular Kitchen Cabinets & Pantry', 'Carpenter', 'Fabricate and install bespoke acrylic finish modular kitchen cabinets, soft-close hinges, and cutlery trays.', 'Villa 42, Palm Meadows, Gurgaon', '2026-10-01', '2026-10-12', '2026-10-11', 'Daily Rate', 850.00, 'Completed', 'Vikram Malhotra', '9810112233', 2, 'High-end teak and marine ply finish. Client highly satisfied.', 1, '2026-09-28 10:00:00'),
(2, 'JOB-2002', 'Commercial Office 3-Phase Rewiring', 'Electrician', 'Complete internal rewiring of 3500 sq ft tech workspace including separate server room UPS circuit and MCB distribution board.', 'Tower B, Cyber City, DLF Phase 2, Gurgaon', '2026-10-15', '2026-10-25', '2026-10-24', 'Daily Rate', 900.00, 'Completed', 'Nexus Tech Solutions (Mr. Goel)', '9833445566', NULL, 'All circuits tested and certified under Indian Electrical Safety codes.', 1, '2026-10-10 11:30:00'),
(3, 'JOB-2003', 'Luxury Bathroom Sanitary Ware & CPVC Line', 'Plumber', 'Full bathroom remodel: replace old galvanized pipes with CPVC, install wall-hung commode, concealed diverter, and rain shower.', 'B-14 Panchsheel Park, New Delhi', '2026-11-01', '2026-11-06', '2026-11-05', 'Daily Rate', 800.00, 'Completed', 'Mrs. Sunita Kapoor', '9877889900', NULL, 'Pressure tested at 10 bar. Zero leakage guaranteed.', 1, '2026-10-28 14:15:00'),
(4, 'JOB-2004', 'Boundary Wall & Stone Cladding Construction', 'Mason', 'Construct 120 running feet perimeter wall using fly-ash bricks with RCC pillars, followed by natural Dholpur stone cladding.', 'Plot 88, Sector 57, Gurgaon', '2026-11-08', '2026-11-20', NULL, 'Daily Rate', 950.00, 'In Progress', 'Heritage Enclave Society', '9811992233', NULL, 'Foundation completed; brick laying in progress up to 4 feet height.', 1, '2026-11-05 09:00:00'),
(5, 'JOB-2005', 'Handcrafted Solid Teakwood Dining & Wardrobes', 'Carpenter', 'Build 8-seater dining table with chairs and 3-door master bedroom wardrobe with inner drawers and mirror paneling.', 'Flat 502, Skyrise Heights, Noida', '2026-11-10', '2026-11-22', NULL, 'Fixed Price', 12500.00, 'In Progress', 'Vikram Malhotra', '9810112233', 2, 'Wood seasoning and cutting commenced. High precision joinery.', 1, '2026-11-08 16:00:00'),
(6, 'JOB-2006', 'Warehouse Floodlight & Solar Inverter Setup', 'Electrician', 'Mount 12 industrial LED floodlights (150W each) and configure 10kVA solar hybrid inverter with battery bank.', 'Godown 4, Okhla Industrial Area Phase 1', '2026-11-12', '2026-11-18', NULL, 'Daily Rate', 1100.00, 'Assigned', 'Apex Logistics Corp', '9810102030', NULL, 'Worker briefing scheduled for tomorrow morning.', 1, '2026-11-11 12:00:00'),
(7, 'JOB-2007', 'Underground Water Sump & Pump Overhaul', 'Plumber', 'Clean underground reservoir, replace rusted foot-valves, and plumb automatic water level sensor switch.', 'Shivalik Apartments, Saket, New Delhi', '2026-11-16', '2026-11-19', NULL, 'Daily Rate', 800.00, 'Pending', 'RWA Shivalik', '9844556677', NULL, 'Pending site clearance and safety clearance from society supervisor.', 1, '2026-11-14 10:30:00');

-- 4. Job Assignments Seed Data
INSERT INTO `job_assignments` (`id`, `job_id`, `worker_id`, `assignment_date`, `assigned_by`, `assignment_notes`, `assignment_status`, `created_at`) VALUES
(1, 1, 1, '2026-10-01', 1, 'Assigned to Rajesh Sharma for master kitchen cabinetry.', 'Completed', '2026-10-01 08:00:00'),
(2, 2, 2, '2026-10-15', 1, 'Assigned to Amit Verma for commercial office cabling.', 'Completed', '2026-10-15 08:30:00'),
(3, 3, 3, '2026-11-01', 1, 'Assigned to Mohammad Imran for luxury bath fittings.', 'Completed', '2026-11-01 09:00:00'),
(4, 4, 4, '2026-11-08', 1, 'Assigned to Ramesh Yadav for brick masonry & stone cladding.', 'In Progress', '2026-11-08 08:00:00'),
(5, 5, 1, '2026-11-10', 1, 'Assigned to Rajesh Sharma for teakwood dining set.', 'In Progress', '2026-11-10 09:30:00'),
(6, 6, 2, '2026-11-12', 1, 'Assigned to Amit Verma for warehouse solar & floodlight project.', 'Assigned', '2026-11-12 10:00:00');

-- 5. Wage Records Seed Data
INSERT INTO `wage_records` (`id`, `voucher_no`, `worker_id`, `job_id`, `payment_type`, `daily_wage`, `working_days`, `base_amount`, `additional_payment`, `deductions`, `total_payable`, `payment_status`, `amount_paid`, `payment_method`, `payment_date`, `reference_no`, `notes`, `created_by`, `created_at`) VALUES
(1, 'WAG-3001', 1, 1, 'Daily Rate', 850.00, 10.0, 8500.00, 500.00, 0.00, 9000.00, 'Paid', 9000.00, 'Bank Transfer', '2026-10-12', 'NEFT-883920194', 'Full settlement for Modular Kitchen project. Includes Rs.500 bonus for early completion.', 1, '2026-10-12 15:00:00'),
(2, 'WAG-3002', 2, 2, 'Daily Rate', 900.00, 9.0, 8100.00, 400.00, 200.00, 8300.00, 'Paid', 8300.00, 'UPI', '2026-10-25', 'UPI-TXN-90231920', 'Settled for commercial office rewiring. Additional payment for overtime; Rs.200 safety gear advance deducted.', 1, '2026-10-25 17:30:00'),
(3, 'WAG-3003', 3, 3, 'Daily Rate', 800.00, 5.0, 4000.00, 200.00, 0.00, 4200.00, 'Paid', 4200.00, 'Cash', '2026-11-06', 'CASH-REC-0192', 'Settled in cash on job completion for bathroom remodeling. Extra tip from client included as additional payment.', 1, '2026-11-06 18:00:00'),
(4, 'WAG-3004', 4, 4, 'Daily Rate', 950.00, 6.0, 5700.00, 0.00, 500.00, 5200.00, 'Partially Paid', 3000.00, 'Cash', '2026-11-14', 'CASH-ADV-0044', 'Mid-job partial advance for 6 days of boundary wall work. Rs.500 tool sharpening deduction.', 1, '2026-11-14 16:45:00'),
(5, 'WAG-3005', 5, NULL, 'Daily Rate', 750.00, 4.0, 3000.00, 0.00, 0.00, 3000.00, 'Pending', 0.00, 'Bank Transfer', NULL, NULL, 'Ad-hoc workshop carpentry maintenance wage invoice. Pending supervisor approval.', 1, '2026-11-14 11:00:00');

-- 6. Performance Records Seed Data
INSERT INTO `performance_records` (`id`, `worker_id`, `job_id`, `rating`, `punctuality_rating`, `quality_rating`, `safety_rating`, `remarks`, `admin_feedback`, `recorded_by`, `review_date`, `created_at`) VALUES
(1, 1, 1, 4.9, 5, 5, 5, 'Exceptional craftsmanship on the acrylic modular kitchen. Finished one day ahead of schedule with zero material wastage.', 'Highly reliable master carpenter. Strongly recommended for luxury residential projects.', 1, '2026-10-12', '2026-10-12 16:00:00'),
(2, 2, 2, 4.8, 5, 5, 4, 'Very knowledgeable regarding industrial 3-phase wiring and load distribution. Clear labeling on all MCB panels.', 'Excellent safety protocols and teamwork.', 1, '2026-10-25', '2026-10-25 18:00:00'),
(3, 3, 3, 4.7, 4, 5, 5, 'Flawless sanitary installations and zero leakage on hydraulic pressure test. Neatly tidied up after work.', 'Great customer interaction skills and prompt execution.', 1, '2026-11-06', '2026-11-06 18:30:00'),
(4, 4, 4, 4.5, 4, 4, 5, 'Consistently delivers true vertical alignments and uniform mortar joints. Maintains strict safety helmet and boots protocol on site.', 'Dependable head mason. Keep up the high standard.', 1, '2026-11-14', '2026-11-14 17:00:00');

-- 7. Contact Messages Seed Data
INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 'Vivek Malhotra', 'vivek.m@gmail.com', '9810112233', 'Need skilled carpenters for home renovation', 'Hello Workido team, I am renovating my 4BHK apartment in South Delhi and require 2 skilled carpenters for bespoke woodwork. Please contact me with availability.', 'Read', '2026-11-12 14:00:00'),
(2, 'Shalini Nair', 'shalini.nair@hotmail.com', '9820334455', 'Inquiry regarding commercial electrical maintenance contract', 'We are looking for licensed electricians for bi-weekly maintenance of our retail stores across Gurgaon. Kindly share the quotation and worker credentials.', 'New', '2026-11-14 18:20:00');
