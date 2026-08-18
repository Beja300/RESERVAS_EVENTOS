-- =========================================================
-- Paradigms Project — Database Schema
-- =========================================================

CREATE DATABASE IF NOT EXISTS dbeventhall;

USE dbeventhall;

CREATE TABLE tbrole (
    tbrolepk INT AUTO_INCREMENT PRIMARY KEY,
    tbrolename VARCHAR(300) NOT NULL,
    tbroleemail VARCHAR(300) NOT NULL UNIQUE,
    tbrolepassword VARCHAR(300) NOT NULL,
    tbrolephone VARCHAR(25),
    tbroleactive CHAR(1) DEFAULT '1'
) ENGINE=InnoDB;

CREATE TABLE tbroleadmin (
    tbroleadminpk INT AUTO_INCREMENT PRIMARY KEY,
    tbroleadminfk INT NOT NULL UNIQUE,
    tbroleadminactive CHAR(1) DEFAULT '1',
    FOREIGN KEY (tbroleadminfk) REFERENCES tbrole(tbrolepk) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbroleclient (
    tbroleclientpk INT AUTO_INCREMENT PRIMARY KEY,
    tbroleclientfk INT NOT NULL UNIQUE,
    tbroleclientactive CHAR(1) DEFAULT '1',
    FOREIGN KEY (tbroleclientfk) REFERENCES tbrole(tbrolepk) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbowner (
    tbownerpk INT AUTO_INCREMENT PRIMARY KEY,
    tbrolefk INT NOT NULL UNIQUE,
    tbownername VARCHAR(250) NOT NULL,
    tbownerlastname VARCHAR(250),
    tbowneralias VARCHAR(100),
    tbowneridentification VARCHAR(30),
    tbowneractive CHAR(1) DEFAULT '1',
    FOREIGN KEY (tbrolefk) REFERENCES tbrole(tbrolepk) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tblocation (
    tblocationpk INT AUTO_INCREMENT PRIMARY KEY,
    tblocationprovince VARCHAR(60) NOT NULL,
    tblocationcanton VARCHAR(60) NOT NULL,
    tblocationdistrict VARCHAR(60) NOT NULL,
    tblocationdetail VARCHAR(300)
) ENGINE=InnoDB;

CREATE TABLE tblocalowner (
    tblocalownerpk INT AUTO_INCREMENT PRIMARY KEY,
    tbownerfk INT NOT NULL,
    tblocationfk INT NOT NULL UNIQUE,
    tblocalname VARCHAR(150) NOT NULL,
    tblocaltype VARCHAR(50),
    tblocalcapacity INT,
    tblocalimage VARCHAR(255),
    tblocalactive CHAR(1) DEFAULT '1',
    FOREIGN KEY (tbownerfk) REFERENCES tbowner(tbownerpk) ON DELETE CASCADE,
    FOREIGN KEY (tblocationfk) REFERENCES tblocation(tblocationpk) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE tblocalservice (
    tblocalservicepk INT AUTO_INCREMENT PRIMARY KEY,
    tblocalownerfk INT NOT NULL,
    tblocalservicename VARCHAR(200) NOT NULL,
    tblocalservicetype VARCHAR(100),
    tblocalserviceprice DECIMAL(10,2) NOT NULL,
    tblocalservicestatus VARCHAR(30) DEFAULT 'disponible',
    tblocalserviceactive CHAR(1) DEFAULT '1',
    FOREIGN KEY (tblocalownerfk) REFERENCES tblocalowner(tblocalownerpk) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbclientbooking (
    tbclientbookingpk INT AUTO_INCREMENT PRIMARY KEY,
    tbroleclientfk INT NOT NULL,
    tblocalownerfk INT NOT NULL,
    tbclientbookingdate DATE NOT NULL,
    tbclientbookingeventtype VARCHAR(50),
    tbclientbookingstatus VARCHAR(30) DEFAULT 'pendiente',
    tbclientbookingactive CHAR(1) DEFAULT '1',
    FOREIGN KEY (tbroleclientfk) REFERENCES tbroleclient(tbroleclientpk) ON DELETE CASCADE,
    FOREIGN KEY (tblocalownerfk) REFERENCES tblocalowner(tblocalownerpk) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbbookingdetail (
    tbbookingdetailpk INT AUTO_INCREMENT PRIMARY KEY,
    tbclientbookingfk INT NOT NULL,
    tblocalservicefk INT NOT NULL,
    tbbookingdetailquantity INT NOT NULL DEFAULT 1,
    tbbookingdetailunitprice DECIMAL(10,2) NOT NULL,
    tbbookingdetaildiscount DECIMAL(10,2) DEFAULT 0,
    tbbookingdetailactive CHAR(1) DEFAULT '1',
    FOREIGN KEY (tbclientbookingfk) REFERENCES tbclientbooking(tbclientbookingpk) ON DELETE CASCADE,
    FOREIGN KEY (tblocalservicefk) REFERENCES tblocalservice(tblocalservicepk) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE tbpaymentmethod (
    tbpaymentmethodpk INT AUTO_INCREMENT PRIMARY KEY,
    tbpaymentmethodtype VARCHAR(50) NOT NULL,
    tbpaymentmethodactive CHAR(1) DEFAULT '1'
) ENGINE=InnoDB;

CREATE TABLE tbbookinginvoice (
    tbbookinginvoicepk INT AUTO_INCREMENT PRIMARY KEY,
    tbclientbookingfk INT NOT NULL UNIQUE,
    tbpaymentmethodfk INT NOT NULL,
    tbbookinginvoicedate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbbookinginvoicestatus VARCHAR(30) DEFAULT 'pending',
    tbbookinginvoiceactive CHAR(1) DEFAULT '1',
    FOREIGN KEY (tbclientbookingfk) REFERENCES tbclientbooking(tbclientbookingpk) ON DELETE CASCADE,
    FOREIGN KEY (tbpaymentmethodfk) REFERENCES tbpaymentmethod(tbpaymentmethodpk) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE tbnotification (
    tbnotificationpk INT AUTO_INCREMENT PRIMARY KEY,
    tbrolefk INT NOT NULL,
    tbnotificationmessage VARCHAR(255) NOT NULL,
    tbnotificationdate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbnotificationread TINYINT(1) DEFAULT 0,
    tbnotificationactive CHAR(1) DEFAULT '1',
    FOREIGN KEY (tbrolefk) REFERENCES tbrole(tbrolepk) ON DELETE CASCADE
) ENGINE=InnoDB;




-- =========================================================
-- TEST DATA
-- =========================================================

-- Roles
INSERT INTO tbrole
(tbrolename, tbroleemail, tbrolepassword, tbrolephone)
VALUES
('Administrator','admin@eventhall.com','admin123','88888888'),
('John Doe','john@email.com','john123','87001122'),
('Jane Smith','jane@email.com','jane123','87002233');

-- Administrator
INSERT INTO tbroleadmin (tbroleadminfk)
VALUES (1);

-- Clients
INSERT INTO tbroleclient (tbroleclientfk)
VALUES
(2),
(3);

-- Owner
INSERT INTO tbowner
(tbrolefk, tbownername, tbownerlastname, tbowneralias, tbowneridentification)
VALUES
(1,'Carlos','Ramirez','Carlitos','101110111');

-- Location
INSERT INTO tblocation
(tblocationprovince, tblocationcanton, tblocationdistrict, tblocationdetail)
VALUES
('San Jose','Desamparados','San Miguel','200 meters north of the central park');

-- Event Hall
INSERT INTO tblocalowner
(tbownerfk, tblocationfk, tblocalname, tblocaltype, tblocalcapacity, tblocalimage)
VALUES
(1,1,'Golden Hall','Event Hall',250,'goldenhall.jpg');

-- Services
INSERT INTO tblocalservice
(tblocalownerfk, tblocalservicename, tblocalservicetype, tblocalserviceprice)
VALUES
(1,'Catering','Food',450000),
(1,'DJ','Music',175000),
(1,'Decoration','Decoration',250000),
(1,'Photography','Photography',300000);

-- Bookings
INSERT INTO tbclientbooking
(tbroleclientfk, tblocalownerfk, tbclientbookingdate, tbclientbookingeventtype)
VALUES
(1,1,'2026-09-20','Wedding'),
(2,1,'2026-10-05','Birthday');

-- Booking Details
INSERT INTO tbbookingdetail
(tbclientbookingfk, tblocalservicefk, tbbookingdetailquantity, tbbookingdetailunitprice, tbbookingdetaildiscount)
VALUES
(1,1,1,450000,0),
(1,2,1,175000,0),
(1,3,1,250000,50000),
(2,2,1,175000,0),
(2,4,1,300000,25000);

-- Payment Methods
INSERT INTO tbpaymentmethod
(tbpaymentmethodtype)
VALUES
('Cash'),
('Credit Card'),
('Bank Transfer'),
('SINPE');

-- Invoices
INSERT INTO tbbookinginvoice
(tbclientbookingfk, tbpaymentmethodfk, tbbookinginvoicestatus)
VALUES
(1,2,'Paid'),
(2,4,'Pending');

-- Notifications
INSERT INTO tbnotification
(tbrolefk, tbnotificationmessage)
VALUES
(1,'A new booking has been created.'),
(2,'Your booking has been confirmed.'),
(3,'Your payment is pending.');