# Insider One Case

Bu proje, PHP 8.4, Laravel 11+, PostgreSQL 16 ve Redis 7 kullanılarak hazırlanan bir bildirim yönetimi (Notification Management) servisidir. Tüm mimari Docker üzerinde çalışacak şekilde yapılandırılmıştır.

## 🚀 Hızlı Kurulum

Projeyi bilgisayarınızda çalıştırmak için aşağıdaki adımları sırasıyla izleyin:

### 1. Ortam Değişkenlerini Ayarlayın
Proje dizinindeki örnek `.env` dosyasını kopyalayarak kendi `.env` dosyanızı oluşturun:
```bash
cp .env.example .env
```

### 2. Docker Konteynerlerini Başlatın
Docker ile tüm servisleri (App, Nginx, PostgreSQL, Redis) ayağa kaldırın:
```bash
docker compose up -d --build
```
*(Not: İlk kurulumda build işlemi biraz zaman alabilir. Veritabanı ve bekleme servislerinin tam olarak hazır olması birkaç saniye sürebilir.)*

### 3. Bağımlılıkları Yükleyin ve Veritabanını Hazırlayın
Konteynerler çalıştıktan sonra Laravel bağımlılıklarını kurmak ve veritabanı tablolarını oluşturmak için aşağıdaki komutları sırasıyla çalıştırın:
```bash
# Gerekli PHP paketlerini yükler
docker compose exec app composer install

# Laravel uygulama anahtarını üretir
docker compose exec app php artisan key:generate

# Veritabanı tablolarını oluşturur
docker compose exec app php artisan migrate
```

### 4. Swagger Dokümantasyonunu Oluşturun
API dokümantasyonunu görüntülemek için Swagger dosyalarını derleyin:
```bash
docker compose exec app php artisan l5-swagger:generate
```

---

## 🌐 API ve Servislere Erişim

Kurulum tamamlandıktan sonra proje servislerine aşağıdaki adreslerden ulaşabilirsiniz:

- **API Base URL:** `http://localhost:8000/api/v1`
- **Swagger API Dokümantasyonu:** `http://localhost:8000/api/documentation`
- **Sistem Sağlık Durumu (Health Check):** `http://localhost:8000/api/v1/health`

---

##  Sık Kullanılan Komutlar

Sistemi yönetirken kullanabileceğiniz bazı temel komutlar:

```bash
# Logları anlık olarak takip etmek için:
docker compose logs -f

# İçerideki kuyruk (queue) loglarını takip etmek için:
docker compose exec app tail -f /var/log/supervisor/queue.err.log

# Artisan komutu çalıştırmak için (Örn: cache temizleme):
docker compose exec app php artisan optimize:clear

# Sistemi durdurmak için:
docker compose down

# Sistemi durdurup tüm verileri (veritabanı dahil) tamamen silmek için:
docker compose down -v
```