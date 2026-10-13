-- =========================================================
-- MIGRACIÓN 001: claves foráneas (FOREIGN KEY) en dbeventhall
-- ---------------------------------------------------------
-- Añade las 50 restricciones de integridad referencial que
-- relacionan las tablas del esquema. Se ejecuta UNA SOLA VEZ
-- sobre bases existentes sin claves foráneas.
--
-- En instalaciones nuevas NO es necesario: dbeventhall.sql ya
-- incluye esta misma sección al final.
--
-- IMPORTANTE:
--   * DDL no es transaccional en MySQL/MariaDB: no se puede
--     hacer ROLLBACK. Ejecutar el pre-chequeo antes.
--   * No existe "ADD CONSTRAINT IF NOT EXISTS": si se ejecuta
--     dos veces fallará con "Duplicate key name". Es idempotente
--     solo respecto de tbvenueimage (CREATE TABLE IF NOT EXISTS).
--
-- POLÍTICAS:
--   * Maestras/transaccionales -> ON DELETE RESTRICT
--   * Dependientes directas      -> ON DELETE CASCADE
--   * Columnas nullable          -> ON DELETE SET NULL
--   * Historial de identidad (NOT NULL) -> ON DELETE RESTRICT
--   * Todas con ON UPDATE CASCADE
-- =========================================================

USE dbeventhall;

-- ---------------------------------------------------------
-- 0) Guard: crear la tabla de galería si la base es antigua
--    (faltaba en instalaciones previas a migrate_venue_gallery).
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbvenueimage (
    tbvenueimageid INT AUTO_INCREMENT PRIMARY KEY,
    tbvenueid INT NOT NULL,
    tbvenueimagepath VARCHAR(255) NOT NULL,
    tbvenueimagesort INT NOT NULL DEFAULT 0,
    tbvenueimageactive TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 1) PRE-CHEQUEO DE HUÉRFANOS
