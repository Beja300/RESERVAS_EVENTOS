-- ============================================================================
-- dbeventhall.sql — ESQUEMA DE EVENTHALL
-- ----------------------------------------------------------------------------
-- NOTA IMPORTANTE SOBRE INTEGRIDAD REFERENCIAL (decisión de diseño):
--
-- Este esquema NO declara constraints FOREIGN KEY a nivel de BD. La
-- integridad referencial (que un tbbooking.tbvenueid exista, que al
-- desactivar un rol se desactiven sus perfiles, etc.) se valida y
-- mantiene en la capa de aplicación (Servicios + Repositorios con
-- sentencias preparadas PDO), aprovechando el patrón de borrado lógico
-- (columna `*active` BOOLEAN) en lugar de ON DELETE CASCADE/RESTRICT.
--
-- CONTRASTE CON LA REVISIÓN (Sprint 65): la rúbrica marcó el criterio de
-- Base de datos como "0 FK, 0 relaciones", pero una versión previa de este
-- script (ver DataBase/Backup/) SÍ declaraba FREN KEY con ON DELETE
-- CASCADE y ON DELETE RESTRICT (p. ej. `FOREIGN KEY (tbroleadminfk)
-- REFERENCES tbrole(tbrolepk) ON DELETE CASCADE`). Ambos enfoques valen:
-- las FK a nivel BD, hoy ausentes, se sustituyen por validaciones de
-- Service → Repository con sentencias preparadas, documentadas en
-- Documentation/IdentidadesYReglas.md. Si se requiere integridad a nivel
-- SGBD, restaurar las FK del backup exige además ordenar los DELETE de
-- cleanDemo/cleanTestData y reescribir seed_test_data.sql respetando
-- padre → hijo.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS dbeventhall;

USE dbeventhall;

-- =========================================================
-- 1) tbrole: identidad base de acceso (login/permisos)
-- =========================================================
CREATE TABLE tbrole (
    tbroleid INT AUTO_INCREMENT PRIMARY KEY,
    tbrolename VARCHAR(300) NOT NULL,
    tbroleemail VARCHAR(300) NOT NULL UNIQUE,
    tbrolepassword VARCHAR(300) NOT NULL,
    tbrolephone VARCHAR(25),
    tbroleactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 2) tbroleadmin: relacion rol <-> administrador
--    (junction: id propio + idRol + idAdmin + active)
-- =========================================================
CREATE TABLE tbroleadmin (
    tbroleadminid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleid INT NOT NULL UNIQUE,
    tbadminid INT NOT NULL,
    tbroleadminactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 3) tbroleclient: relacion rol <-> cliente
--    (junction: id propio + idRol + idClient + active)
-- =========================================================
CREATE TABLE tbroleclient (
    tbroleclientid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleid INT NOT NULL UNIQUE,
    tbclientid INT NOT NULL,
    tbroleclientactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 4) tbroleowner: relacion rol <-> propietario
--    (junction: id propio + idRol + idOwner + active)
-- =========================================================
CREATE TABLE tbroleowner (
    tbroleownerid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleid INT NOT NULL UNIQUE,
    tbownerid INT NOT NULL,
    tbroleowneractive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 5) tbadmin: perfil del administrador
-- =========================================================
CREATE TABLE tbadmin (
    tbadminid INT AUTO_INCREMENT PRIMARY KEY,
    tbadminname VARCHAR(300) NOT NULL,
    tbadminimage VARCHAR(255),
    tbadminactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 6) tbclient: perfil del cliente
-- =========================================================
CREATE TABLE tbclient (
    tbclientid INT AUTO_INCREMENT PRIMARY KEY,
    tbclientname VARCHAR(300) NOT NULL,
    tbclientimage VARCHAR(255),
    tblocationid INT,
    tbclientactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 7) tbowner: perfil del propietario
