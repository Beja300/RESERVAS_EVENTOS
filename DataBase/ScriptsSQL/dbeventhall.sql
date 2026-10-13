CREATE DATABASE IF NOT EXISTS dbeventhall;

USE dbeventhall;

-- =========================================================
-- 1) tbrole: identidad base de acceso (login/permisos)
-- =========================================================
CREATE TABLE tbrole (
    tbroleid INT AUTO_INCREMENT PRIMARY KEY,
    tbrolename VARCHAR(300) NOT NULL,
    tbroleemail VARCHAR(300) NOT NULL,
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
    tbroleid INT NOT NULL,
    tbadminid INT NOT NULL,
    tbroleadminactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 3) tbroleclient: relacion rol <-> cliente
--    (junction: id propio + idRol + idClient + active)
-- =========================================================
CREATE TABLE tbroleclient (
    tbroleclientid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleid INT NOT NULL,
    tbclientid INT NOT NULL,
    tbroleclientactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 4) tbroleowner: relacion rol <-> propietario
--    (junction: id propio + idRol + idOwner + active)
-- =========================================================
CREATE TABLE tbroleowner (
    tbroleownerid INT AUTO_INCREMENT PRIMARY KEY,
    tbroleid INT NOT NULL,
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
    tbowneridentificationnumber VARCHAR(30),
    tbownerimage VARCHAR(255),
    tbowneractive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 8) tblocation: ubicaciones (provincia/canton/distrito/pueblo/desc) + coords
