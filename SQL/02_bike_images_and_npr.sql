-- ============================================================
-- Bike Rental System - bike images + Nepali Rupee pricing
-- Run this against an EXISTING database (phpMyAdmin > SQL > Go)
-- Safe to re-run.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- ---------------------------------------------------------
-- 1. Add an image column to the bike table
-- ---------------------------------------------------------

SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME   = 'bike'
     AND COLUMN_NAME  = 'image'
);

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `bike` ADD COLUMN `image` varchar(255) DEFAULT NULL AFTER `bike_name`',
  'DO 0');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ---------------------------------------------------------
-- 2. Widen price so hourly Nepali Rupee rates fit comfortably
-- ---------------------------------------------------------

ALTER TABLE `bike` MODIFY `price` int(7) NOT NULL;


-- ---------------------------------------------------------
-- 3. Realistic NPR hourly rates + photo per bike
-- ---------------------------------------------------------

UPDATE `bike` SET `image` = 'placeholder.svg'  WHERE `image` IS NULL OR `image` = '';

UPDATE `bike` SET `price` = 1500, `image` = '101-pulsar.svg'     WHERE `bike_id` = 101;
UPDATE `bike` SET `price` = 1500, `image` = '102-rx100.svg'      WHERE `bike_id` = 102;
UPDATE `bike` SET `price` = 1200, `image` = '103-activa.svg'     WHERE `bike_id` = 103;
UPDATE `bike` SET `price` = 1200, `image` = '104-jupiter.svg'    WHERE `bike_id` = 104;
UPDATE `bike` SET `price` = 1500, `image` = '105-apachertr.svg'  WHERE `bike_id` = 105;
UPDATE `bike` SET `price` = 1200, `image` = '106-vespa.svg'      WHERE `bike_id` = 106;
UPDATE `bike` SET `price` = 1500, `image` = '107-ktm.svg'        WHERE `bike_id` = 107;
UPDATE `bike` SET `price` = 1200, `image` = '108-pleasure.svg'   WHERE `bike_id` = 108;
UPDATE `bike` SET `price` = 1200, `image` = '109-yamaharay.svg'  WHERE `bike_id` = 109;
UPDATE `bike` SET `price` = 1200, `image` = '110-hero.svg'       WHERE `bike_id` = 110;


-- ---------------------------------------------------------
-- 4. Fix spcost
--
--    ongointns.php calls  CALL spcost($diff, $bikeid)  where
--    $diff = strtotime(end) - strtotime(start)  = SECONDS.
--
--    The old body multiplied those seconds by the hourly
--    price as if they were hours, so a 49 second ride on a
--    Rs.32/hr bike was billed 49 * 32 = Rs.1568. With real
--    NPR rates that becomes catastrophic, so the procedure
--    now converts seconds to hours and bills a 1 hour
--    minimum.
-- ---------------------------------------------------------

DROP PROCEDURE IF EXISTS `spcost`;

DELIMITER $$
CREATE PROCEDURE `spcost`(IN p_seconds INT, IN p_bikeid INT)
BEGIN
  DECLARE v_hours DECIMAL(10,2);
  DECLARE v_price INT DEFAULT 0;

  SELECT `price` INTO v_price FROM `bike` WHERE `bike_id` = p_bikeid;

  SET v_hours = GREATEST(ROUND(p_seconds / 3600, 2), 1);

  UPDATE `payment`
     SET `cost` = v_hours * v_price
   WHERE `bike_id` = p_bikeid
     AND `date` IS NULL;
END$$
DELIMITER ;


-- ---------------------------------------------------------
-- 5. Re-bill any completed payments that used the old maths
-- ---------------------------------------------------------

UPDATE `payment` p
  JOIN `transaction` t ON t.`bike_id` = p.`bike_id` AND t.`email` = p.`email`
  JOIN `bike` b ON b.`bike_id` = p.`bike_id`
   SET p.`cost` = GREATEST(ROUND(TIMESTAMPDIFF(SECOND, t.`start_time`, t.`end_time`) / 3600, 2), 1) * b.`price`
 WHERE p.`date` IS NOT NULL
   AND t.`end_time` IS NOT NULL;
