-- =========================================================
-- Paradigms Project — Database Schema
-- Relaciones manejadas desde PHP
-- Sin FOREIGN KEY
-- =========================================================

CREATE DATABASE IF NOT EXISTS dbeventhall;

USE dbeventhall;


-- =========================================================
-- TABLE: tbrole
-- =========================================================

CREATE TABLE tbrole (
    tbroleid INT AUTO_INCREMENT PRIMARY KEY,
    tbrolename VARCHAR(300) NOT NULL,
    tbroleemail VARCHAR(300) NOT NULL UNIQUE,
    tbrolepassword VARCHAR(300) NOT NULL,
    tbrolephone VARCHAR(25),
    tbroleactive CHAR(1) DEFAULT '1'
) ENGINE=InnoDB;


-- =========================================================
-- TABLE: tbroleadmin
-- =========================================================

CREATE TABLE tbroleadmin (
    tbroleadminid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleadminroleid INT NOT NULL UNIQUE,
    tbroleadminactive CHAR(1) DEFAULT '1'
) ENGINE=InnoDB;


-- =========================================================
-- TABLE: tbroleclient
-- =========================================================

CREATE TABLE tbroleclient (
    tbroleclientid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleclientroleid INT NOT NULL UNIQUE,
    tbroleclientactive CHAR(1) DEFAULT '1'
) ENGINE=InnoDB;


-- =========================================================
-- TABLE: tbowner
-- =========================================================

CREATE TABLE tbowner (
    tbownerid INT AUTO_INCREMENT PRIMARY KEY,
    tbownerroleid INT NOT NULL UNIQUE,
    tbownername VARCHAR(250) NOT NULL,--quitar
    tbownerlastname VARCHAR(250),
    tbowneralias VARCHAR(100),
    tbowneridentificationnumber VARCHAR(30),
    tbowneractive CHAR(1) DEFAULT '1'
) ENGINE=InnoDB;


-- =========================================================
-- TABLE: tblocation
-- =========================================================

CREATE TABLE tblocation (
    tblocationid INT AUTO_INCREMENT PRIMARY KEY,
    tblocationprovince VARCHAR(60) NOT NULL,
    tblocationcanton VARCHAR(60) NOT NULL,
    tblocationdistrict VARCHAR(60) NOT NULL,
    tblocationdetail VARCHAR(300)
) ENGINE=InnoDB;


-- =========================================================
-- TABLE: tblocalowner
-- =========================================================

CREATE TABLE tblocalowner (
    tblocalownerid INT AUTO_INCREMENT PRIMARY KEY,
    tblocalownerownerid INT NOT NULL,
    tblocalownerlocationid INT NOT NULL UNIQUE,
    tblocalname VARCHAR(150) NOT NULL,
    tblocaltype VARCHAR(50),
    tblocalcapacity INT,
    tblocalimage VARCHAR(255),
    tblocalactive CHAR(1) DEFAULT '1'
) ENGINE=InnoDB;


-- =========================================================
-- TABLE: tblocalservice
-- =========================================================

CREATE TABLE tblocalservice (
    tblocalserviceid INT AUTO_INCREMENT PRIMARY KEY,
    tblocalservicelocalownerid INT NOT NULL,
    tblocalservicename VARCHAR(200) NOT NULL,
    tblocalservicetype VARCHAR(100),
    tblocalserviceprice DECIMAL(10,2) NOT NULL,
    tblocalservicestatus VARCHAR(30) DEFAULT 'disponible',
    tblocalserviceactive CHAR(1) DEFAULT '1'
) ENGINE=InnoDB;


-- =========================================================
-- TABLE: tbclientbooking
-- =========================================================

CREATE TABLE tbclientbooking (
    tbclientbookingid INT AUTO_INCREMENT PRIMARY KEY,
    tbclientbookingclientid INT NOT NULL,
    tbclientbookinglocalownerid INT NOT NULL,
    tbclientbookingdate DATE NOT NULL,
    tbclientbookingeventtype VARCHAR(50),
    tbclientbookingstatus VARCHAR(30) DEFAULT 'pendiente',
    tbclientbookingactive CHAR(1) DEFAULT '1'
) ENGINE=InnoDB;


-- =========================================================
-- TABLE: tbbookingdetail
-- =========================================================

CREATE TABLE tbbookingdetail (
    tbbookingdetailid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookingdetailbookingid INT NOT NULL,
    tbbookingdetailserviceid INT NOT NULL,
    tbbookingdetailquantity INT NOT NULL DEFAULT 1,
    tbbookingdetailunitprice DECIMAL(10,2) NOT NULL,
    tbbookingdetaildiscount DECIMAL(10,2) DEFAULT 0,
    tbbookingdetailactive CHAR(1) DEFAULT '1'
) ENGINE=InnoDB;


-- =========================================================
-- TABLE: tbpaymentmethod
-- =========================================================