--    lat/long (DECIMAL(10,7)) para calcular distancia con Haversine.
-- =========================================================
CREATE TABLE tblocation (
    tblocationid INT AUTO_INCREMENT PRIMARY KEY,
    tblocationprovince VARCHAR(60) NOT NULL,
    tblocationcanton VARCHAR(60) NOT NULL,
    tblocationdistrict VARCHAR(60) NOT NULL,
    tblocationtown VARCHAR(100),
    tblocationdescription VARCHAR(300),
    tblocationlatitude DECIMAL(10,7) NULL,
    tblocationlongitude DECIMAL(10,7) NULL
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
    tbbookingid INT NOT NULL,
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

-- =========================================================
-- 29) tbcreditconfig: política de crédito definida por el
--     propietario (plazo en días y umbral de cliente frecuente).
-- =========================================================
CREATE TABLE tbcreditconfig (
    tbcreditconfigid INT AUTO_INCREMENT PRIMARY KEY,
    tbownerid INT NOT NULL,
    tbcreditconfigenabled BOOLEAN NOT NULL DEFAULT FALSE,
    tbcreditconfigdays INT NOT NULL DEFAULT 30,
    tbcreditconfigfrequentmin INT NOT NULL DEFAULT 3,
    tbcreditconfigactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

-- =========================================================
-- 30) tbcredit: cuenta por cobrar generada al otorgar crédito
--     sobre una reserva (relación 1:1 con la reserva).
--     estados: pendiente | parcial | pagado | vencido
-- =========================================================
CREATE TABLE tbcredit (
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

-- =========================================================
-- 31) tbcreditpayment: abonos (pagos parciales) de un crédito.
-- =========================================================
CREATE TABLE tbcreditpayment (
    tbcreditpaymentid INT AUTO_INCREMENT PRIMARY KEY,
    tbcreditid INT NOT NULL,
    tbcreditpaymentamount DECIMAL(12,2) NOT NULL,
    tbcreditpaymentnote VARCHAR(255),
    tbroleid INT,
    tbcreditpaymentdate DATETIME DEFAULT CURRENT_TIMESTAMP,
    tbcreditpaymentactive BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tbvenueimage (
    tbvenueimageid INT AUTO_INCREMENT PRIMARY KEY,
    tbvenueid INT NOT NULL,
    tbvenueimagepath VARCHAR(255) NOT NULL,
    tbvenueimagesort INT NOT NULL DEFAULT 0,
    tbvenueimageactive TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- =========================================================
-- 32) CLAVES FORÁNEAS (FOREIGN KEY)
--     Se añaden al final con ALTER TABLE para no depender del
--     orden de creación de las tablas (p. ej. tbbookingticket
--     se define antes que su padre tbpaymentmethod).
--     Políticas:
--       * Maestras/transaccionales -> ON DELETE RESTRICT
--       * Dependientes directas      -> ON DELETE CASCADE
--       * Columnas nullable          -> ON DELETE SET NULL
--       * Historial de identidad (NOT NULL) -> ON DELETE RESTRICT
--     Todas con ON UPDATE CASCADE (las PK son AUTO_INCREMENT y
--     no cambian, pero es la política estándar del proyecto).
-- =========================================================

-- Identidad (junctions rol <-> perfil)
ALTER TABLE tbroleadmin ADD CONSTRAINT fk_tbroleadmin_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbroleadmin ADD CONSTRAINT fk_tbroleadmin_tbadminid FOREIGN KEY (tbadminid) REFERENCES tbadmin (tbadminid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbroleclient ADD CONSTRAINT fk_tbroleclient_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbroleclient ADD CONSTRAINT fk_tbroleclient_tbclientid FOREIGN KEY (tbclientid) REFERENCES tbclient (tbclientid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbroleowner ADD CONSTRAINT fk_tbroleowner_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbroleowner ADD CONSTRAINT fk_tbroleowner_tbownerid FOREIGN KEY (tbownerid) REFERENCES tbowner (tbownerid) ON DELETE CASCADE ON UPDATE CASCADE;

-- Ubicación
ALTER TABLE tbclient ADD CONSTRAINT fk_tbclient_tblocationid FOREIGN KEY (tblocationid) REFERENCES tblocation (tblocationid) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE tbvenue ADD CONSTRAINT fk_tbvenue_tblocationid FOREIGN KEY (tblocationid) REFERENCES tblocation (tblocationid) ON DELETE SET NULL ON UPDATE CASCADE;

-- Local / propietario / galería
ALTER TABLE tbvenue ADD CONSTRAINT fk_tbvenue_tbownerid FOREIGN KEY (tbownerid) REFERENCES tbowner (tbownerid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbvenueimage ADD CONSTRAINT fk_tbvenueimage_tbvenueid FOREIGN KEY (tbvenueid) REFERENCES tbvenue (tbvenueid) ON DELETE CASCADE ON UPDATE CASCADE;

-- Servicios / promociones
ALTER TABLE tbservice ADD CONSTRAINT fk_tbservice_tbvenueid FOREIGN KEY (tbvenueid) REFERENCES tbvenue (tbvenueid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbservice ADD CONSTRAINT fk_tbservice_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE tbservicehistory ADD CONSTRAINT fk_tbservicehistory_tbserviceid FOREIGN KEY (tbserviceid) REFERENCES tbservice (tbserviceid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbpromotion ADD CONSTRAINT fk_tbpromotion_tbvenueid FOREIGN KEY (tbvenueid) REFERENCES tbvenue (tbvenueid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbpromotionservice ADD CONSTRAINT fk_tbpromotionservice_tbpromotionid FOREIGN KEY (tbpromotionid) REFERENCES tbpromotion (tbpromotionid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbpromotionservice ADD CONSTRAINT fk_tbpromotionservice_tbserviceid FOREIGN KEY (tbserviceid) REFERENCES tbservice (tbserviceid) ON DELETE CASCADE ON UPDATE CASCADE;

-- Reservas / detalle
ALTER TABLE tbbooking ADD CONSTRAINT fk_tbbooking_tbclientid FOREIGN KEY (tbclientid) REFERENCES tbclient (tbclientid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbbooking ADD CONSTRAINT fk_tbbooking_tbvenueid FOREIGN KEY (tbvenueid) REFERENCES tbvenue (tbvenueid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbdetail ADD CONSTRAINT fk_tbdetail_tbserviceid FOREIGN KEY (tbserviceid) REFERENCES tbservice (tbserviceid) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE tbdetail ADD CONSTRAINT fk_tbdetail_tbvenueid FOREIGN KEY (tbvenueid) REFERENCES tbvenue (tbvenueid) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE tbbookingdetail ADD CONSTRAINT fk_tbbookingdetail_tbbookingid FOREIGN KEY (tbbookingid) REFERENCES tbbooking (tbbookingid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbbookingdetail ADD CONSTRAINT fk_tbbookingdetail_tbdetailid FOREIGN KEY (tbdetailid) REFERENCES tbdetail (tbdetailid) ON DELETE CASCADE ON UPDATE CASCADE;

-- Cobros / facturas / ganancias
ALTER TABLE tbbookingticket ADD CONSTRAINT fk_tbbookingticket_tbbookingid FOREIGN KEY (tbbookingid) REFERENCES tbbooking (tbbookingid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbbookingticket ADD CONSTRAINT fk_tbbookingticket_tbpaymentmethodid FOREIGN KEY (tbpaymentmethodid) REFERENCES tbpaymentmethod (tbpaymentmethodid) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE tbownerpayment ADD CONSTRAINT fk_tbownerpayment_tbownerid FOREIGN KEY (tbownerid) REFERENCES tbowner (tbownerid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbownerpayment ADD CONSTRAINT fk_tbownerpayment_tbpaymentmethodid FOREIGN KEY (tbpaymentmethodid) REFERENCES tbpaymentmethod (tbpaymentmethodid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbinvoice ADD CONSTRAINT fk_tbinvoice_tbbookingid FOREIGN KEY (tbbookingid) REFERENCES tbbooking (tbbookingid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbinvoice ADD CONSTRAINT fk_tbinvoice_tbpaymentmethodid FOREIGN KEY (tbpaymentmethodid) REFERENCES tbpaymentmethod (tbpaymentmethodid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbeearning ADD CONSTRAINT fk_tbeearning_tbbookingid FOREIGN KEY (tbbookingid) REFERENCES tbbooking (tbbookingid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbeearning ADD CONSTRAINT fk_tbeearning_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE SET NULL ON UPDATE CASCADE;

-- Auditoría / historial
ALTER TABLE tbbookinghistory ADD CONSTRAINT fk_tbbookinghistory_tbbookingid FOREIGN KEY (tbbookingid) REFERENCES tbbooking (tbbookingid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbbookinghistory ADD CONSTRAINT fk_tbbookinghistory_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE tbbookingrefund ADD CONSTRAINT fk_tbbookingrefund_tbbookingid FOREIGN KEY (tbbookingid) REFERENCES tbbooking (tbbookingid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbbookingrefund ADD CONSTRAINT fk_tbbookingrefund_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbownerhistory ADD CONSTRAINT fk_tbownerhistory_tbownerid FOREIGN KEY (tbownerid) REFERENCES tbowner (tbownerid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbuserhistory ADD CONSTRAINT fk_tbuserhistory_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbrolpasswordhistorical ADD CONSTRAINT fk_tbrolpasswordhistorical_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbrolphonehistorical ADD CONSTRAINT fk_tbrolphonehistorical_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbrolemailhistorical ADD CONSTRAINT fk_tbrolemailhistorical_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Calificaciones / notificaciones
ALTER TABLE tbvenuerating ADD CONSTRAINT fk_tbvenuerating_tbvenueid FOREIGN KEY (tbvenueid) REFERENCES tbvenue (tbvenueid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbvenuerating ADD CONSTRAINT fk_tbvenuerating_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbservicerating ADD CONSTRAINT fk_tbservicerating_tbserviceid FOREIGN KEY (tbserviceid) REFERENCES tbservice (tbserviceid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbservicerating ADD CONSTRAINT fk_tbservicerating_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbnotification ADD CONSTRAINT fk_tbnotification_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Crédito
ALTER TABLE tbcreditconfig ADD CONSTRAINT fk_tbcreditconfig_tbownerid FOREIGN KEY (tbownerid) REFERENCES tbowner (tbownerid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbcredit ADD CONSTRAINT fk_tbcredit_tbbookingid FOREIGN KEY (tbbookingid) REFERENCES tbbooking (tbbookingid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbcredit ADD CONSTRAINT fk_tbcredit_tbclientid FOREIGN KEY (tbclientid) REFERENCES tbclient (tbclientid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbcredit ADD CONSTRAINT fk_tbcredit_tbownerid FOREIGN KEY (tbownerid) REFERENCES tbowner (tbownerid) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE tbcreditpayment ADD CONSTRAINT fk_tbcreditpayment_tbcreditid FOREIGN KEY (tbcreditid) REFERENCES tbcredit (tbcreditid) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE tbcreditpayment ADD CONSTRAINT fk_tbcreditpayment_tbroleid FOREIGN KEY (tbroleid) REFERENCES tbrole (tbroleid) ON DELETE SET NULL ON UPDATE CASCADE;