CREATE DATABASE IF NOT EXISTS pg_estetica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pg_estetica;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS payment_events, payments, finance_transactions, before_after_photos, procedure_history, anamnesis, client_packages, package_services, packages, schedule_blocks, business_hours, appointments, clients, services, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','recepcao','profissional') NOT NULL DEFAULT 'admin',
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE services (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 category VARCHAR(80) NOT NULL,
 description TEXT NULL,
 price DECIMAL(10,2) NOT NULL DEFAULT 0,
 duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE clients (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 email VARCHAR(150) NULL,
 birth_date DATE NULL,
 cpf VARCHAR(20) NULL,
 address VARCHAR(255) NULL,
 notes TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_clients_phone(phone), INDEX idx_clients_email(email)
) ENGINE=InnoDB;

CREATE TABLE business_hours (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 weekday TINYINT UNSIGNED NOT NULL,
 start_time TIME NOT NULL,
 end_time TIME NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 INDEX idx_business_weekday(weekday)
) ENGINE=InnoDB;

CREATE TABLE schedule_blocks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 block_date DATE NOT NULL,
 start_time TIME NOT NULL,
 end_time TIME NOT NULL,
 reason VARCHAR(180) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_blocks_date(block_date)
) ENGINE=InnoDB;

CREATE TABLE appointments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 client_id BIGINT UNSIGNED NOT NULL,
 service_id INT UNSIGNED NOT NULL,
 appointment_date DATE NOT NULL,
 appointment_time TIME NOT NULL,
 notes TEXT NULL,
 status ENUM('pendente','confirmado','concluido','cancelado','nao_compareceu') NOT NULL DEFAULT 'pendente',
 confirmation_token VARCHAR(80) NULL UNIQUE,
 confirmed_at DATETIME NULL,
 reminder_sent_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_appointments_client FOREIGN KEY(client_id) REFERENCES clients(id),
 CONSTRAINT fk_appointments_service FOREIGN KEY(service_id) REFERENCES services(id),
 INDEX idx_appointments_date(appointment_date,appointment_time), INDEX idx_appointments_client(client_id)
) ENGINE=InnoDB;

CREATE TABLE anamnesis (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 client_id BIGINT UNSIGNED NOT NULL,
 allergies TEXT NULL,
 medications TEXT NULL,
 medical_conditions TEXT NULL,
 surgeries TEXT NULL,
 pregnancy TINYINT(1) NOT NULL DEFAULT 0,
 pacemaker TINYINT(1) NOT NULL DEFAULT 0,
 skincare_routine TEXT NULL,
 goals TEXT NULL,
 observations TEXT NULL,
 consent TINYINT(1) NOT NULL DEFAULT 0,
 signed_at DATETIME NULL,
 updated_by INT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_anamnesis_client FOREIGN KEY(client_id) REFERENCES clients(id),
 CONSTRAINT fk_anamnesis_user FOREIGN KEY(updated_by) REFERENCES users(id),
 UNIQUE KEY uq_anamnesis_client(client_id)
) ENGINE=InnoDB;

CREATE TABLE procedure_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 client_id BIGINT UNSIGNED NOT NULL,
 appointment_id BIGINT UNSIGNED NULL,
 service_id INT UNSIGNED NOT NULL,
 performed_at DATETIME NOT NULL,
 professional_name VARCHAR(120) NULL,
 products_used TEXT NULL,
 parameters_used TEXT NULL,
 observations TEXT NULL,
 next_recommendation TEXT NULL,
 created_by INT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_history_client FOREIGN KEY(client_id) REFERENCES clients(id),
 CONSTRAINT fk_history_appointment FOREIGN KEY(appointment_id) REFERENCES appointments(id),
 CONSTRAINT fk_history_service FOREIGN KEY(service_id) REFERENCES services(id),
 CONSTRAINT fk_history_user FOREIGN KEY(created_by) REFERENCES users(id),
 INDEX idx_history_client(client_id,performed_at)
) ENGINE=InnoDB;

CREATE TABLE before_after_photos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 client_id BIGINT UNSIGNED NOT NULL,
 procedure_id BIGINT UNSIGNED NULL,
 photo_type ENUM('antes','depois') NOT NULL,
 file_path VARCHAR(255) NOT NULL,
 caption VARCHAR(180) NULL,
 consent_publish TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_photos_client FOREIGN KEY(client_id) REFERENCES clients(id),
 CONSTRAINT fk_photos_procedure FOREIGN KEY(procedure_id) REFERENCES procedure_history(id),
 INDEX idx_photos_client(client_id)
) ENGINE=InnoDB;

