-- =========================================================
-- EventHall Database Schema
-- Relaciones manejadas desde PHP (sin FOREIGN KEY)
-- Todas las tablas usan el sufijo ...active como BOOLEAN.
-- Solo se definen las TABLAS; los datos de prueba se insertan
-- desde la aplicacion mediante el boton "Clean" del login.
-- =========================================================

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
-- =========================================================
CREATE TABLE tbroleadmin (
    tbroleadminid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleadminrolid INT NOT NULL UNIQUE,
    tbroleadminactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 3) tbroleclient: relacion rol <-> cliente
-- =========================================================
CREATE TABLE tbroleclient (
    tbroleclientid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleclientrolid INT NOT NULL UNIQUE,
    tbroleclientactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 4) tbroleowner: relacion rol <-> propietario
-- =========================================================
CREATE TABLE tbroleowner (
    tbroleownerid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleownerrolid INT NOT NULL UNIQUE,
    tbroleowneractive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 5) tbadmin: perfil del administrador
-- =========================================================
CREATE TABLE tbadmin (
    tbadminid INT AUTO_INCREMENT PRIMARY KEY,
    tbadminroleid INT NOT NULL,
    tbadminname VARCHAR(300) NOT NULL,
    tbadminimage VARCHAR(255),
    tbadminactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 6) tbclient: perfil del cliente
-- =========================================================
CREATE TABLE tbclient (
    tbclientid INT AUTO_INCREMENT PRIMARY KEY,
    tbclientroleid INT NOT NULL,
    tbclientname VARCHAR(300) NOT NULL,
    tbclientimage VARCHAR(255),
    tbclientlocationid INT,
    tbclientactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 7) tbowner: perfil del propietario
-- =========================================================
CREATE TABLE tbowner (
    tbownerid INT AUTO_INCREMENT PRIMARY KEY,
    tbownerroleid INT NOT NULL,
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
    tbvenueownerid INT NOT NULL,
    tbvenuelocationid INT,
    tbvenuename VARCHAR(150) NOT NULL,
    tbvenuetype VARCHAR(50),
    tbvenuecapacity INT,
    tbvenueimage VARCHAR(255),
    tbvenueactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 10) tbservice: servicios por local
-- =========================================================
CREATE TABLE tbservice (
    tbserviceid INT AUTO_INCREMENT PRIMARY KEY,
    tbservicelocalid INT NOT NULL,
    tbservicename VARCHAR(200) NOT NULL,
    tbservicetype VARCHAR(100),
    tbserviceprice DECIMAL(10,2) NOT NULL,
    tbservicestate VARCHAR(30) DEFAULT 'solicitado',
    tbserviceapprovedby INT NULL,
    tbserviceapprovedon DATETIME NULL,
    tbserviceactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 11) tbservicehistory: historial de precios de un servicio
-- =========================================================
CREATE TABLE tbservicehistory (
    tbservicehistoryid INT AUTO_INCREMENT PRIMARY KEY,
    tbservicehistoryserviceid INT NOT NULL,
    tbservicehistoryprice DECIMAL(10,2) NOT NULL,
    tbservicehistoryvalidfrom DATE NOT NULL,
    tbservicehistoryactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 12) tbpromotion: promociones vigentes por local
-- =========================================================
CREATE TABLE tbpromotion (
    tbpromotionid INT AUTO_INCREMENT PRIMARY KEY,
    tbpromotionvenueid INT NOT NULL,
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
    tbpromotionservicepromotionid INT NOT NULL,
    tbpromotionserviceserviceid INT NOT NULL,
    tbpromotionserviceactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 14) tbbooking: reservas
-- =========================================================
CREATE TABLE tbbooking (
    tbbookingid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookingclientid INT NOT NULL,
    tbbookinglocalid INT NOT NULL,
    tbbookingdate DATE NOT NULL,
    tbbookingeventtype VARCHAR(50),
    tbbookingstate VARCHAR(30) DEFAULT 'pendiente',
    tbbookingactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 15) tbbookingdetail: lineas de la reserva (carrito)
-- =========================================================
CREATE TABLE tbbookingdetail (
    tbbookingdetailid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookingdetailbookingid INT NOT NULL,
    tbbookingdetaildetailid INT NOT NULL,
    tbbookingdetailquantity INT NOT NULL DEFAULT 1,
    tbbookingdetailunitprice DECIMAL(10,2) NOT NULL,
    tbbookingdetaildiscount DECIMAL(10,2) NOT NULL DEFAULT 0,
    tbbookingdetailactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 16) tbbookingticket: comprobante de pago de una reserva
-- =========================================================
CREATE TABLE tbbookingticket (
    tbbookingticketid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookingticketbookingid INT NOT NULL,
    tbbookingticketfile VARCHAR(255) NOT NULL,
    tbbookingtickettype VARCHAR(10) NOT NULL,
    tbbookingticketstate VARCHAR(30) DEFAULT 'pendiente',
    tbbookingticketactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 17) tbpaymentmethod: metodos de pago
-- =========================================================
CREATE TABLE tbpaymentmethod (
    tbpaymentmethodid INT AUTO_INCREMENT PRIMARY KEY,
    tbpaymentmethodtype VARCHAR(50) NOT NULL,
    tbpaymentmethodactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 18) tbownerpayment: datos de cobro del propietario