CREATE TABLE tbpaymentmethod (
    tbpaymentmethodid INT AUTO_INCREMENT PRIMARY KEY,
    tbpaymentmethodtype VARCHAR(50) NOT NULL,
    tbpaymentmethodactive CHAR(1) DEFAULT '1'
) ENGINE=InnoDB;


-- =========================================================
-- TABLE: tbbookinginvoice
-- =========================================================

CREATE TABLE tbbookinginvoice (
    tbbookinginvoiceid INT AUTO_INCREMENT PRIMARY KEY,
    tbbookinginvoicebookingid INT NOT NULL UNIQUE,
    tbbookinginvoicepaymentmethodid INT NOT NULL,
    tbbookinginvoicedate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbbookinginvoicestatus VARCHAR(30) DEFAULT 'pending',
    tbbookinginvoiceactive CHAR(1) DEFAULT '1'
) ENGINE=InnoDB;


-- =========================================================
-- TABLE: tbnotification
-- =========================================================

CREATE TABLE tbnotification (
    tbnotificationid INT AUTO_INCREMENT PRIMARY KEY,
    tbnotificationroleid INT NOT NULL,
    tbnotificationmessage VARCHAR(255) NOT NULL,
    tbnotificationdate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbnotificationread TINYINT(1) DEFAULT 0,
    tbnotificationactive CHAR(1) DEFAULT '1'
) ENGINE=InnoDB;


-- =========================================================
-- TEST DATA
-- =========================================================


-- =========================================================
-- Roles
-- =========================================================

INSERT INTO tbrole
(tbrolename, tbroleemail, tbrolepassword, tbrolephone)
VALUES
('Administrator','admin@eventhall.com','admin123','88888888'),
('John Doe','john@email.com','john123','87001122'),
('Jane Smith','jane@email.com','jane123','87002233');


-- =========================================================
-- Administrator
-- =========================================================

INSERT INTO tbroleadmin
(tbroleadminroleid)
VALUES
(1);


-- =========================================================
-- Clients
-- =========================================================

INSERT INTO tbroleclient
(tbroleclientroleid)
VALUES
(2),
(3);


-- =========================================================
-- Owner
-- =========================================================

INSERT INTO tbowner
(tbownerroleid, tbownername, tbownerlastname, tbowneralias, tbowneridentification)
VALUES
(1,'Carlos','Ramirez','Carlitos','101110111');


-- =========================================================
-- Location
-- =========================================================

INSERT INTO tblocation
(tblocationprovince, tblocationcanton, tblocationdistrict, tblocationdetail)
VALUES
('San Jose','Desamparados','San Miguel','200 meters north of the central park');


-- =========================================================
-- Event Hall
-- =========================================================

INSERT INTO tblocalowner
(
    tblocalownerownerid,
    tblocalownerlocationid,
    tblocalname,
    tblocaltype,
    tblocalcapacity,
    tblocalimage
)
VALUES
(
    1,
    1,
    'Golden Hall',
    'Event Hall',
    250,
    'goldenhall.jpg'
);


-- =========================================================
-- Services
-- =========================================================

INSERT INTO tblocalservice
(
    tblocalservicelocalownerid,
    tblocalservicename,
    tblocalservicetype,
    tblocalserviceprice
)
VALUES
(1,'Catering','Food',450000),
(1,'DJ','Music',175000),
(1,'Decoration','Decoration',250000),
(1,'Photography','Photography',300000);


-- =========================================================
-- Bookings
-- =========================================================

INSERT INTO tbclientbooking
(
    tbclientbookingclientid,
    tbclientbookinglocalownerid,
    tbclientbookingdate,
    tbclientbookingeventtype
)
VALUES
(1,1,'2026-09-20','Wedding'),
(2,1,'2026-10-05','Birthday');


-- =========================================================
-- Booking Details
-- =========================================================

INSERT INTO tbbookingdetail
(
    tbbookingdetailbookingid,
    tbbookingdetailserviceid,
    tbbookingdetailquantity,
    tbbookingdetailunitprice,
    tbbookingdetaildiscount
)
VALUES
(1,1,1,450000,0),
(1,2,1,175000,0),
(1,3,1,250000,50000),
(2,2,1,175000,0),
(2,4,1,300000,25000);


-- =========================================================
-- Payment Methods
-- =========================================================

INSERT INTO tbpaymentmethod
(tbpaymentmethodtype)
VALUES
('Cash'),
('Credit Card'),
('Bank Transfer'),
('SINPE');


-- =========================================================
-- Invoices
-- =========================================================

INSERT INTO tbbookinginvoice
(
    tbbookinginvoicebookingid,
    tbbookinginvoicepaymentmethodid,
    tbbookinginvoicestatus
)
VALUES
(1,2,'Paid'),
(2,4,'Pending');


-- =========================================================
-- Notifications
-- =========================================================

INSERT INTO tbnotification
(
    tbnotificationroleid,
    tbnotificationmessage
)
VALUES
(1,'A new booking has been created.'),
(2,'Your booking has been confirmed.'),
(3,'Your payment is pending.');