# BiharBusiness.com — Laravel API

> REST API backend for Bihar's local business directory platform

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat&logo=laravel)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql)
![License](https://img.shields.io/badge/License-MIT-green)

## Tech Stack
- **Backend:** Laravel 11, PHP 8.2
- **Database:** MySQL 8.0
- **Cache/Queue:** Redis
- **Auth:** Laravel Sanctum + Firebase Phone OTP
- **Payments:** Razorpay UPI (WebView compatible)
- **Mobile:** Capacitor 8 Android

## Features
- 475+ business listings across Bihar districts
- Three-tier monetization: Free / Featured ₹999 / Premium ₹2499
- Firebase Phone OTP authentication
- Razorpay UPI payment with webhook verification
- District & category based filtering
- WhatsApp lead generation links
- Admin panel for listing management

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/businesses | List all businesses |
| GET | /api/v1/businesses/{id} | Single business |
| POST | /api/v1/businesses | Create listing |
| PUT | /api/v1/businesses/{id}/upgrade-tier | Upgrade tier |
| POST | /api/v1/auth/firebase-login | OTP Login |
| POST | /api/v1/payments/create-order | Razorpay order |

## Setup

```bash
git clone https://github.com/nirajtech15/biharbusiness-api.git
cd biharbusiness-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Live Project
🌐 [biharbusiness.com](https://biharbusiness.com)

## Developer
**Er. Niraj Singh** — Senior Full-Stack Developer, 9 years exp  
Pharma IT · Laravel · Angular · Node.js  
📍 Ghaziabad, NCR | Open to Noida/Gurugram/Remote