-- =========================================================
CREATE TABLE tbownerpayment (
    tbownerpaymentid INT AUTO_INCREMENT PRIMARY KEY,
    tbownerpaymentownerid INT NOT NULL,
    tbownerpaymentpaymentmethodid INT NOT NULL,
    tbownerpaymentholder VARCHAR(150),
    tbownerpaymentaccount VARCHAR(100),
    tbownerpaymentinstructions VARCHAR(500),
    tbownerpaymentactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 19) tbinvoice: facturas (relacion 1:1 con reserva)
-- =========================================================
CREATE TABLE tbinvoice (
    tbinvoiceid INT AUTO_INCREMENT PRIMARY KEY,
    tbinvoicebookingid INT NOT NULL UNIQUE,
    tbinvoicepaymentmethodid INT NOT NULL,
    tbinvoicedate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbinvoicestatus VARCHAR(30) DEFAULT 'pendiente',
    tbinvoiceactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 20) tbcommissionconfig: configuracion de comision e IVA
-- =========================================================
CREATE TABLE tbcommissionconfig (
    tbcommissionconfigid INT AUTO_INCREMENT PRIMARY KEY,
    tbcommissionconfigpercentage DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    tbcommissionconfigtax DECIMAL(5,2) NOT NULL DEFAULT 13.00,
    tbcommissionconfigactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 21) tbeearning: reparticion de ganancias por reserva pagada
-- =========================================================
CREATE TABLE tbeearning (
    tbeearningid INT AUTO_INCREMENT PRIMARY KEY,
    tbeearningbookingid INT NOT NULL,
    tbeearningtotal DECIMAL(12,2) NOT NULL,
    tbeearningcommission DECIMAL(12,2) NOT NULL,
    tbeearningtax DECIMAL(12,2) NOT NULL,
    tbeearningowneramount DECIMAL(12,2) NOT NULL,
    tbeearningreviewedbyrole INT,
    tbeearningdate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbeearningactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 21b) tbbookinghistory: auditoria de modificaciones de reservas
-- =========================================================
CREATE TABLE tbbookinghistory (
    tbbookinghistoryid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookinghistorybookingid INT NOT NULL,
    tbbookinghistoryroleid INT,
    tbbookinghistoryaction VARCHAR(50) NOT NULL,
    tbbookinghistorydetail VARCHAR(500),
    tbbookinghistorydate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbbookinghistoryactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 21c) tbbookingrefund: solicitudes de reembolso (cliente -> admin)
-- =========================================================
CREATE TABLE tbbookingrefund (
    tbbookingrefundid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookingrefundbookingid INT NOT NULL,
    tbbookingrefundclientroleid INT NOT NULL,
    tbbookingrefunddetail VARCHAR(500) NOT NULL,
    tbbookingrefundstate VARCHAR(30) DEFAULT 'pendiente',
    tbbookingrefunddate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbbookingrefundactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 22) tbvenuerating: calificaciones de locales
-- =========================================================
CREATE TABLE tbvenuerating (
    tbvenueratingid INT AUTO_INCREMENT PRIMARY KEY,
    tbvenueratingvenueid INT NOT NULL,
    tbvenueratingroleid INT NOT NULL,
    tbvenueratingstars TINYINT NOT NULL,
    tbvenueratingcomment VARCHAR(500),
    tbvenueratingactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 23) tbservicerating: calificaciones de servicios
-- =========================================================
CREATE TABLE tbservicerating (
    tbserviceratingid INT AUTO_INCREMENT PRIMARY KEY,
    tbserviceratingserviceid INT NOT NULL,
    tbserviceratingroleid INT NOT NULL,
    tbserviceratingstars TINYINT NOT NULL,
    tbserviceratingcomment VARCHAR(500),
    tbserviceratingactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 24) tbnotification: notificaciones
-- =========================================================
CREATE TABLE tbnotification (
    tbnotificationid INT AUTO_INCREMENT PRIMARY KEY,
    tbnotificationroleid INT NOT NULL,
    tbnotificationmessage VARCHAR(255) NOT NULL,
    tbnotificationdate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbnotificationread BOOLEAN NOT NULL DEFAULT FALSE,
    tbnotificationactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 25) tbuserhistory: historial de acciones de usuarios
-- =========================================================
CREATE TABLE tbuserhistory (
    tbuserhistoryid INT AUTO_INCREMENT PRIMARY KEY,
    tbuserhistoryroleid INT NOT NULL,
    tbuserhistoryaction VARCHAR(50) NOT NULL,
    tbuserhistoryentity VARCHAR(50),
    tbuserhistoryentityid INT,
    tbuserhistorydate DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 26) tbownerhistory: historial de acciones del propietario
-- =========================================================
CREATE TABLE tbownerhistory (
    tbownerhistoryid INT AUTO_INCREMENT PRIMARY KEY,
    tbownerhistoryownerid INT NOT NULL,
    tbownerhistoryaction VARCHAR(50) NOT NULL,
    tbownerhistorydetail VARCHAR(500),
    tbownerhistorydate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbownerhistoryactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;
