saas-boilerplate/
├── public/
│   └── index.php          ← Entry point
├── src/
│   ├── Controllers/       ← Thin HTTP controllers
│   │   ├── Auth/
│   │   ├── Tenant/
│   │   └── Billing/
│   ├── Services/          ← Business logic lives here
│   │   ├── AuthService.php
│   │   ├── TenantService.php
│   │   └── BillingService.php
│   ├── Repositories/      ← Data access layer
│   │   ├── Interfaces/
│   │   └── Eloquent/      ← or PDO/custom
│   ├── Middleware/
│   │   ├── JwtMiddleware.php
│   │   ├── TenantMiddleware.php
│   │   └── RbacMiddleware.php
│   ├── Models/
│   └── Exceptions/
├── database/
│   └── migrations/
├── config/
│   ├── app.php
│   └── database.php
├── routes/
│   └── api.php
└── composer.json