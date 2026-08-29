-- =========================================================
-- EventHall — Seed de datos de prueba (dbeventhall.sql debe
-- haberse ejecutado antes, sobre una base limpia).
-- Todos los accesos usan la clave: Clave123
-- (hash bcrypt: $2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W)
--
-- Relaciones SIN foreign keys, igual que el esquema:
--   rol <-> perfil  se resuelve por las tablas intermedias
--   reserva <-> detalle por tbbookingdetail (junction pura)
-- =========================================================

-- =========================================================
-- 1) ROLES BASE (identidad de acceso)
-- =========================================================
INSERT INTO tbrole (tbrolename, tbroleemail, tbrolepassword, tbrolephone, tbroleactive) VALUES
('Administrador',     'admin@eventhall.com',   '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '88888887', TRUE);
SET @adminRoleId = LAST_INSERT_ID();

INSERT INTO tbrole (tbrolename, tbroleemail, tbrolepassword, tbrolephone, tbroleactive) VALUES
('María Fernanda',    'owner@eventhall.com',   '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '88888888', TRUE);
SET @ownerRoleId = LAST_INSERT_ID();

INSERT INTO tbrole (tbrolename, tbroleemail, tbrolepassword, tbrolephone, tbroleactive) VALUES
('Carlos El Cliente', 'cliente@eventhall.com', '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '88888889', TRUE);
SET @clientRoleId = LAST_INSERT_ID();

-- =========================================================
-- 2) UBICACIONES
-- =========================================================
INSERT INTO tblocation (tblocationprovince, tblocationcanton, tblocationdistrict, tblocationtown, tblocationdescription) VALUES
('San José', 'Escazú',    'San Rafael',  'Centro',        'Zona residencial'),
('Alajuela', 'Alajuela',  'San José',    'Frente al parque', 'Céntrico'),
('Heredia',  'Heredia',   'San Francisco', 'La Y Griega', 'Al oeste del Mall'  );
SET @locSanRafael = 1;
SET @locAlajuela  = 2;
SET @locHeredia   = 3;

-- =========================================================
-- 3) PERFILES Y JUNCTIONS (perfil -> junction -> rol)
-- =========================================================
-- Admin
INSERT INTO tbadmin (tbadminname, tbadminimage, tbadminactive) VALUES
('Administrador del Sistema', NULL, TRUE);
SET @adminId = LAST_INSERT_ID();

INSERT INTO tbroleadmin (tbroleadminrolid, tbroleadminadminid, tbroleadminactive) VALUES
(@adminRoleId, @adminId, TRUE);

-- Owner
INSERT INTO tbowner (tbownerfirstname, tbownerlastname, tbowneralias, tbowneridentificationnumber, tbownerimage, tbowneractive) VALUES
('María Fernanda', 'Rodríguez', 'Mari', '207610598', NULL, TRUE);
SET @ownerId = LAST_INSERT_ID();

INSERT INTO tbroleowner (tbroleownerrolid, tbroleownerownerid, tbroleowneractive) VALUES
(@ownerRoleId, @ownerId, TRUE);

-- Client
INSERT INTO tbclient (tbclientname, tbclientimage, tbclientlocationid, tbclientactive) VALUES
('Carlos El Cliente', NULL, @locSanRafael, TRUE);
SET @clientId = LAST_INSERT_ID();

INSERT INTO tbroleclient (tbroleclientrolid, tbroleclientclientid, tbroleclientactive) VALUES
(@clientRoleId, @clientId, TRUE);

-- =========================================================
-- 4) LOCAL (VENUE) DEL OWNER
-- =========================================================
INSERT INTO tbvenue (tbvenueownerid, tbvenuelocationid, tbvenuename, tbvenuetype, tbvenuecapacity, tbvenueprice, tbvenueimage, tbvenueactive) VALUES
(@ownerId, @locAlajuela, 'Salón La Quinta', 'Salón de eventos', 120, 120000.00, NULL, TRUE);
SET @venueId = LAST_INSERT_ID();

-- =========================================================
-- 5) SERVICIOS (estado 'aprobado' para poder reservarse)
-- =========================================================
INSERT INTO tbservice (tbservicelocalid, tbservicename, tbservicetype, tbserviceprice, tbservicestate, tbserviceactive) VALUES
(@venueId, 'Decoración floral',          'Decoración', 150000.00, 'aprobado', TRUE),
(@venueId, 'Servicio de banquetes',      'Catering',   250000.00, 'aprobado', TRUE),
(@venueId, 'Sonido y luces',             'Producción',  80000.00, 'aprobado', TRUE);
SET @service1 = 1;
SET @service2 = 2;
SET @service3 = 3;