-- =========================================================
CREATE TABLE tbowner (
    tbownerid INT AUTO_INCREMENT PRIMARY KEY,
    tbownerfirstname VARCHAR(300) NOT NULL,
    tbownerlastname VARCHAR(250),
    tbowneralias VARCHAR(100),
    tbowneridentificationnumber VARCHAR(30) UNIQUE,
    tbownerimage VARCHAR(255),
    tbowneractive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 8) tblocation: ubicaciones (provincia/canton/distrito/pueblo/desc)
-- =========================================================
CREATE TABLE tblocation (
    tblocationid INT AUTO_INCREMENT PRIMARY KEY,
    tblocationprovince VARCHAR(60) NOT NULL,
    tblocationcanton VARCHAR(60) NOT NULL,
    tblocationdistrict VARCHAR(60) NOT NULL,
    tblocationtown VARCHAR(100),
    tblocationdescription VARCHAR(300)
) ENGINE=InnoDB;

-- =========================================================
-- 9) tbvenue: locales / negocios del propietario
-- =========================================================
CREATE TABLE tbvenue (
    tbvenueid INT AUTO_INCREMENT PRIMARY KEY,
    tbownerid INT NOT NULL,
    tblocationid INT,
    tbvenuename VARCHAR(150) NOT NULL,
    tbvenuetype VARCHAR(50),
    tbvenuecapacity INT,
    tbvenueprice DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tbvenueimage VARCHAR(255),
    tbvenueactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 10) tbservice: servicios por local
-- =========================================================
CREATE TABLE tbservice (
    tbserviceid INT AUTO_INCREMENT PRIMARY KEY,
    tbvenueid INT NOT NULL,
    tbservicename VARCHAR(200) NOT NULL,
    tbservicetype VARCHAR(100),
    tbserviceprice DECIMAL(10,2) NOT NULL,
    tbservicestate VARCHAR(30) DEFAULT 'solicitado',
    tbroleid INT NULL,
    tbserviceapprovedon DATETIME NULL,
    tbserviceactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 11) tbservicehistory: historial de precios de un servicio
-- =========================================================
CREATE TABLE tbservicehistory (
    tbservicehistoryid INT AUTO_INCREMENT PRIMARY KEY,
    tbserviceid INT NOT NULL,
    tbservicehistoryprice DECIMAL(10,2) NOT NULL,
    tbservicehistoryvalidfrom DATE NOT NULL,
    tbservicehistoryactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 12) tbpromotion: promociones vigentes por local
-- =========================================================
CREATE TABLE tbpromotion (
    tbpromotionid INT AUTO_INCREMENT PRIMARY KEY,
    tbvenueid INT NOT NULL,
    tbpromotiondescription VARCHAR(500),
    tbpromotionlabel VARCHAR(100),
    tbpromotionstart DATE,
    tbpromotionend DATE,
    tbpromotionminservices INT DEFAULT 1,
    tbpromotionactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 13) tbpromotionservice: servicios incluidos en una promocion
-- =========================================================
CREATE TABLE tbpromotionservice (
    tbpromotionserviceid INT AUTO_INCREMENT PRIMARY KEY,
    tbpromotionid INT NOT NULL,
    tbserviceid INT NOT NULL,
    tbpromotionserviceactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 14) tbbooking: reservas
-- =========================================================
CREATE TABLE tbbooking (
    tbbookingid INT AUTO_INCREMENT PRIMARY KEY,
    tbclientid INT NOT NULL,
    tbvenueid INT NOT NULL,
    tbbookingdate DATE NOT NULL,
    tbbookingenddate DATE,
    tbbookingeventtype VARCHAR(50),
    tbbookingeventdetail VARCHAR(255),
    tbbookingstate VARCHAR(30) DEFAULT 'pendiente',
    tbbookingactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 15) tbdetail: linea del detalle (local/servicio, cants, precios)