CREATE TABLE packages (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(140) NOT NULL,
 description TEXT NULL,
 price DECIMAL(10,2) NOT NULL,
 validity_days SMALLINT UNSIGNED NOT NULL DEFAULT 90,
 featured TINYINT(1) NOT NULL DEFAULT 0,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE package_services (
 package_id INT UNSIGNED NOT NULL,
 service_id INT UNSIGNED NOT NULL,
 quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
 PRIMARY KEY(package_id,service_id),
 CONSTRAINT fk_ps_package FOREIGN KEY(package_id) REFERENCES packages(id) ON DELETE CASCADE,
 CONSTRAINT fk_ps_service FOREIGN KEY(service_id) REFERENCES services(id)
) ENGINE=InnoDB;

CREATE TABLE client_packages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 client_id BIGINT UNSIGNED NOT NULL,
 package_id INT UNSIGNED NOT NULL,
 purchased_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 expires_at DATE NULL,
 status ENUM('pendente','ativo','consumido','expirado','cancelado') NOT NULL DEFAULT 'pendente',
 sessions_json JSON NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_cp_client FOREIGN KEY(client_id) REFERENCES clients(id),
 CONSTRAINT fk_cp_package FOREIGN KEY(package_id) REFERENCES packages(id)
) ENGINE=InnoDB;

CREATE TABLE payments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 client_id BIGINT UNSIGNED NULL,
 appointment_id BIGINT UNSIGNED NULL,
 client_package_id BIGINT UNSIGNED NULL,
 provider ENUM('mercado_pago','dinheiro','pix_manual','cartao_manual','outro') NOT NULL DEFAULT 'mercado_pago',
 description VARCHAR(180) NOT NULL,
 amount DECIMAL(10,2) NOT NULL,
 status ENUM('criado','pendente','aprovado','rejeitado','cancelado','reembolsado') NOT NULL DEFAULT 'criado',
 external_reference VARCHAR(120) NULL,
 preference_id VARCHAR(160) NULL,
 provider_payment_id VARCHAR(160) NULL,
 checkout_url TEXT NULL,
 paid_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_payments_client FOREIGN KEY(client_id) REFERENCES clients(id),
 CONSTRAINT fk_payments_appointment FOREIGN KEY(appointment_id) REFERENCES appointments(id),
 CONSTRAINT fk_payments_client_package FOREIGN KEY(client_package_id) REFERENCES client_packages(id),
 INDEX idx_payments_provider(provider_payment_id), INDEX idx_payments_status(status)
) ENGINE=InnoDB;

CREATE TABLE payment_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 payment_id BIGINT UNSIGNED NULL,
 provider_event_id VARCHAR(160) NULL,
 event_type VARCHAR(100) NULL,
 payload JSON NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_payment_events_payment FOREIGN KEY(payment_id) REFERENCES payments(id)
) ENGINE=InnoDB;

CREATE TABLE finance_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 client_id BIGINT UNSIGNED NULL,
 payment_id BIGINT UNSIGNED NULL,
 type ENUM('receita','despesa') NOT NULL,
 category VARCHAR(80) NOT NULL,
 description VARCHAR(180) NOT NULL,
 amount DECIMAL(10,2) NOT NULL,
 due_date DATE NULL,
 paid_date DATE NULL,
 status ENUM('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
 payment_method VARCHAR(80) NULL,
 notes TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_finance_client FOREIGN KEY(client_id) REFERENCES clients(id),
 CONSTRAINT fk_finance_payment FOREIGN KEY(payment_id) REFERENCES payments(id),
 INDEX idx_finance_dates(due_date,paid_date), INDEX idx_finance_status(status)
) ENGINE=InnoDB;

INSERT INTO users(name,email,password_hash,role) VALUES ('Administrador','admin@pgestetica.local','$2y$12$gDa6BFwgcrvvOJ7hSTsJrewX6QebiXCOGbDLiE/aii/K4KKRMT1yO','admin');
INSERT INTO services(name,category,description,price,duration_minutes) VALUES
('Limpeza de Pele','Estética Facial','Higienização, esfoliação e cuidados para revitalização da pele.',120.00,60),
('Drenagem Linfática','Massoterapia','Técnica manual suave voltada ao relaxamento e bem-estar corporal.',140.00,60),
('Massagem Relaxante','Massoterapia','Sessão para aliviar tensões e proporcionar uma experiência profunda de relaxamento.',130.00,60),
('Hidratação Facial','Estética Facial','Protocolo de hidratação para melhorar maciez e viço da pele.',110.00,50),
('Modeladora Corporal','Estética Corporal','Massagem com manobras vigorosas para cuidado estético corporal.',150.00,60),
('Spa dos Pés','Bem-estar','Cuidado relaxante para pés cansados com foco em conforto e descanso.',90.00,40);
INSERT INTO business_hours(weekday,start_time,end_time) VALUES
(1,'09:00','18:00'),(2,'09:00','18:00'),(3,'09:00','18:00'),(4,'09:00','18:00'),(5,'09:00','18:00'),(6,'09:00','14:00');
INSERT INTO packages(name,description,price,validity_days,featured) VALUES
('Ritual Renovar','3 sessões de limpeza de pele + 1 hidratação facial.',399.00,120,1),
('Corpo Leve','5 sessões de drenagem linfática.',620.00,120,1),
('Momento Relax','3 massagens relaxantes.',349.00,90,0);
INSERT INTO package_services(package_id,service_id,quantity) VALUES (1,1,3),(1,4,1),(2,2,5),(3,3,3);
