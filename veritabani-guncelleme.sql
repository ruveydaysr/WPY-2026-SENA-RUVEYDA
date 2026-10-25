-- Servis işlem geçmişi tablosu
CREATE TABLE IF NOT EXISTS servis_islem_gecmisi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    servis_id INT NOT NULL,
    teknisyen_id INT NOT NULL,
    islem_aciklamasi TEXT NOT NULL,
    eski_durum VARCHAR(50),
    yeni_durum VARCHAR(50),
    islem_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (servis_id) REFERENCES servisler(id),
    FOREIGN KEY (teknisyen_id) REFERENCES kullanicilar(id)
);

-- Müşteri ücret onayı için yeni alanlar
ALTER TABLE servisler
    ADD COLUMN tahmini_ucret DECIMAL(10,2) DEFAULT NULL,
    ADD COLUMN ucret_onay_durumu VARCHAR(30) DEFAULT NULL,
    ADD COLUMN ucret_onay_tarihi DATETIME DEFAULT NULL;