--     tbserviceid -> servicio (nullable si es renta del local)
--     tbvenueid   -> local (nullable si es un servicio)
-- =========================================================
CREATE TABLE tbdetail (
    tbdetailid INT AUTO_INCREMENT PRIMARY KEY,
    tbserviceid INT,
    tbvenueid INT,
    tbdetailquantity INT NOT NULL DEFAULT 1,
    tbdetailunitprice DECIMAL(10,2) NOT NULL,
    tbdetaildiscount DECIMAL(10,2) NOT NULL DEFAULT 0,
    tbdetailactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 16) tbbookingdetail: relacion reserva <-> detalle (junction pura)
-- =========================================================
CREATE TABLE tbbookingdetail (
    tbbookingdetailid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookingid INT NOT NULL,
    tbdetailid INT NOT NULL,
    tbbookingdetailactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 17) tbbookingticket: comprobante de pago de una reserva
-- =========================================================
CREATE TABLE tbbookingticket (
    tbbookingticketid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookingid INT NOT NULL,
    tbbookingticketfile VARCHAR(255) NOT NULL,
    tbbookingtickettype VARCHAR(10) NOT NULL,
    tbpaymentmethodid INT,
    tbbookingticketstate VARCHAR(30) DEFAULT 'pendiente',
    tbbookingticketactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 18) tbpaymentmethod: metodos de pago
-- =========================================================
CREATE TABLE tbpaymentmethod (
    tbpaymentmethodid INT AUTO_INCREMENT PRIMARY KEY,
    tbpaymentmethodtype VARCHAR(50) NOT NULL,
    tbpaymentmethodactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 19) tbownerpayment: datos de cobro del propietario
-- =========================================================
CREATE TABLE tbownerpayment (
    tbownerpaymentid INT AUTO_INCREMENT PRIMARY KEY,
    tbownerid INT NOT NULL,
    tbpaymentmethodid INT NOT NULL,
    tbownerpaymentholder VARCHAR(150),
    tbownerpaymentaccount VARCHAR(100),
    tbownerpaymentinstructions VARCHAR(500),
    tbownerpaymentactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 20) tbinvoice: facturas (relacion 1:1 con reserva)
-- =========================================================
CREATE TABLE tbinvoice (
    tbinvoiceid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookingid INT NOT NULL UNIQUE,
    tbpaymentmethodid INT NOT NULL,
    tbinvoicedate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbinvoicestatus VARCHAR(30) DEFAULT 'pendiente',
    tbinvoiceactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 21) tbcommissionconfig: configuracion de comision e IVA
-- =========================================================
CREATE TABLE tbcommissionconfig (
    tbcommissionconfigid INT AUTO_INCREMENT PRIMARY KEY,
    tbcommissionconfigpercentage DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    tbcommissionconfigtax DECIMAL(5,2) NOT NULL DEFAULT 13.00,
    tbcommissionconfigactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 22) tbeearning: reparticion de ganancias por reserva pagada
-- =========================================================
CREATE TABLE tbeearning (
    tbeearningid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookingid INT NOT NULL,
    tbeearningtotal DECIMAL(12,2) NOT NULL,
    tbeearningcommission DECIMAL(12,2) NOT NULL,
    tbeearningtax DECIMAL(12,2) NOT NULL,
    tbeearningowneramount DECIMAL(12,2) NOT NULL,
    tbroleid INT,
    tbeearningdate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbeearningactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 22b) tbbookinghistory: auditoria de modificaciones de reservas
--      (quién lo modificó = tbroleid -> tbrole.tbroleid)
-- =========================================================
CREATE TABLE tbbookinghistory (
    tbbookinghistoryid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookingid INT NOT NULL,
    tbroleid INT,
    tbbookinghistoryaction VARCHAR(50) NOT NULL,
    tbbookinghistorydetail VARCHAR(500),
    tbbookinghistorydate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbbookinghistoryactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 22c) tbbookingrefund: solicitudes de reembolso (cliente -> admin)
