-- =========================================================
-- MIGRACIÓN: módulo de crédito y cuentas por cobrar/pagar
-- ---------------------------------------------------------
-- Crea las tablas nuevas del módulo P5 sobre una base ya
-- instalada, sin tocar los datos existentes.
--
-- Ejecutar una sola vez sobre bases existentes. En instalaciones
-- nuevas NO es necesario (dbeventhall.sql ya las incluye).
-- =========================================================

USE dbeventhall;

CREATE TABLE IF NOT EXISTS tbcreditconfig (
    tbcreditconfigid INT AUTO_INCREMENT PRIMARY KEY,
    tbownerid INT NOT NULL,
    tbcreditconfigenabled BOOLEAN NOT NULL DEFAULT FALSE,
    tbcreditconfigdays INT NOT NULL DEFAULT 30,
    tbcreditconfigfrequentmin INT NOT NULL DEFAULT 3,
    tbcreditconfigactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tbcredit (
    tbcreditid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookingid INT NOT NULL,
    tbclientid INT NOT NULL,
    tbownerid INT NOT NULL,
    tbcredittotal DECIMAL(12,2) NOT NULL,
    tbcreditbalance DECIMAL(12,2) NOT NULL,
    tbcreditduedate DATE NOT NULL,
    tbcreditstate VARCHAR(30) DEFAULT 'pendiente',
    tbcreditdate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbcreditactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tbcreditpayment (
    tbcreditpaymentid INT AUTO_INCREMENT PRIMARY KEY,
    tbcreditid INT NOT NULL,
    tbcreditpaymentamount DECIMAL(12,2) NOT NULL,
    tbcreditpaymentnote VARCHAR(255),
    tbroleid INT,
    tbcreditpaymentdate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbcreditpaymentactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;
