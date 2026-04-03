
-- =============================================
-- Hotel DB schema + seed (MAMP / MySQL 8)
-- DB: hotel_db
-- =============================================

-- Safe re-run
DROP DATABASE IF EXISTS hotel_db;
CREATE DATABASE hotel_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE hotel_db;

-- ---------- Reference: room types ----------
CREATE TABLE room_types (
  code        VARCHAR(20) PRIMARY KEY,         -- 'single','twin','double','deluxe','suite'
  name_jp     VARCHAR(50) NOT NULL,            -- 日本語名
  max_capacity TINYINT UNSIGNED NOT NULL,      -- タイプの最大想定定員
  default_bed VARCHAR(50) NOT NULL             -- 代表的なベッドタイプ
) ENGINE=InnoDB;

INSERT INTO room_types (code, name_jp, max_capacity, default_bed) VALUES
('single','シングル',1,'シングルベッド'),
('twin','ツイン',2,'セミダブル×2'),
('double','ダブル',2,'ダブルベッド'),
('deluxe','デラックス',2,'キングベッド'),
('suite','スイート',3,'キング＋ソファ');

-- ---------- Physical rooms ----------
CREATE TABLE rooms (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  roomNo       INT NOT NULL UNIQUE,                 -- 物理ルーム番号
  type_code    VARCHAR(20) NOT NULL,                -- FK -> room_types
  floor        TINYINT UNSIGNED NOT NULL,
  capacity     TINYINT UNSIGNED NOT NULL,
  base_price   INT UNSIGNED NOT NULL,               -- 税抜/素泊まりの基準価格
  size_sqm     DECIMAL(5,1) NULL,                   -- 平米
  bed_type     VARCHAR(50) NULL,
  view_label   VARCHAR(50) NULL,                    -- Tokyo view / Park view など
  description  TEXT NULL,
  status       ENUM('active','out_of_service') NOT NULL DEFAULT 'active',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_rooms_type FOREIGN KEY (type_code) REFERENCES room_types(code)
) ENGINE=InnoDB;

-- サンプル客室（必要に応じて増やしてください）
INSERT INTO rooms (roomNo, type_code, floor, capacity, base_price, size_sqm, bed_type, view_label, description) VALUES
(701,'single',7,1,26000,18.0,'シングル','City','シンプルで機能的なシングルルーム'),
(702,'single',7,1,26000,18.5,'シングル','City','デスクが広いワーク向けシングル'),
(803,'twin',8,2,38000,26.0,'セミダブル×2','City','ファミリーにも最適なツイン'),
(804,'twin',8,2,39000,26.5,'セミダブル×2','Park','高層階ツイン'),
(901,'double',9,2,36000,24.0,'ダブル','City','スタンダードダブル'),
(902,'double',9,2,37000,24.0,'ダブル','Park','角部屋ダブル'),
(1101,'deluxe',11,2,42000,34.0,'キング','Tokyo View','広めのデラックス キング'),
(1102,'deluxe',11,2,43000,34.0,'キング','Park','静かなパークビュー'),
(1201,'deluxe',12,2,45000,36.0,'キング','Tokyo View','人気の東京ビュー・デラックス'),
(1202,'suite',12,3,82000,80.0,'キング＋ソファ','Panorama','リビング付きコーナースイート'),
(1501,'suite',15,3,98000,95.0,'キング＋ソファ','Panorama','最上階スイート'),
(1502,'suite',15,3,110000,110.0,'キング＋ソファ','Skyline','プレジデンシャルスイート');

-- ---------- Room images (relative paths) ----------
CREATE TABLE room_images (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  room_id   INT NOT NULL,
  path      VARCHAR(255) NOT NULL,          -- 例: /hotel-portfolio/images/rooms/deluxe-1101-1.jpg
  alt_text  VARCHAR(120) NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  sort_no   INT NOT NULL DEFAULT 1,
  CONSTRAINT fk_images_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
  INDEX idx_room_sort (room_id, sort_no)
) ENGINE=InnoDB;

-- 代表画像だけ数件（必要に応じて追加）
INSERT INTO room_images (room_id, path, alt_text, is_primary, sort_no)
SELECT id, CONCAT('/hotel-portfolio/images/rooms/', roomNo, '-1.jpg'), CONCAT('Room ', roomNo, ' メイン'), 1, 1
FROM rooms;

-- ---------- Reservations ----------
CREATE TABLE reservations (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  room_id     INT NOT NULL,
  checkin     DATE NOT NULL,
  checkout    DATE NOT NULL,
  guests      TINYINT UNSIGNED NOT NULL DEFAULT 1,
  guest_name  VARCHAR(120) NULL,
  email       VARCHAR(120) NULL,
  phone       VARCHAR(40)  NULL,
  total_price INT UNSIGNED NULL,
  status      ENUM('reserved','cancelled','checked_in','checked_out') NOT NULL DEFAULT 'reserved',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_resv_room FOREIGN KEY (room_id) REFERENCES rooms(id),
  INDEX idx_resv_room (room_id),
  INDEX idx_resv_span (checkin, checkout)
) ENGINE=InnoDB;

-- テスト用の予約（重なり判定の動作確認に利用）
-- 例: 2026-01-15〜2026-01-17 で room_id=1 をブロック
INSERT INTO reservations (room_id, checkin, checkout, guests, guest_name, total_price)
VALUES (1, '2026-01-15', '2026-01-17', 1, 'テスト太郎', 52000);

-- ---------- Helper: availability test queries ----------

-- 使い方:
-- SET @checkin='2026-01-09', @checkout='2026-01-10', @guests=1, @type='';
-- （typeは '' / 'single' / 'twin' / 'double' / 'deluxe' / 'suite'）

-- 可用ルーム検索（APIのSQLと同等ロジック）
-- SELECT r.*
-- FROM rooms r
-- WHERE r.status='active'
--   AND r.capacity >= @guests
--   AND (@type = '' OR r.type_code = @type)
--   AND r.id NOT IN (
--     SELECT room_id FROM reservations
--     WHERE NOT (checkout <= @checkin OR checkin >= @checkout)
--   )
-- ORDER BY r.base_price ASC;

-- 予約登録例（トランザクション）
-- START TRANSACTION;
--   -- まず最新の空き確認
--   SELECT COUNT(*) INTO @is_busy FROM reservations
--   WHERE room_id = 1101
--     AND NOT (checkout <= '2026-01-20' OR checkin >= '2026-01-22')
--     FOR UPDATE;
--   -- 空きならINSERT
--   IF (@is_busy = 0) THEN
--     INSERT INTO reservations(room_id,checkin,checkout,guests,guest_name,total_price)
--     VALUES (1101,'2026-01-20','2026-01-22',2,'山田様', 84000);
--   END IF;
-- COMMIT;