-- =========================================================
-- 6) PROMOCIÓN + servicios incluidos (junction)
-- =========================================================
INSERT INTO tbpromotion (tbpromotionvenueid, tbpromotiondescription, tbpromotionlabel, tbpromotionstart, tbpromotionend, tbpromotionminservices, tbpromotionactive) VALUES
(@venueId, 'Pack boda completo', 'Pack Boda', '2026-01-01', '2026-12-31', 3, TRUE);
SET @promoId = LAST_INSERT_ID();

INSERT INTO tbpromotionservice (tbpromotionservicepromotionid, tbpromotionserviceserviceid, tbpromotionserviceactive) VALUES
(@promoId, @service1, TRUE),
(@promoId, @service2, TRUE),
(@promoId, @service3, TRUE);

-- =========================================================
-- 7) MÉTODOS DE PAGO
-- =========================================================
INSERT INTO tbpaymentmethod (tbpaymentmethodtype, tbpaymentmethodactive) VALUES
('Efectivo', TRUE),
('Tarjeta',  TRUE),
('Transferencia', TRUE);
SET @pmEfectivo       = 1;
SET @pmTarjeta       = 2;
SET @pmTransferencia = 3;

-- =========================================================
-- 7b) DATOS DE COBRO DEL PROPIETARIO (tbownerpayment)
-- El cliente elige el método y ve estos datos para pagar al owner.
-- =========================================================
INSERT INTO tbownerpayment (tbownerpaymentownerid, tbownerpaymentpaymentmethodid, tbownerpaymentholder, tbownerpaymentaccount, tbownerpaymentinstructions, tbownerpaymentactive) VALUES
(@ownerId, @pmEfectivo,       'María Fernanda Rodríguez',       NULL,      'Realizar el pago en efectivo el día del evento.', TRUE),
(@ownerId, @pmTarjeta,        'María Fernanda Rodríguez',       NULL,      'Aceptamos tarjetas Visa y Mastercard.', TRUE),
(@ownerId, @pmTransferencia,  'Salón La Quinta S.A.',           'CR12 1234 5678 9012 3456 7', 'Transferencia SINPE a la cuenta indicada.', TRUE);

-- =========================================================
-- 8) CONFIGURACIÓN DE COMISIÓN (5% + IVA 13%)
-- =========================================================
INSERT INTO tbcommissionconfig (tbcommissionconfigpercentage, tbcommissionconfigtax, tbcommissionconfigactive) VALUES
(5.00, 13.00, TRUE);

-- =========================================================
-- 9) UNA RESERVA DE EJEMPLO (cliente -> junction detalle)
-- =========================================================
INSERT INTO tbbooking (tbbookingclientid, tbbookinglocalid, tbbookingdate, tbbookingeventtype, tbbookingstate, tbbookingactive) VALUES
(@clientId, @venueId, '2026-10-10', 'Boda', 'pendiente', TRUE);
SET @bookingId = LAST_INSERT_ID();

-- Línea base: renta del local (la factura NUNCA puede ser 0) + junction
INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venueId, 1, 120000.00, 0.00, TRUE);
SET @detailBase = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@bookingId, @detailBase, TRUE);

-- Línea 1: detalle (tbdetail) + junction (tbbookingdetail); servicio
INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service1, NULL, 1, 150000.00, 0.00, TRUE);
SET @detail1 = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@bookingId, @detail1, TRUE);

-- Línea 2 (servicio)
INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service2, NULL, 2, 250000.00, 0.00, TRUE);
SET @detail2 = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@bookingId, @detail2, TRUE);

-- =========================================================
-- 10) UNA CALIFICACIÓN DE EJEMPLO (local y servicio)
-- =========================================================
INSERT INTO tbvenuerating (tbvenueratingvenueid, tbvenueratingroleid, tbvenueratingstars, tbvenueratingcomment, tbvenueratingactive) VALUES
(@venueId, @clientRoleId, 5, 'Excelente lugar para celebrar.', TRUE);

INSERT INTO tbservicerating (tbserviceratingserviceid, tbserviceratingroleid, tbserviceratingstars, tbserviceratingcomment, tbserviceratingactive) VALUES
(@service1, @clientRoleId, 5, 'La decoración fue espectacular.', TRUE);