--    Ejecuta esta sección ANTES de las claves foráneas.
--    Solo imprime las relaciones con filas huérfanas (> 0).
--    Si devuelve filas, corregir los datos antes de continuar.
-- ---------------------------------------------------------
SELECT 'tbroleadmin.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbroleadmin c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbroleadmin.tbadminid -> tbadmin' AS fk, COUNT(*) AS huerfanos FROM tbroleadmin c LEFT JOIN tbadmin p ON c.tbadminid = p.tbadminid WHERE c.tbadminid IS NOT NULL AND p.tbadminid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbroleclient.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbroleclient c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbroleclient.tbclientid -> tbclient' AS fk, COUNT(*) AS huerfanos FROM tbroleclient c LEFT JOIN tbclient p ON c.tbclientid = p.tbclientid WHERE c.tbclientid IS NOT NULL AND p.tbclientid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbroleowner.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbroleowner c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbroleowner.tbownerid -> tbowner' AS fk, COUNT(*) AS huerfanos FROM tbroleowner c LEFT JOIN tbowner p ON c.tbownerid = p.tbownerid WHERE c.tbownerid IS NOT NULL AND p.tbownerid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbclient.tblocationid -> tblocation' AS fk, COUNT(*) AS huerfanos FROM tbclient c LEFT JOIN tblocation p ON c.tblocationid = p.tblocationid WHERE c.tblocationid IS NOT NULL AND p.tblocationid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbvenue.tblocationid -> tblocation' AS fk, COUNT(*) AS huerfanos FROM tbvenue c LEFT JOIN tblocation p ON c.tblocationid = p.tblocationid WHERE c.tblocationid IS NOT NULL AND p.tblocationid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbvenue.tbownerid -> tbowner' AS fk, COUNT(*) AS huerfanos FROM tbvenue c LEFT JOIN tbowner p ON c.tbownerid = p.tbownerid WHERE c.tbownerid IS NOT NULL AND p.tbownerid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbvenueimage.tbvenueid -> tbvenue' AS fk, COUNT(*) AS huerfanos FROM tbvenueimage c LEFT JOIN tbvenue p ON c.tbvenueid = p.tbvenueid WHERE c.tbvenueid IS NOT NULL AND p.tbvenueid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbservice.tbvenueid -> tbvenue' AS fk, COUNT(*) AS huerfanos FROM tbservice c LEFT JOIN tbvenue p ON c.tbvenueid = p.tbvenueid WHERE c.tbvenueid IS NOT NULL AND p.tbvenueid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbservice.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbservice c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbservicehistory.tbserviceid -> tbservice' AS fk, COUNT(*) AS huerfanos FROM tbservicehistory c LEFT JOIN tbservice p ON c.tbserviceid = p.tbserviceid WHERE c.tbserviceid IS NOT NULL AND p.tbserviceid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbpromotion.tbvenueid -> tbvenue' AS fk, COUNT(*) AS huerfanos FROM tbpromotion c LEFT JOIN tbvenue p ON c.tbvenueid = p.tbvenueid WHERE c.tbvenueid IS NOT NULL AND p.tbvenueid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbpromotionservice.tbpromotionid -> tbpromotion' AS fk, COUNT(*) AS huerfanos FROM tbpromotionservice c LEFT JOIN tbpromotion p ON c.tbpromotionid = p.tbpromotionid WHERE c.tbpromotionid IS NOT NULL AND p.tbpromotionid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbpromotionservice.tbserviceid -> tbservice' AS fk, COUNT(*) AS huerfanos FROM tbpromotionservice c LEFT JOIN tbservice p ON c.tbserviceid = p.tbserviceid WHERE c.tbserviceid IS NOT NULL AND p.tbserviceid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbbooking.tbclientid -> tbclient' AS fk, COUNT(*) AS huerfanos FROM tbbooking c LEFT JOIN tbclient p ON c.tbclientid = p.tbclientid WHERE c.tbclientid IS NOT NULL AND p.tbclientid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbbooking.tbvenueid -> tbvenue' AS fk, COUNT(*) AS huerfanos FROM tbbooking c LEFT JOIN tbvenue p ON c.tbvenueid = p.tbvenueid WHERE c.tbvenueid IS NOT NULL AND p.tbvenueid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbdetail.tbserviceid -> tbservice' AS fk, COUNT(*) AS huerfanos FROM tbdetail c LEFT JOIN tbservice p ON c.tbserviceid = p.tbserviceid WHERE c.tbserviceid IS NOT NULL AND p.tbserviceid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbdetail.tbvenueid -> tbvenue' AS fk, COUNT(*) AS huerfanos FROM tbdetail c LEFT JOIN tbvenue p ON c.tbvenueid = p.tbvenueid WHERE c.tbvenueid IS NOT NULL AND p.tbvenueid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbbookingdetail.tbbookingid -> tbbooking' AS fk, COUNT(*) AS huerfanos FROM tbbookingdetail c LEFT JOIN tbbooking p ON c.tbbookingid = p.tbbookingid WHERE c.tbbookingid IS NOT NULL AND p.tbbookingid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbbookingdetail.tbdetailid -> tbdetail' AS fk, COUNT(*) AS huerfanos FROM tbbookingdetail c LEFT JOIN tbdetail p ON c.tbdetailid = p.tbdetailid WHERE c.tbdetailid IS NOT NULL AND p.tbdetailid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbbookingticket.tbbookingid -> tbbooking' AS fk, COUNT(*) AS huerfanos FROM tbbookingticket c LEFT JOIN tbbooking p ON c.tbbookingid = p.tbbookingid WHERE c.tbbookingid IS NOT NULL AND p.tbbookingid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbbookingticket.tbpaymentmethodid -> tbpaymentmethod' AS fk, COUNT(*) AS huerfanos FROM tbbookingticket c LEFT JOIN tbpaymentmethod p ON c.tbpaymentmethodid = p.tbpaymentmethodid WHERE c.tbpaymentmethodid IS NOT NULL AND p.tbpaymentmethodid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbownerpayment.tbownerid -> tbowner' AS fk, COUNT(*) AS huerfanos FROM tbownerpayment c LEFT JOIN tbowner p ON c.tbownerid = p.tbownerid WHERE c.tbownerid IS NOT NULL AND p.tbownerid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbownerpayment.tbpaymentmethodid -> tbpaymentmethod' AS fk, COUNT(*) AS huerfanos FROM tbownerpayment c LEFT JOIN tbpaymentmethod p ON c.tbpaymentmethodid = p.tbpaymentmethodid WHERE c.tbpaymentmethodid IS NOT NULL AND p.tbpaymentmethodid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbinvoice.tbbookingid -> tbbooking' AS fk, COUNT(*) AS huerfanos FROM tbinvoice c LEFT JOIN tbbooking p ON c.tbbookingid = p.tbbookingid WHERE c.tbbookingid IS NOT NULL AND p.tbbookingid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbinvoice.tbpaymentmethodid -> tbpaymentmethod' AS fk, COUNT(*) AS huerfanos FROM tbinvoice c LEFT JOIN tbpaymentmethod p ON c.tbpaymentmethodid = p.tbpaymentmethodid WHERE c.tbpaymentmethodid IS NOT NULL AND p.tbpaymentmethodid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbeearning.tbbookingid -> tbbooking' AS fk, COUNT(*) AS huerfanos FROM tbeearning c LEFT JOIN tbbooking p ON c.tbbookingid = p.tbbookingid WHERE c.tbbookingid IS NOT NULL AND p.tbbookingid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbeearning.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbeearning c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbbookinghistory.tbbookingid -> tbbooking' AS fk, COUNT(*) AS huerfanos FROM tbbookinghistory c LEFT JOIN tbbooking p ON c.tbbookingid = p.tbbookingid WHERE c.tbbookingid IS NOT NULL AND p.tbbookingid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbbookinghistory.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbbookinghistory c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbbookingrefund.tbbookingid -> tbbooking' AS fk, COUNT(*) AS huerfanos FROM tbbookingrefund c LEFT JOIN tbbooking p ON c.tbbookingid = p.tbbookingid WHERE c.tbbookingid IS NOT NULL AND p.tbbookingid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbbookingrefund.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbbookingrefund c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbownerhistory.tbownerid -> tbowner' AS fk, COUNT(*) AS huerfanos FROM tbownerhistory c LEFT JOIN tbowner p ON c.tbownerid = p.tbownerid WHERE c.tbownerid IS NOT NULL AND p.tbownerid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbuserhistory.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbuserhistory c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbrolpasswordhistorical.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbrolpasswordhistorical c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbrolphonehistorical.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbrolphonehistorical c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbrolemailhistorical.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbrolemailhistorical c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbvenuerating.tbvenueid -> tbvenue' AS fk, COUNT(*) AS huerfanos FROM tbvenuerating c LEFT JOIN tbvenue p ON c.tbvenueid = p.tbvenueid WHERE c.tbvenueid IS NOT NULL AND p.tbvenueid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbvenuerating.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbvenuerating c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbservicerating.tbserviceid -> tbservice' AS fk, COUNT(*) AS huerfanos FROM tbservicerating c LEFT JOIN tbservice p ON c.tbserviceid = p.tbserviceid WHERE c.tbserviceid IS NOT NULL AND p.tbserviceid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbservicerating.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbservicerating c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbnotification.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbnotification c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbcreditconfig.tbownerid -> tbowner' AS fk, COUNT(*) AS huerfanos FROM tbcreditconfig c LEFT JOIN tbowner p ON c.tbownerid = p.tbownerid WHERE c.tbownerid IS NOT NULL AND p.tbownerid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbcredit.tbbookingid -> tbbooking' AS fk, COUNT(*) AS huerfanos FROM tbcredit c LEFT JOIN tbbooking p ON c.tbbookingid = p.tbbookingid WHERE c.tbbookingid IS NOT NULL AND p.tbbookingid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbcredit.tbclientid -> tbclient' AS fk, COUNT(*) AS huerfanos FROM tbcredit c LEFT JOIN tbclient p ON c.tbclientid = p.tbclientid WHERE c.tbclientid IS NOT NULL AND p.tbclientid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbcredit.tbownerid -> tbowner' AS fk, COUNT(*) AS huerfanos FROM tbcredit c LEFT JOIN tbowner p ON c.tbownerid = p.tbownerid WHERE c.tbownerid IS NOT NULL AND p.tbownerid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbcreditpayment.tbcreditid -> tbcredit' AS fk, COUNT(*) AS huerfanos FROM tbcreditpayment c LEFT JOIN tbcredit p ON c.tbcreditid = p.tbcreditid WHERE c.tbcreditid IS NOT NULL AND p.tbcreditid IS NULL HAVING COUNT(*) > 0;
SELECT 'tbcreditpayment.tbroleid -> tbrole' AS fk, COUNT(*) AS huerfanos FROM tbcreditpayment c LEFT JOIN tbrole p ON c.tbroleid = p.tbroleid WHERE c.tbroleid IS NOT NULL AND p.tbroleid IS NULL HAVING COUNT(*) > 0;

-- ---------------------------------------------------------
-- 2) CLAVES FORÁNEAS (50)
-- ---------------------------------------------------------

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