-- =========================================================
CREATE TABLE tbbookingrefund (
    tbbookingrefundid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookingid INT NOT NULL,
    tbroleid INT NOT NULL,
    tbbookingrefunddetail VARCHAR(500) NOT NULL,
    tbbookingrefundstate VARCHAR(30) DEFAULT 'pendiente',
    tbbookingrefunddate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbbookingrefundactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 23) tbvenuerating: calificaciones de locales
-- =========================================================
CREATE TABLE tbvenuerating (
    tbvenueratingid INT AUTO_INCREMENT PRIMARY KEY,
    tbvenueid INT NOT NULL,
    tbroleid INT NOT NULL,
    tbvenueratingstars TINYINT NOT NULL,
    tbvenueratingcomment VARCHAR(500),
    tbvenueratingactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 24) tbservicerating: calificaciones de servicios
-- =========================================================
CREATE TABLE tbservicerating (
    tbserviceratingid INT AUTO_INCREMENT PRIMARY KEY,
    tbserviceid INT NOT NULL,
    tbroleid INT NOT NULL,
    tbserviceratingstars TINYINT NOT NULL,
    tbserviceratingcomment VARCHAR(500),
    tbserviceratingactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 25) tbnotification: notificaciones
-- =========================================================
CREATE TABLE tbnotification (
    tbnotificationid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleid INT NOT NULL,
    tbnotificationmessage VARCHAR(255) NOT NULL,
    tbnotificationlink VARCHAR(255) NULL DEFAULT NULL,
    tbnotificationdate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbnotificationread BOOLEAN NOT NULL DEFAULT FALSE,
    tbnotificationactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 26) tbuserhistory: historial de acciones de usuarios
-- =========================================================
CREATE TABLE tbuserhistory (
    tbuserhistoryid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleid INT NOT NULL,
    tbuserhistoryaction VARCHAR(50) NOT NULL,
    tbuserhistoryentity VARCHAR(50),
    tbuserhistoryentityid INT,
    tbuserhistorydate DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 27) tbownerhistory: historial de acciones del propietario
-- =========================================================
CREATE TABLE tbownerhistory (
    tbownerhistoryid INT AUTO_INCREMENT PRIMARY KEY,
    tbownerid INT NOT NULL,
    tbownerhistoryaction VARCHAR(50) NOT NULL,
    tbownerhistorydetail VARCHAR(500),
    tbownerhistorydate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbownerhistoryactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 28) Mini-tablas históricas de credenciales de la identidad
--     Rol (tbrole). No llevan columna "active": son de solo
--     registro (append-only) para perpetuar el movimiento y
--     validar ataques informáticos sin sobrecargar tbrole.
--     La contraseña siempre se guarda HASEADA (bcrypt).
-- =========================================================

-- 28a) histórico de contraseñas: permite impedir la reutilización
--      de las últimas N contraseñas y detectar cambios frecuentes.
CREATE TABLE tbrolpasswordhistorical (
    tbrolpasswordhistoricalid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleid INT NOT NULL,
    tbrolpasswordhistoricalactualpassword VARCHAR(300) NOT NULL,
    tbrolpasswordhistoricalnewpassword VARCHAR(300) NOT NULL,
    tbrolpasswordhistoricaldate DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 28b) histórico de teléfonos: "advertencia si cambia demasiado"
--      de teléfono (frecuencia anómala = posible ataque).
CREATE TABLE tbrolphonehistorical (
    tbrolphonehistoricalid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleid INT NOT NULL,
    tbrolphonehistoricalactualphone VARCHAR(25),
    tbrolphonehistoricalnewphone VARCHAR(25) NOT NULL,
    tbrolphonehistoricaldate DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 28c) histórico de correos: cambios del email de acceso.
CREATE TABLE tbrolemailhistorical (
    tbrolemailhistoricalid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleid INT NOT NULL,
    tbrolemailhistoricalactualemail VARCHAR(300),
    tbrolemailhistoricalnewemail VARCHAR(300) NOT NULL,
    tbrolemailhistoricaldate DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
